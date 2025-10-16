<?php  declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\CreateContractWithConstructorHostFunction;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\CreateContractHostFunction;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\Requests\SimulateTransactionRequest;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\SendTransactionResponse;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\UploadContractWasmHostFunction;
use Soneso\StellarSDK\Util\FriendBot;
use Soneso\StellarSDK\Util\FuturenetFriendBot;
use Soneso\StellarSDK\Xdr\XdrInt128Parts;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValType;
use function PHPUnit\Framework\assertNotNull;

// See https://developers.stellar.org/docs/smart-contracts/example-contracts/atomic-swap
// See: https://developers.stellar.org/docs/learn/smart-contract-internals/authorization
class SorobanAtomicSwapTest extends TestCase
{

    const SWAP_CONTRACT_PATH = './wasm/soroban_atomic_swap_contract.wasm';
    const TOKEN_CONTRACT_PATH = './wasm/soroban_token_contract.wasm';

    const TESTNET_SERVER_URL = "https://soroban-testnet.stellar.org";
    const FUTURENET_SERVER_URL = "https://rpc-futurenet.stellar.org";

    private string $testOn = 'testnet'; // 'futurenet'
    private Network $network;
    private SorobanServer $server;

    public function setUp(): void
    {
        // Turn on error reporting
        error_reporting(E_ALL);
        if ($this->testOn === 'testnet') {
            $this->network = Network::testnet();
            $this->server = new SorobanServer(self::TESTNET_SERVER_URL);
            $this->server->enableLogging = true;
        } elseif ($this->testOn === 'futurenet') {
            $this->network = Network::futurenet();
            $this->server = new SorobanServer(self::FUTURENET_SERVER_URL);
            $this->server->enableLogging = true;
        }
        sleep(5);
    }

