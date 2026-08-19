<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Integration;

use Exception;
use Soneso\StellarSDKTests\TestUtils;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\AccountMergeOperationBuilder;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;
use Soneso\StellarSDK\BumpSequenceOperationBuilder;
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\CreateAccountOperationBuilder;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\ManageDataOperationBuilder;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Responses\Effects\SignerCreatedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\SignerEffectResponse;
use Soneso\StellarSDK\Responses\Effects\SignerRemovedEffectResponse;
use Soneso\StellarSDK\Responses\Effects\SignerUpdatedEffectResponse;
use Soneso\StellarSDK\SetOptionsOperation;
use Soneso\StellarSDK\SetOptionsOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Xdr\XdrSignerKey;
use Soneso\StellarSDK\Xdr\XdrSignerKeyType;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertTrue;

final class AccountTest extends TestCase
{

    private string $testOn = 'testnet'; // 'futurenet'
    private Network $network;
    private StellarSDK $sdk;

    public function setUp(): void
    {
        if ($this->testOn === 'testnet') {
            $this->network = Network::testnet();
            $this->sdk = StellarSDK::getTestNetInstance();
        } elseif ($this->testOn === 'futurenet') {
            $this->network = Network::futurenet();
            $this->sdk = StellarSDK::getFutureNetInstance();
        }
    }
    public function testSetAccountOptions(): void {

        $isValid = true;
        try {
            KeyPair::fromAccountId("GBEJWZEYDCJIKBW7PZKIJPRHD6WSPNETCEDV5UWRLDBLKXA7QT2DTLVF");
        } catch (Exception $e) {
            $isValid = false;
        }
        if ($isValid) {
            self::fail();
        }
        $keyPairA = KeyPair::random();
        $accountId = $keyPairA->getAccountId();
        TestUtils::fundTestAccountAndAwaitVisibility(
            $accountId,
            horizon: $this->sdk,
            useFuturenet: $this->testOn !== 'testnet',
        );

        $accountA = $this->sdk->requestAccount($accountId);
        $seqNr = $accountA->getSequenceNumber();

        $keyPairB = KeyPair::random();
        $bkey = new XdrSignerKey();
        $bkey->setType(new XdrSignerKeyType(XdrSignerKeyType::ED25519));
        $bkey->setEd25519($keyPairB->getPublicKey());

        $newHomeDomain = "www".rand(1, 10000).".com";

        $setOptionsOperation = (new SetOptionsOperationBuilder())
            ->setHomeDomain($newHomeDomain)
            ->setSigner($bkey, 6)
            ->setHighThreshold(5)
            ->setMediumThreshold(3)
            ->setLowThreshold(2)
            ->setMasterKeyWeight(5)
            ->setSetFlags(2)
            ->build();

        // test issue #7
        $xdrTest = $setOptionsOperation->toXdr();
        $setOpTest = SetOptionsOperation::fromXdr($xdrTest);
        self::assertEquals($setOptionsOperation->getHomeDomain(), $setOpTest->getHomeDomain());

        $transaction = (new TransactionBuilder($accountA))
            ->addOperation($setOptionsOperation)
            ->addMemo(Memo::text("test set options"))
            ->build();

        $transaction->sign($keyPairA, $this->network);
        $response = $this->sdk->submitTransaction($transaction);

        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);


        $this->assertTrue($response->isSuccessful());

        $accountA = $this->sdk->requestAccount($accountId);
        $this->assertTrue($accountA->getSequenceNumber() > $seqNr);
        $this->assertTrue($accountA->getHomeDomain() === $newHomeDomain);
        $this->assertTrue($accountA->getThresholds()->getLowThreshold() === 2);
        $this->assertTrue($accountA->getThresholds()->getMedThreshold() === 3);
        $this->assertTrue($accountA->getThresholds()->getHighThreshold() === 5);

        $aFound = false;
        $bFound = false;
        foreach($accountA->getSigners() as $signer) {
            if ($signer->getKey() == $accountA->getAccountId()) {
                $aFound = true;
                $this->assertTrue($signer->getWeight() === 5);
            }
            else if ($signer->getKey() == $keyPairB->getAccountId()) {
                $bFound = true;
                $this->assertTrue($signer->getWeight() === 6);
            }
        }
        $this->assertTrue($aFound);
        $this->assertTrue($bFound);

