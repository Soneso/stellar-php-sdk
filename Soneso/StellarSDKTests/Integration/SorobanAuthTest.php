<?php  declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Integration;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\CreateContractWithConstructorHostFunction;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\Requests\SimulateTransactionRequest;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;
use Soneso\StellarSDK\Soroban\Responses\GetHealthResponse;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\SendTransactionResponse;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\UploadContractWasmHostFunction;
use Soneso\StellarSDK\Util\FriendBot;
use Soneso\StellarSDK\Util\FuturenetFriendBot;
use Soneso\StellarSDKTests\PrintLogger;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrTransactionMeta;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotNull;

// see: https://developers.stellar.org/docs/learn/smart-contract-internals/authorization
// see: https://developers.stellar.org/docs/smart-contracts/example-contracts/auth
class SorobanAuthTest extends TestCase
{
    const AUTH_CONTRACT_PATH = './../wasm/soroban_auth_contract.wasm';

    const TESTNET_SERVER_URL = "https://soroban-testnet.stellar.org";
    const FUTURENET_SERVER_URL = "https://rpc-futurenet.stellar.org";

    private string $testOn = 'testnet'; // futurenet
    private Network $network;
    private SorobanServer $server;
    private StellarSDK $sdk;

    public function setUp(): void
    {
        // Turn on error reporting
        error_reporting(E_ALL);
        if ($this->testOn === 'testnet') {
            $this->network = Network::testnet();
            $this->server = new SorobanServer(self::TESTNET_SERVER_URL);
            $this->server->setLogger(new PrintLogger());
            $this->sdk = StellarSDK::getTestNetInstance();
        } elseif ($this->testOn === 'futurenet') {
            $this->network = Network::futurenet();
            $this->server = new SorobanServer(self::FUTURENET_SERVER_URL);
            $this->server->setLogger(new PrintLogger());
            $this->sdk = StellarSDK::getFutureNetInstance();
        }
    }

    public function testAuthInvoker(): void
    {
        // submitter and invoker use are the same
        // no need to sign auth

        $invokerKeyPair = KeyPair::random();
        $invokerId = $invokerKeyPair->getAccountId();

        // get health
        $getHealthResponse = $this->server->getHealth();
        $this->assertEquals(GetHealthResponse::HEALTHY, $getHealthResponse->status);

        if ($this->testOn == 'testnet') {
            FriendBot::fundTestAccount($invokerId);
        } elseif($this->testOn == 'futurenet') {
            FuturenetFriendBot::fundTestAccount($invokerId);
        }

        $contractId = $this->deployContract($this->server, self::AUTH_CONTRACT_PATH, $invokerKeyPair);

        $contractInfo = $this->server->loadContractInfoForContractId($contractId);
        $this->assertNotNull($contractInfo);
        $this->assertTrue(count($contractInfo->specEntries) > 0);
        $this->assertTrue(count($contractInfo->metaEntries) > 0);

        // submitter and invoker use are the same
        // no need to sign auth

        $functionName = "increment";

        $invokerAddress = new Address(Address::TYPE_ACCOUNT, accountId: $invokerId);
        $args = [$invokerAddress->toXdrSCVal(), XdrSCVal::forU32(3)];

        // simulate first to get the transaction data and resource fee
        $invokeHostFunction = new InvokeContractHostFunction($contractId, $functionName, $args);
        $builder = new InvokeHostFunctionOperationBuilder($invokeHostFunction);
        $op = $builder->build();

        sleep(5);
        $invokerAccount = $this->server->getAccount($invokerId);
        assertNotNull($invokerAccount);
        $transaction = (new TransactionBuilder($invokerAccount))
            ->addOperation($op)->build();

        $request = new SimulateTransactionRequest($transaction);
        $simulateResponse = $this->server->simulateTransaction($request);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);
        $this->assertNotNull($simulateResponse->results);
        $this->assertNotNull($simulateResponse->transactionData);
        $this->assertNotNull($simulateResponse->minResourceFee);


        $transactionData = $simulateResponse->getTransactionData();

        // set the transaction data + fee and sign
        $transaction->setSorobanTransactionData($transactionData);
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->setSorobanAuth($simulateResponse->getSorobanAuth());
        // sign auth
        $transaction->sign($invokerKeyPair, $this->network);

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
        $this->assertNotNull($statusResponse->getResultValue());

