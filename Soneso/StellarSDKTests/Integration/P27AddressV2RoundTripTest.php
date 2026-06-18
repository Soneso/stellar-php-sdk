<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Integration;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\Requests\SimulateTransactionRequest;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;
use Soneso\StellarSDK\Soroban\SorobanCredentials;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Util\FriendBot;
use Soneso\StellarSDKTests\PrintLogger;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSorobanCredentialsType;

/**
 * Protocol 27 (CAP-71) ADDRESS_V2 round-trip integration test.
 *
 * Tests the full simulate -> assemble ADDRESS_V2 -> sign -> submit flow. Simulation
 * returns legacy ADDRESS credentials; the ADDRESS_V2 credential arm is assembled
 * client-side so the round-trip exercises the address-bound V2 signing and submission
 * path. Submission succeeds only once the network runs Protocol 27.
 *
 * The test requires network access to the testnet RPC and is gated by $testOn.
 * It will only run when explicitly invoked via the integration suite.
 */
class P27AddressV2RoundTripTest extends TestCase
{
    const AUTH_CONTRACT_PATH = './../wasm/soroban_auth_contract.wasm';
    const TESTNET_SERVER_URL = "https://soroban-testnet.stellar.org";

    /**
     * Set to 'testnet' to run; any other value skips the test.
     *
     * This gate follows the same convention used throughout the integration suite.
     */
    private string $testOn = 'skip'; // Change to 'testnet' to enable

    private Network $network;
    private SorobanServer $server;

    public function setUp(): void
    {
        error_reporting(E_ALL);
        if ($this->testOn === 'testnet') {
            $this->network = Network::testnet();
            $this->server  = new SorobanServer(self::TESTNET_SERVER_URL);
            $this->server->setLogger(new PrintLogger());
        }
    }

    /**
     * ADDRESS_V2 round-trip: simulate, assemble the ADDRESS_V2 arm client-side, sign using
     * the address-bound preimage, and submit.
     *
     * Simulation returns a legacy ADDRESS entry for the invoker (the invoker differs from the
     * transaction source). That entry is converted to the ADDRESS_V2 arm before signing, and a
     * hard assertion guarantees a V2 entry is present so the V2 path is genuinely exercised.
     *
     * @throws Exception
     * @throws GuzzleException
     */
    public function testAddressV2SimulateSignRoundTrip(): void
    {
        if ($this->testOn !== 'testnet') {
            $this->markTestSkipped(
                'P27 ADDRESS_V2 integration test requires testnet access. '
                . 'Set $testOn = "testnet" to enable. '
                . 'Submission succeeds only once the network runs Protocol 27.'
            );
        }

        // Fund two accounts: submitter and invoker.
        $submitterKeyPair = KeyPair::random();
        $invokerKeyPair   = KeyPair::random();
        $submitterId      = $submitterKeyPair->getAccountId();
        $invokerId        = $invokerKeyPair->getAccountId();

        FriendBot::fundTestAccount($submitterId);
        FriendBot::fundTestAccount($invokerId);
        sleep(5);

        // Deploy the auth contract.
        $contractId = $this->deployContract($this->server, self::AUTH_CONTRACT_PATH, $submitterKeyPair);

        // Build the invoke transaction.
        $invokerAddress = Address::fromAccountId($invokerId);
        $args = [$invokerAddress->toXdrSCVal(), XdrSCVal::forU32(1)];

        $invokeHostFunction = new InvokeContractHostFunction($contractId, 'increment', $args);
        $op = (new InvokeHostFunctionOperationBuilder($invokeHostFunction))->build();

        $submitterAccount = $this->server->getAccount($submitterId);
        $this->assertNotNull($submitterAccount);
        $transaction = (new TransactionBuilder($submitterAccount))->addOperation($op)->build();

        $request = new SimulateTransactionRequest(transaction: $transaction);
        $simulateResponse = $this->server->simulateTransaction($request);

        $this->assertNull($simulateResponse->error);
        $this->assertNotNull($simulateResponse->results);
        $this->assertNotNull($simulateResponse->transactionData);
        $this->assertNotNull($simulateResponse->minResourceFee);

        $transactionData = $simulateResponse->getTransactionData();
        $transaction->setSorobanTransactionData($transactionData);
        $transaction->addResourceFee($simulateResponse->minResourceFee);

        $auth = $simulateResponse->getSorobanAuth();
        $this->assertNotNull($auth);

        // Simulation returns a legacy ADDRESS entry for the invoker. Assemble the ADDRESS_V2
        // arm client-side so the round-trip exercises the address-bound V2 signing path.
        foreach ($auth as $entry) {
            if ($entry->credentials->credentialType === XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS) {
                $inner = $entry->credentials->getAddressCredentials();
                if ($inner !== null && $inner->address->accountId === $invokerId) {
                    $entry->credentials = SorobanCredentials::forAddressCredentialsV2($inner);
                }
            }
        }

        $hasV2 = false;
        foreach ($auth as $entry) {
            if ($entry->credentials->credentialType === XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_V2) {
                $hasV2 = true;
            }
        }
        $this->assertTrue($hasV2, 'Expected an ADDRESS_V2 auth entry after the client-side rewrite');

        $latestLedgerResponse = $this->server->getLatestLedger();
        $this->assertNotNull($latestLedgerResponse->sequence);

        // Sign each entry; sign() selects the correct preimage based on the credential arm,
        // so the V2 entry uses the address-bound preimage.
        foreach ($auth as $entry) {
            $this->assertInstanceOf(SorobanAuthorizationEntry::class, $entry);

            $credType = $entry->credentials->credentialType;

            // The arm must be one of the three address-based types (not SOURCE_ACCOUNT).
            $this->assertContains(
                $credType,
                [
                    XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS,
                    XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_V2,
                    XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_WITH_DELEGATES,
                ],
                'Simulate must return an address-based credential arm',
            );

            // Set the expiration ledger via the arm-preserving getAddressCredentials() helper
            // and sign. The sign() method selects the correct preimage automatically based on
            // the credential arm, so V2 entries use the address-bound preimage.
            $innerCreds = $entry->credentials->getAddressCredentials();
            $this->assertNotNull($innerCreds);
            $innerCreds->signatureExpirationLedger = $latestLedgerResponse->sequence + 100;
            $entry->credentials->writeBackAddressCredentials($innerCreds);

            $entry->sign($invokerKeyPair, $this->network);

            // Confirm that the arm was preserved after signing.
            $this->assertSame(
                $credType,
                $entry->credentials->credentialType,
                'Credential arm must be preserved after sign()',
            );

            // Confirm that a signature was written.
            $signedCreds = $entry->credentials->getAddressCredentials();
            $this->assertNotNull($signedCreds);
            $this->assertNotNull(
                $signedCreds->signature->vec,
                'Signature must be written after sign()',
            );
            $this->assertCount(1, $signedCreds->signature->vec);
        }

        $transaction->setSorobanAuth($auth);
        $transaction->sign($submitterKeyPair, $this->network);

        $sendResponse = $this->server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);