    public function testAtomicSwap() {


        $adminKeyPair = KeyPair::random();
        $adminId = $adminKeyPair->getAccountId();
        $aliceKeyPair = KeyPair::random();
        $aliceId = $aliceKeyPair->getAccountId();
        $bobKeyPair = KeyPair::random();
        $bobId = $bobKeyPair->getAccountId();

        if ($this->testOn === 'testnet') {
            FriendBot::fundTestAccount($adminId);
            FriendBot::fundTestAccount($aliceId);
            FriendBot::fundTestAccount($bobId);
        } elseif ($this->testOn === 'futurenet') {
            FuturenetFriendBot::fundTestAccount($adminId);
            FuturenetFriendBot::fundTestAccount($aliceId);
            FuturenetFriendBot::fundTestAccount($bobId);
        }

        sleep(5);

        print("admin: " . $adminKeyPair->getSecretSeed() .  " : " . $adminKeyPair->getAccountId(). PHP_EOL);
        print("alice: " . $aliceKeyPair->getSecretSeed() .  " : " . $aliceKeyPair->getAccountId(). PHP_EOL);
        print("bob: " . $bobKeyPair->getSecretSeed() .  " : " . $bobKeyPair->getAccountId(). PHP_EOL);

        $atomicSwapContractId = $this->deployContract($this->server,self::SWAP_CONTRACT_PATH, $adminKeyPair);
        print("atomic swap cid: " . StrKey::encodeContractIdHex($atomicSwapContractId) . PHP_EOL);

        $contractInfo = $this->server->loadContractInfoForContractId($atomicSwapContractId);
        $this->assertNotNull($contractInfo);
        $this->assertTrue(count($contractInfo->specEntries) > 0);
        $this->assertTrue(count($contractInfo->metaEntries) > 0);

        $adminAddress = Address::fromAccountId($adminKeyPair->getAccountId())->toXdrSCVal();
        $tokenName = XdrSCVal::forString("TokenA");
        $tokenSymbol = XdrSCVal::forString("TokenA");
        $decimal = XdrSCVal::forU32(8);

        $tokenAContractId = $this->deployContract($this->server,self::TOKEN_CONTRACT_PATH, $adminKeyPair,
            constructorArgs: [$adminAddress, $decimal, $tokenName, $tokenSymbol]);
        print("token a cid: " . StrKey::encodeContractIdHex($tokenAContractId) . PHP_EOL);

        $tokenName = XdrSCVal::forString("TokenB");
        $tokenSymbol = XdrSCVal::forString("TokenB");
        $tokenBContractId = $this->deployContract($this->server,self::TOKEN_CONTRACT_PATH, $adminKeyPair,
            constructorArgs: [$adminAddress, $decimal, $tokenName, $tokenSymbol]);
        print("token b cid: " . StrKey::encodeContractIdHex($tokenBContractId) . PHP_EOL);

        $contractInfo = $this->server->loadContractInfoForContractId($tokenBContractId);
        $this->assertNotNull($contractInfo);
        $this->assertTrue(count($contractInfo->specEntries) > 0);
        $this->assertTrue(count($contractInfo->metaEntries) > 0);

        $this->mint($this->server, $adminKeyPair, $tokenAContractId, $aliceId, 10000000000000);
        $this->mint($this->server, $adminKeyPair, $tokenBContractId, $bobId, 10000000000000);

        $aliceTokenABalance = $this->balance($this->server, $adminKeyPair, $tokenAContractId, $aliceId);
        $this->assertEquals(10000000000000, $aliceTokenABalance);

        $bobTokenBBalance = $this->balance($this->server, $adminKeyPair, $tokenBContractId, $bobId);
        $this->assertEquals(10000000000000, $bobTokenBBalance);


        $addressAlice = Address::fromAccountId($aliceId)->toXdrSCVal();
        $addressBob = Address::fromAccountId($bobId)->toXdrSCVal();

        $amountA = XdrSCVal::forI128(new XdrInt128Parts(0,1000));
        $minBForA = XdrSCVal::forI128(new XdrInt128Parts(0,4500));

        $amountB = XdrSCVal::forI128(new XdrInt128Parts(0,5000));
        $minAForB = XdrSCVal::forI128(new XdrInt128Parts(0,950));

        $swapFunctionName = "swap";

        $invokeContract = [
            $addressAlice,
            $addressBob,
            Address::fromContractId($tokenAContractId)->toXdrSCVal(),
            Address::fromContractId($tokenBContractId)->toXdrSCVal(),
            $amountA,
            $minBForA,
            $amountB,
            $minAForB
         ];

        $invokeContractHostFunction = new InvokeContractHostFunction($atomicSwapContractId, $swapFunctionName, $invokeContract);
        $builder = new InvokeHostFunctionOperationBuilder($invokeContractHostFunction);
        $op = $builder->build();

        $source = $this->server->getAccount($adminId);
        assertNotNull($source);
        $transaction = (new TransactionBuilder($source))->addOperation($op)->build();

        $request = new SimulateTransactionRequest($transaction);
        $simulateResponse = $this->server->simulateTransaction($request);

        $transactionData = $simulateResponse->getTransactionData();

        $transaction->setSorobanTransactionData($transactionData);
        $transaction->addResourceFee($simulateResponse->minResourceFee);

        // sign auth
        $auth = $simulateResponse->getSorobanAuth();
        $this->assertNotNull($auth);

        $latestLedgerResponse = $this->server->getLatestLedger();
        $this->assertNotNull($latestLedgerResponse->sequence);
        foreach ($auth as $a) {
            if ($a instanceof  SorobanAuthorizationEntry) {
                $this->assertNotNull($a->credentials->addressCredentials);
                // increase signature expiration ledger
                $a->credentials->addressCredentials->signatureExpirationLedger = $latestLedgerResponse->sequence + 10;
                // sign
                if ($a->credentials->addressCredentials->address->accountId == $aliceId) {
                    $a->sign($aliceKeyPair, $this->network);
                }
                if ($a->credentials->addressCredentials->address->accountId == $bobId) {
                    $a->sign($bobKeyPair, $this->network);
                }
            } else {
                self::fail("invalid auth");
            }
        }
        $transaction->setSorobanAuth($auth);

        // sign transaction
        $transaction->sign($adminKeyPair, $this->network);

        // check transaction xdr encoding back and forth
        $transctionEnvelopeXdr = $transaction->toEnvelopeXdrBase64();
        $this->assertEquals($transctionEnvelopeXdr, Transaction::fromEnvelopeBase64XdrString($transctionEnvelopeXdr)->toEnvelopeXdrBase64());

        // send the transaction
        $sendResponse = $this->server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($this->server, $sendResponse->hash);
        $this->assertNotNull($statusResponse);
        $this->assertEquals(GetTransactionResponse::STATUS_SUCCESS, $statusResponse->status);
        $this->assertNotNull($statusResponse->getResultValue());
        $result = $statusResponse->getResultValue();
        $this->assertEquals(XdrSCValType::SCV_VOID, $result->type->value);
    }

    private function deployContract(SorobanServer $server, String $pathToCode, KeyPair $submitterKp, ?array $constructorArgs = null) : String {
        sleep(5);

        // upload contract wasm
        $contractCode = file_get_contents($pathToCode, false);

        $uploadContractHostFunction = new UploadContractWasmHostFunction($contractCode);
        $builder = new InvokeHostFunctionOperationBuilder($uploadContractHostFunction);
        $op = $builder->build();

        sleep(5);
        $submitterId = $submitterKp->getAccountId();
        $account = $this->server->getAccount($submitterId);
        assertNotNull($account);
        $transaction = (new TransactionBuilder($account))->addOperation($op)->build();

        // simulate first to get the transaction data and resource fee
        $request = new SimulateTransactionRequest($transaction);
        $simulateResponse = $server->simulateTransaction($request);


        // set the transaction data + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->sign($submitterKp, $this->network);

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->hash);
        $this->assertNotNull($statusResponse);
        $wasmId = $statusResponse->getWasmId();
        $this->assertNotNull($wasmId);