        $this->assertTrue($accountA->getFlags()->isAuthRequired() == false);
        $this->assertTrue($accountA->getFlags()->isAuthRevocable() == true);
        $this->assertTrue($accountA->getFlags()->isAuthImmutable() == false);

        // Walk signer B through the rest of its lifecycle. Adding it above emitted a
        // signer_created effect; raising its weight emits signer_updated and zeroing
        // it emits signer_removed.
        $accountA = $this->sdk->requestAccount($accountId);
        $updateSignerTransaction = (new TransactionBuilder($accountA))
            ->addOperation((new SetOptionsOperationBuilder())->setSigner($bkey, 8)->build())
            ->build();
        $updateSignerTransaction->sign($keyPairA, $this->network);
        $response = $this->sdk->submitTransaction($updateSignerTransaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $updateSignerTransaction, $response);

        $accountA = $this->sdk->requestAccount($accountId);
        $removeSignerTransaction = (new TransactionBuilder($accountA))
            ->addOperation((new SetOptionsOperationBuilder())->setSigner($bkey, 0)->build())
            ->build();
        $removeSignerTransaction->sign($keyPairA, $this->network);
        $response = $this->sdk->submitTransaction($removeSignerTransaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $removeSignerTransaction, $response);

        $accountA = $this->sdk->requestAccount($accountId);
        $bStillPresent = false;
        foreach ($accountA->getSigners() as $signer) {
            if ($signer->getKey() === $keyPairB->getAccountId()) {
                $bStillPresent = true;
            }
        }
        $this->assertFalse($bStillPresent);

        // Horizon ingests effects behind transaction inclusion, so poll until all
        // three signer effects for B are served or the budget runs out.
        $bAccountId = $keyPairB->getAccountId();
        $signerCreated = null;
        $signerUpdated = null;
        $signerRemoved = null;
        $start = microtime(true);
        while (true) {
            $effectsResponse = $this->sdk->effects()->forAccount($accountId)->limit(200)->order("desc")->execute();
            foreach ($effectsResponse->getEffects() as $effect) {
                if (!($effect instanceof SignerEffectResponse) || $effect->getPublicKey() !== $bAccountId) {
                    continue;
                }
                if ($effect instanceof SignerCreatedEffectResponse) {
                    $signerCreated = $effect;
                } elseif ($effect instanceof SignerUpdatedEffectResponse) {
                    $signerUpdated = $effect;
                } elseif ($effect instanceof SignerRemovedEffectResponse) {
                    $signerRemoved = $effect;
                }
            }
            if ($signerCreated !== null && $signerUpdated !== null && $signerRemoved !== null) {
                break;
            }
            if (microtime(true) - $start >= 60) {
                self::fail('Horizon did not serve all three signer effects for ' . $bAccountId . ' within 60 s');
            }
            sleep(3);
        }

        // Assert the wire codes as literals. The response class alone only shows that
        // dispatch agreed with the SDK's own constants; the literal type_i values are
        // what tie those constants to Horizon. Horizon renders all three through the
        // same resource struct, so signer_removed reports a zero weight rather than
        // omitting the field.
        $this->assertEquals('signer_created', $signerCreated->getHumanReadableEffectType());
        $this->assertEquals(10, $signerCreated->getEffectType());
        $this->assertEquals($bAccountId, $signerCreated->getPublicKey());
        $this->assertEquals(6, $signerCreated->getWeight());

        $this->assertEquals('signer_updated', $signerUpdated->getHumanReadableEffectType());
        $this->assertEquals(12, $signerUpdated->getEffectType());
        $this->assertEquals($bAccountId, $signerUpdated->getPublicKey());
        $this->assertEquals(8, $signerUpdated->getWeight());