        $statusResponse = $this->pollStatus($this->server, $sendResponse->hash);
        $this->assertNotNull($statusResponse->getResultValue());

        $resVal = $statusResponse->getResultValue();
        $this->assertNotNull($resVal);
        $this->assertEquals(1, $resVal->u32);
    }

    private function pollStatus(SorobanServer $server, string $transactionId): GetTransactionResponse
    {
        $statusResponse = $server->getTransaction($transactionId);
        $count = 15;
        while ($count-- > 0 && $statusResponse->status === GetTransactionResponse::STATUS_NOT_FOUND) {
            sleep(3);
            $statusResponse = $server->getTransaction($transactionId);
        }
        return $statusResponse;
    }

    private function deployContract(SorobanServer $server, string $wasmPath, KeyPair $accountKeyPair): string
    {
        // Load WASM.
        $wasm = file_get_contents($wasmPath);
        if ($wasm === false) {
            throw new Exception("Could not load WASM from $wasmPath");
        }

        $accountId = $accountKeyPair->getAccountId();
        $account   = $server->getAccount($accountId);
        $this->assertNotNull($account);

        // Upload WASM.
        $uploadHostFunction = new \Soneso\StellarSDK\UploadContractWasmHostFunction($wasm);
        $op = (new \Soneso\StellarSDK\InvokeHostFunctionOperationBuilder($uploadHostFunction))->build();

        $transaction = (new TransactionBuilder($account))->addOperation($op)->build();

        $uploadRequest       = new SimulateTransactionRequest($transaction);
        $simulateResponse    = $server->simulateTransaction($uploadRequest);

        $this->assertNull($simulateResponse->error);
        $transactionData = $simulateResponse->getTransactionData();
        $transaction->setSorobanTransactionData($transactionData);
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->sign($accountKeyPair, $this->network);

        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);

        $statusResponse = $this->pollStatus($server, $sendResponse->hash);
        $this->assertNotNull($statusResponse);

        $wasmId = $statusResponse->getResultValue()?->bytes?->getValue();
        $this->assertNotNull($wasmId);

        // Re-fetch account (sequence updated).
        $account = $server->getAccount($accountId);
        $this->assertNotNull($account);

        // Create contract.
        $createHostFunction = new \Soneso\StellarSDK\CreateContractHostFunction(
            new \Soneso\StellarSDK\Soroban\Address(
                \Soneso\StellarSDK\Soroban\Address::TYPE_ACCOUNT,
                accountId: $accountId,
            ),
            bin2hex($wasmId),
        );
        $op2 = (new \Soneso\StellarSDK\InvokeHostFunctionOperationBuilder($createHostFunction))->build();

        $transaction2 = (new TransactionBuilder($account))->addOperation($op2)->build();

        $createRequest     = new SimulateTransactionRequest($transaction2);
        $simulateResponse2 = $server->simulateTransaction($createRequest);

        $this->assertNull($simulateResponse2->error);
        $transactionData2 = $simulateResponse2->getTransactionData();
        $transaction2->setSorobanTransactionData($transactionData2);
        $transaction2->addResourceFee($simulateResponse2->minResourceFee);
        $transaction2->setSorobanAuth($simulateResponse2->getSorobanAuth());
        $transaction2->sign($accountKeyPair, $this->network);

        $sendResponse2 = $server->sendTransaction($transaction2);
        $this->assertNull($sendResponse2->error);

        $statusResponse2 = $this->pollStatus($server, $sendResponse2->hash);
        $this->assertNotNull($statusResponse2);

        $contractIdBytes = $statusResponse2->getResultValue()?->address?->contractId;
        $this->assertNotNull($contractIdBytes);

        return \Soneso\StellarSDK\Crypto\StrKey::encodeContractIdHex($contractIdBytes);
    }
}