        // create contract
        if ($constructorArgs != null) {
            $createContractHostFunction = new CreateContractWithConstructorHostFunction(Address::fromAccountId($submitterId), $wasmId, $constructorArgs);
        } else {
            $createContractHostFunction = new CreateContractHostFunction(Address::fromAccountId($submitterId), $wasmId);
        }

        $builder = new InvokeHostFunctionOperationBuilder($createContractHostFunction);
        $op = $builder->build();

        sleep(5);
        $account = $this->server->getAccount($submitterId);
        assertNotNull($account);
        $transaction = (new TransactionBuilder($account))
            ->addOperation($op)->build();

        // simulate first to get the transaction data and resource fee
        $request = new SimulateTransactionRequest($transaction);
        $simulateResponse = $server->simulateTransaction($request);

        // set the transaction data + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->setSorobanAuth($simulateResponse->getSorobanAuth());
        $transaction->sign($submitterKp, $this->network);

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->hash);
        $this->assertNotNull($statusResponse);
        $contractId = $statusResponse->getCreatedContractId();
        $this->assertNotNull($contractId);
        return $contractId;
    }

    private function mint(SorobanServer $server, Keypair $submitterKp, String $contractId, String $toAccountId, int $amount) : void
    {
        sleep(5);

        // see https://soroban.stellar.org/docs/reference/interfaces/token-interface
        $submitterId = $submitterKp->getAccountId();


        $adminAddress = Address::fromAccountId($submitterId)->toXdrSCVal();
        $toAddress = Address::fromAccountId($toAccountId)->toXdrSCVal();
        $amountValue = XdrSCVal::forI128(new XdrInt128Parts(0,$amount));
        $functionName = "mint";

        $args = [$toAddress, $amountValue];

        $invokeContractHostFunction = new InvokeContractHostFunction($contractId, $functionName, $args);
        $builder = new InvokeHostFunctionOperationBuilder($invokeContractHostFunction);
        $op = $builder->build();

        // reload account for sequence number
        $account = $this->server->getAccount($submitterId);
        assertNotNull($account);
        $transaction = (new TransactionBuilder($account))->addOperation($op)->build();

        $request = new SimulateTransactionRequest($transaction);
        $simulateResponse = $server->simulateTransaction($request);
        // set the transaction data  + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->setSorobanAuth($simulateResponse->getSorobanAuth());
        $transaction->sign($submitterKp, $this->network);


        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->hash);
        $this->assertEquals(GetTransactionResponse::STATUS_SUCCESS, $statusResponse->status);
    }

    private function balance(SorobanServer $server, Keypair $submitterKp, String $contractId, String $accountId) : int
    {
        sleep(5);

        // see https://soroban.stellar.org/docs/reference/interfaces/token-interface
        $submitterId = $submitterKp->getAccountId();

        $address = Address::fromAccountId($accountId)->toXdrSCVal();
        $functionName = "balance";

        $args = [$address];

        $invokeContractHostFunction = new InvokeContractHostFunction($contractId, $functionName, $args);
        $builder = new InvokeHostFunctionOperationBuilder($invokeContractHostFunction);
        $op = $builder->build();

        $account = $this->server->getAccount($submitterId);
        assertNotNull($account);
        $transaction = (new TransactionBuilder($account))
            ->addOperation($op)->build();

        $request = new SimulateTransactionRequest($transaction);
        $simulateResponse = $server->simulateTransaction($request);
        // set the transaction data  + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->sign($submitterKp, $this->network);

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->hash);
        $this->assertEquals(GetTransactionResponse::STATUS_SUCCESS, $statusResponse->status);
        $this->assertNotNull($statusResponse->getResultValue());
        $resultVal = $statusResponse->getResultValue();
        $this->assertNotNull($resultVal->getI128());
        return $resultVal->getI128()->lo;
    }

    private function pollStatus(SorobanServer $server, string $transactionId) : ?GetTransactionResponse {
        $statusResponse = null;
        $status = GetTransactionResponse::STATUS_NOT_FOUND;
        $count = 15;
        while ($status == GetTransactionResponse::STATUS_NOT_FOUND) {
            sleep(3);
            $statusResponse = $server->getTransaction($transactionId);
            $this->assertNull($statusResponse->error);
            $this->assertNotNull($statusResponse->status);
            $status = $statusResponse->status;
            if ($status == GetTransactionResponse::STATUS_FAILED) {
                $this->assertNotNull($statusResponse->resultXdr);
            } else if ($status == GetTransactionResponse::STATUS_SUCCESS) {
                $this->assertNotNull($statusResponse->resultXdr);
                $this->assertNotNull($statusResponse->resultMetaXdr);
            }
            $count -= 1;
            $this->assertGreaterThan(0, $count);
        }
        return $statusResponse;
    }
}