        $resVal = $statusResponse->getResultValue();
        assertNotNull($resVal);
        assertEquals(3, $resVal->u32);

        sleep(3);
        // check horizon response decoding.
        $transactionResponse = $this->sdk->requestTransaction($sendResponse->hash);
        $this->assertEquals(1, $transactionResponse->getOperationCount());
        $this->assertEquals($transctionEnvelopeXdr, $transactionResponse->getEnvelopeXdr()->toBase64Xdr());
        $meta = $transactionResponse->getResultMetaXdrBase64();

        // parsing meta is working
        if ($meta !== null) {
            $metaXdr = XdrTransactionMeta::fromBase64Xdr($meta);
            $this->assertEquals($meta, $metaXdr->toBase64Xdr());
        }
    }

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function testAuthDifferentSubmitter(): void
    {

        // submitter and invoker use are NOT the same
        // we need to sign auth

        $submitterKeyPair = KeyPair::random();
        $submitterId = $submitterKeyPair->getAccountId();
        $invokerKeyPair = KeyPair::random();
        $invokerId = $invokerKeyPair->getAccountId();

        if ($this->testOn == 'testnet') {
            FriendBot::fundTestAccount($submitterId);
            FriendBot::fundTestAccount($invokerId);
        } elseif($this->testOn == 'futurenet') {
            FuturenetFriendBot::fundTestAccount($submitterId);
            FuturenetFriendBot::fundTestAccount($invokerId);
        }
        sleep(5);

        $contractId = $this->deployContract($this->server, self::AUTH_CONTRACT_PATH, $submitterKeyPair);

        // invoke contract
        // submitter and invoker use are NOT the same
        // we need to sign auth

        $invokerAddress = Address::fromAccountId($invokerId);

        $functionName = "increment";
        $args = [$invokerAddress->toXdrSCVal(), XdrSCVal::forU32(4)];

        // simulate first to get the transaction data and resource fee + auth

        $invokeHostFunction = new InvokeContractHostFunction($contractId, $functionName, $args);
        $builder = new InvokeHostFunctionOperationBuilder($invokeHostFunction);
        $op = $builder->build();

        $submitterAccount = $this->server->getAccount($submitterId);
        assertNotNull($submitterAccount);
        $transaction = (new TransactionBuilder($submitterAccount))
            ->addOperation($op)->build();

        $request = new SimulateTransactionRequest($transaction);
        $simulateResponse = $this->server->simulateTransaction($request);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);
        $this->assertNotNull($simulateResponse->results);
        $this->assertNotNull($simulateResponse->transactionData);
        $this->assertNotNull($simulateResponse->minResourceFee);

        $transactionData = $simulateResponse->getTransactionData();

        // set the transaction data + fee and sign
        $transaction->setSorobanTransactionData($transactionData);
        $transaction->addResourceFee($simulateResponse->minResourceFee);

        // submitter and invoker use are NOT the same
        // we need to sign auth

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
                $a->sign($invokerKeyPair, $this->network);
            } else {
                self::fail("invalid auth");
            }
        }
        $transaction->setSorobanAuth($auth);
        $transaction->sign($submitterKeyPair, $this->network);

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
        $this->assertNotNull($statusResponse->getResultValue());

        $resVal = $statusResponse->getResultValue();
        assertNotNull($resVal);
        assertEquals(4, $resVal->u32);

        sleep(5);
        // check horizon response decoding.
        $transactionResponse = $this->sdk->requestTransaction($sendResponse->hash);
        $this->assertEquals(1, $transactionResponse->getOperationCount());
        $this->assertEquals($transctionEnvelopeXdr, $transactionResponse->getEnvelopeXdr()->toBase64Xdr());
        $meta = $transactionResponse->getResultMetaXdrBase64();

        // parsing meta is working
        if ($meta !== null) {
            $metaXdr = XdrTransactionMeta::fromBase64Xdr($meta);
            $this->assertEquals($meta, $metaXdr->toBase64Xdr());
        }
    }


    private function pollStatus(SorobanServer $server, string $transactionId) : ?GetTransactionResponse {
        $statusResponse = null;
        $status = GetTransactionResponse::STATUS_NOT_FOUND;
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
        }
        return $statusResponse;
    }

    private function deployContract(SorobanServer $server, String $pathToCode, KeyPair $submitterKp) : String {
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
        $createContractHostFunction = new CreateContractWithConstructorHostFunction(Address::fromAccountId($submitterId), $wasmId, []);
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

}