        $this->assertEquals('signer_removed', $signerRemoved->getHumanReadableEffectType());
        $this->assertEquals(11, $signerRemoved->getEffectType());
        $this->assertEquals($bAccountId, $signerRemoved->getPublicKey());
        $this->assertEquals(0, $signerRemoved->getWeight());
    }

    public function testFindAccountforAsset(): void {
        $keyPairA = KeyPair::random();
        $accountAId = $keyPairA->getAccountId();
        TestUtils::fundTestAccountAndAwaitVisibility(
            $accountAId,
            horizon: $this->sdk,
            useFuturenet: $this->testOn !== 'testnet',
        );
        $accountA = $this->sdk->requestAccount($accountAId);

        $keyPairC = KeyPair::random();
        $accountCId = $keyPairC->getAccountId();

        $createAccountOperation = (new CreateAccountOperationBuilder($accountCId, "10"))->build();
        $transaction = (new TransactionBuilder($accountA))
            ->addOperation($createAccountOperation)
            ->build();

        $transaction->sign($keyPairA, $this->network);
        $response = $this->sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $iomAsset = new AssetTypeCreditAlphanum4("IOM", $accountCId);

        $changeTrustOperation = (new ChangeTrustOperationBuilder($iomAsset, "200999"))->build();
        $transaction = (new TransactionBuilder($accountA))
            ->addOperation($changeTrustOperation)
            ->build();
        $transaction->sign($keyPairA, $this->network);
        $response = $this->sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        // Find account for asset
        $response = $this->sdk->accounts()->forAsset($iomAsset)->execute();
        $this->assertGreaterThan(0, $response->getAccounts()->count());
        $found = false;
        foreach ($response->getAccounts() as $account) {
            $this->assertTrue($account->getAccountId() === $accountAId);
            $found = true;
        }
        $this->assertTrue($found);
    }

    public function testAccountMerge(): void {
        $keyPairX = KeyPair::random();
        $keyPairY = KeyPair::random();
        $accountXId = $keyPairX->getAccountId();
        $accountYId = $keyPairY->getAccountId();
        TestUtils::fundTestAccountAndAwaitVisibility(
            $accountXId,
            horizon: $this->sdk,
            useFuturenet: $this->testOn !== 'testnet',
        );
        TestUtils::fundTestAccountAndAwaitVisibility(
            $accountYId,
            horizon: $this->sdk,
            useFuturenet: $this->testOn !== 'testnet',
        );

        $accountMergeOperation = (new AccountMergeOperationBuilder($accountXId))->build();
        $accountY = $this->sdk->requestAccount($accountYId);
        $transaction = (new TransactionBuilder($accountY))
            ->addOperation($accountMergeOperation)
            ->build();

        $transaction->sign($keyPairY, $this->network);
        $response = $this->sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $this->assertFalse($this->sdk->accountExists($accountYId));
    }

    public function testAccountMergeMuxedAccounts(): void {
        $keyPairX = KeyPair::random();
        $keyPairY = KeyPair::random();
        $accountXId = $keyPairX->getAccountId();
        $accountYId = $keyPairY->getAccountId();
        TestUtils::fundTestAccountAndAwaitVisibility(
            $accountXId,
            horizon: $this->sdk,
            useFuturenet: $this->testOn !== 'testnet',
        );
        TestUtils::fundTestAccountAndAwaitVisibility(
            $accountYId,
            horizon: $this->sdk,
            useFuturenet: $this->testOn !== 'testnet',
        );

        $muxedDestination = new MuxedAccount($accountXId, 1919198222);
        $muxedSource = new MuxedAccount($accountYId, 99999999);
        $accountMergeOperation = (AccountMergeOperationBuilder::forMuxedDestinationAccount($muxedDestination))
            ->setMuxedSourceAccount($muxedSource)
            ->build();

        $accountY = $this->sdk->requestAccount($accountYId);
        $transaction = (new TransactionBuilder($accountY))
            ->addOperation($accountMergeOperation)
            ->build();

        $transaction->sign($keyPairY, $this->network);
        $response = $this->sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $this->assertFalse($this->sdk->accountExists($accountYId));
    }

    public function testBumpSequence(): void {
        $keyPair = KeyPair::random();
        $accountId = $keyPair->getAccountId();
        TestUtils::fundTestAccountAndAwaitVisibility(
            $accountId,
            horizon: $this->sdk,
            useFuturenet: $this->testOn !== 'testnet',
        );

        $account = $this->sdk->requestAccount($accountId);

        $seqNr = $account->getSequenceNumber();
        $bumpTo = $seqNr->add(new BigInteger(10));
        $bumpSequenceOperation = (new BumpSequenceOperationBuilder($bumpTo))->build();
        $transaction = (new TransactionBuilder($account))
            ->addOperation($bumpSequenceOperation)
            ->build();

        $transaction->sign($keyPair, $this->network);
        $response = $this->sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $account = $this->sdk->requestAccount($accountId);
        $this->assertEquals($bumpTo, $account->getSequenceNumber());
    }

    public function testManageData(): void {
        $keyPair = KeyPair::random();
        $accountId = $keyPair->getAccountId();
        TestUtils::fundTestAccountAndAwaitVisibility(
            $accountId,
            horizon: $this->sdk,
            useFuturenet: $this->testOn !== 'testnet',
        );

        $account = $this->sdk->requestAccount($accountId);

        $key = "soneso";
        $value = "is cool!";
        $manageDataOperation = (new ManageDataOperationBuilder($key, $value))->build();
        $transaction = (new TransactionBuilder($account))
            ->addOperation($manageDataOperation)
            ->build();

        $transaction->sign($keyPair, $this->network);
        $response = $this->sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $account = $this->sdk->requestAccount($accountId);
        $this->assertTrue($account->getData()->get($key) === $value);
    }

    public function testStrKeyAccount(): void {
        $accountId = "GA5SRA3BGOEN6ASL33AVTC2QV7G2PV3DU4A3VDMPEIEZVF2H4Z5YV6CC";
        $tb = StrKey::decodeAccountId($accountId);
        $bt = StrKey::encodeAccountId($tb);
        $this->assertEquals($accountId, $bt);

        $muxAccountId = "MA5SRA3BGOEN6ASL33AVTC2QV7G2PV3DU4A3VDMPEIEZVF2H4Z5YUAAAAAAACL7RNP5CM";
        $tb = StrKey::decodeMuxedAccountId($muxAccountId);
        $bt = StrKey::encodeMuxedAccountId($tb);
        $this->assertEquals($muxAccountId, $bt);
    }

    public function testMuxedAccount(): void {
        $accountId = "GA5SRA3BGOEN6ASL33AVTC2QV7G2PV3DU4A3VDMPEIEZVF2H4Z5YV6CC";
        $id = 19919211;
        $muxAccount = MuxedAccount::fromAccountId($accountId);
        $this->assertEquals($accountId, $muxAccount->getAccountId());

        $muxAccountId = "MA5SRA3BGOEN6ASL33AVTC2QV7G2PV3DU4A3VDMPEIEZVF2H4Z5YUAAAAAAACL7RNP5CM";
        $muxAccount = MuxedAccount::fromAccountId($muxAccountId);
        $this->assertEquals($muxAccountId, $muxAccount->getAccountId());
        $muxAccount = new MuxedAccount($accountId, $id);
        $this->assertEquals($muxAccountId, $muxAccount->getAccountId());
    }

    public function testHorizonRequestException() {
        //bogus invalid key
        $accountId = 'GC3CT2B55RPLE6JX2U3SOPZFGSE5A3MYFBFYAXSQCJ5BBYJX5HIBTNIX';

        $thrown = false;
        try {
            $this->sdk->requestAccount($accountId);
        } catch (HorizonRequestException $e) {
            $horizonResponse = $e->getHorizonErrorResponse();
            assertNotNull($horizonResponse);
            print($horizonResponse->title . ':' . $horizonResponse->detail . PHP_EOL);
            $extras = $horizonResponse->getExtrasJson();
            assertNotNull($extras);
            foreach ($extras as $key => $value) {
                print($key . " = " . $value . PHP_EOL);
            }
            $thrown = true;
        }
        assertTrue($thrown);
    }

    public function testAccountDataEndpoint(): void {
        // Create a new test account
        $keyPair = KeyPair::random();
        $accountId = $keyPair->getAccountId();

        TestUtils::fundTestAccountAndAwaitVisibility(
            $accountId,
            horizon: $this->sdk,
            useFuturenet: $this->testOn !== 'testnet',
        );

        // Load account
        $account = $this->sdk->requestAccount($accountId);

        // Add a data entry
        $dataKey = "testKey";
        $dataValue = "testValue";

        $manageDataOp = (new ManageDataOperationBuilder($dataKey, $dataValue))->build();
        $transaction = (new TransactionBuilder($account))
            ->addOperation($manageDataOp)
            ->build();

        $transaction->sign($keyPair, $this->network);
        $response = $this->sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        // Wait for transaction to be processed
        sleep(3);

        // Test fetching the data entry using the accountData endpoint
        $dataResponse = $this->sdk->accounts()->accountData($accountId, $dataKey);

        // Verify base64-encoded value
        $encodedValue = $dataResponse->getValue();
        $this->assertNotNull($encodedValue);
        $this->assertNotEmpty($encodedValue);

        // Verify decoded value matches original
        $decodedValue = $dataResponse->getDecodedValue();
        $this->assertEquals($dataValue, $decodedValue);

        // Test non-existent key (should throw exception)
        $thrown = false;
        try {
            $this->sdk->accounts()->accountData($accountId, "nonExistentKey");
        } catch (HorizonRequestException $e) {
            $this->assertEquals(404, $e->getStatusCode());
            $thrown = true;
        }
        $this->assertTrue($thrown);

        // Clean up: delete the data entry
        $account = $this->sdk->requestAccount($accountId);
        $deleteDataOp = (new ManageDataOperationBuilder($dataKey, null))->build();
        $transaction = (new TransactionBuilder($account))
            ->addOperation($deleteDataOp)
            ->build();

        $transaction->sign($keyPair, $this->network);
        $response = $this->sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        // Verify deletion - should throw 404
        sleep(3);
        $thrown = false;
        try {
            $this->sdk->accounts()->accountData($accountId, $dataKey);
        } catch (HorizonRequestException $e) {
            $this->assertEquals(404, $e->getStatusCode());
            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    public function testStreamAccount(): void {
        if (!function_exists('pcntl_fork')) {
            $this->markTestSkipped('pcntl extension required for this test (Unix only).');
        }

        // Create and fund test account
        $keyPair = KeyPair::random();
        $accountId = $keyPair->getAccountId();

        TestUtils::fundTestAccountAndAwaitVisibility(
            $accountId,
            horizon: $this->sdk,
            useFuturenet: $this->testOn !== 'testnet',
        );

        // Get initial account state
        $initialAccount = $this->sdk->requestAccount($accountId);
        $initialSequence = $initialAccount->getSequenceNumber();

        $pid = pcntl_fork();

        if ($pid == 0) {
            // Child process - stream account changes
            try {
                $streamSdk = $this->testOn === 'testnet'
                    ? StellarSDK::getTestNetInstance()
                    : StellarSDK::getFutureNetInstance();

                $updateCount = 0;

                $streamSdk->accounts()->streamAccount($accountId, function($account) use ($initialSequence, &$updateCount) {
                    $updateCount++;

                    // Skip initial state
                    if ($updateCount == 1) {
                        return;
                    }

                    // Check if sequence number increased (transaction processed)
                    if ($account->getSequenceNumber()->compare($initialSequence) > 0) {
                        // print("Success" .PHP_EOL);
                        exit(1); // Success
                    }
                });
            } catch (Exception $e) {
                exit(0); // Failure
            }
        } else {
            // Parent process - wait for stream to initialize
            sleep(8);

            // Submit transaction to trigger account update
            $account = $this->sdk->requestAccount($accountId);
            $manageDataOp = (new ManageDataOperationBuilder("test_stream", "streaming_test"))->build();
            $transaction = (new TransactionBuilder($account))
                ->addOperation($manageDataOp)
                ->build();

            $transaction->sign($keyPair, $this->network);
            $response = $this->sdk->submitTransaction($transaction);
            $this->assertTrue($response->isSuccessful());

            // Wait for child to detect update (max 60 seconds for Horizon polling)
            $timeout = 60;
            $elapsed = 0;
            $status = 0;

            while ($elapsed < $timeout) {
                $result = pcntl_waitpid($pid, $status, WNOHANG);
                if ($result > 0) {
                    break;
                }
                sleep(1);
                $elapsed++;
            }

            // print("ELAPSED: " . $elapsed . PHP_EOL);
            if ($elapsed >= $timeout) {
                posix_kill($pid, SIGKILL);
                pcntl_waitpid($pid, $status);
                $this->fail('Stream test timed out - Horizon did not detect account update');
            }

            $exitStatus = pcntl_wexitstatus($status);
            $this->assertEquals(1, $exitStatus, 'Stream should have detected account update');
        }
    }

    public function testStreamAccountData(): void {
        if (!function_exists('pcntl_fork')) {
            $this->markTestSkipped('pcntl extension required for this test (Unix only).');
        }

        // Create and fund test account
        $keyPair = KeyPair::random();
        $accountId = $keyPair->getAccountId();

        TestUtils::fundTestAccountAndAwaitVisibility(
            $accountId,
            horizon: $this->sdk,
            useFuturenet: $this->testOn !== 'testnet',
        );

        // Load account and create initial data entry
        $account = $this->sdk->requestAccount($accountId);
        $dataKey = "stream_test_key";
        $initialValue = "initial_value";

        $manageDataOp = (new ManageDataOperationBuilder($dataKey, $initialValue))->build();
        $transaction = (new TransactionBuilder($account))
            ->addOperation($manageDataOp)
            ->build();

        $transaction->sign($keyPair, $this->network);
        $response = $this->sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        // Wait for transaction to be processed
        sleep(5);

        // Verify initial data entry exists
        $initialData = $this->sdk->accounts()->accountData($accountId, $dataKey);
        $this->assertEquals($initialValue, $initialData->getDecodedValue());

        $pid = pcntl_fork();

        if ($pid == 0) {
            // Child process - stream account data changes
            try {
                $streamSdk = $this->testOn === 'testnet'
                    ? StellarSDK::getTestNetInstance()
                    : StellarSDK::getFutureNetInstance();

                $updateCount = 0;
                $expectedUpdatedValue = "updated_value";

                $streamSdk->accounts()->streamAccountData($accountId, $dataKey, function($dataResponse) use ($expectedUpdatedValue, &$updateCount) {
                    $updateCount++;

                    // Validate response type and structure
                    if (!($dataResponse instanceof \Soneso\StellarSDK\Responses\Account\AccountDataValueResponse)) {
                        exit(0); // Failure - wrong response type
                    }

                    // Validate response has value
                    $value = $dataResponse->getValue();
                    if (empty($value)) {
                        exit(0); // Failure - empty value
                    }

                    // Skip initial state
                    if ($updateCount == 1) {
                        return;
                    }

                    // Check if value was updated to expected value
                    $decodedValue = $dataResponse->getDecodedValue();
                    if ($decodedValue === $expectedUpdatedValue) {
                        // print("Stream detected update: " . $decodedValue . PHP_EOL);
                        exit(1); // Success
                    }
                });
            } catch (Exception $e) {
                // print("Stream error: " . $e->getMessage() . PHP_EOL);
                exit(0); // Failure
            }
        } else {
            // Parent process - wait for stream to initialize
            sleep(8);

            // Submit transaction to update data entry
            $account = $this->sdk->requestAccount($accountId);
            $updatedValue = "updated_value";
            $updateDataOp = (new ManageDataOperationBuilder($dataKey, $updatedValue))->build();
            $transaction = (new TransactionBuilder($account))
                ->addOperation($updateDataOp)
                ->build();

            $transaction->sign($keyPair, $this->network);
            $response = $this->sdk->submitTransaction($transaction);
            $this->assertTrue($response->isSuccessful());

            // Wait for child to detect update (max 60 seconds for Horizon polling)
            $timeout = 60;
            $elapsed = 0;
            $status = 0;

            while ($elapsed < $timeout) {
                $result = pcntl_waitpid($pid, $status, WNOHANG);
                if ($result > 0) {
                    break;
                }
                sleep(1);
                $elapsed++;
            }

            // print("ELAPSED: " . $elapsed . PHP_EOL);
            if ($elapsed >= $timeout) {
                posix_kill($pid, SIGKILL);
                pcntl_waitpid($pid, $status);
                $this->fail('Stream test timed out - Horizon did not detect account data update');
            }

            $exitStatus = pcntl_wexitstatus($status);
            $this->assertEquals(1, $exitStatus, 'Stream should have detected account data update');

            // Clean up: verify final state and delete data entry
            $finalData = $this->sdk->accounts()->accountData($accountId, $dataKey);
            $this->assertEquals($updatedValue, $finalData->getDecodedValue());

            $account = $this->sdk->requestAccount($accountId);
            $deleteDataOp = (new ManageDataOperationBuilder($dataKey, null))->build();
            $transaction = (new TransactionBuilder($account))
                ->addOperation($deleteDataOp)
                ->build();

            $transaction->sign($keyPair, $this->network);
            $response = $this->sdk->submitTransaction($transaction);
            $this->assertTrue($response->isSuccessful());
        }
    }
}

