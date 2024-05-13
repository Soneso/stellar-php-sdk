<?php  declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\ExtendFootprintTTLOperationBuilder;
use Soneso\StellarSDK\CreateContractHostFunction;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\RestoreFootprintOperationBuilder;
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
use Soneso\StellarSDK\Xdr\XdrExtensionPoint;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryType;
use Soneso\StellarSDK\Xdr\XdrLedgerFootprint;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyContractCode;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSorobanResources;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionData;
use Soneso\StellarSDK\Xdr\XdrTransactionMeta;

// see: https://soroban.stellar.org/docs/fundamentals-and-concepts/authorization
// see: https://soroban.stellar.org/docs/basic-tutorials/auth
class SorobanAuthTest extends TestCase
{
    const AUTH_CONTRACT_PATH = './wasm/soroban_auth_contract.wasm';

    const TESTNET_SERVER_URL = "https://soroban-testnet.stellar.org";
    const FUTURENET_SERVER_URL = "https://rpc-futurenet.stellar.org";

    private string $testOn = 'testnet'; // 'futurenet'
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
            $this->server->enableLogging = true;
            $this->sdk = StellarSDK::getTestNetInstance();
        } elseif ($this->testOn === 'futurenet') {
            $this->network = Network::futurenet();
            $this->server = new SorobanServer(self::FUTURENET_SERVER_URL);
            $this->server->enableLogging = true;
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
        $invokerAccount = $this->sdk->requestAccount($invokerId);
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

        // user friendly
        $resVal = $statusResponse->getResultValue();
        $map = $resVal->getMap();
        if ($map != null && count($map) > 0) {
            foreach ($map as $entry) {
                print("{" . $entry->key->address->accountId->getAccountId() . ", " . strval($entry->val->u32) . "}".PHP_EOL);
            }
        }

        sleep(3);
        // check horizon response decoding.
        $transactionResponse = $this->sdk->requestTransaction($sendResponse->hash);
        $this->assertEquals(1, $transactionResponse->getOperationCount());
        $this->assertEquals($transctionEnvelopeXdr, $transactionResponse->getEnvelopeXdr()->toBase64Xdr());
        $meta = $transactionResponse->getResultMetaXdrBase64();

        // parsing meta is working
        $metaXdr = XdrTransactionMeta::fromBase64Xdr($meta);
        //$this->assertEquals($meta, $metaXdr->toBase64Xdr());

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
        $args = [$invokerAddress->toXdrSCVal(), XdrSCVal::forU32(3)];

        // simulate first to get the transaction data and resource fee + auth

        $invokeHostFunction = new InvokeContractHostFunction($contractId, $functionName, $args);
        $builder = new InvokeHostFunctionOperationBuilder($invokeHostFunction);
        $op = $builder->build();

        $submitterAccount = $this->sdk->requestAccount($submitterId);
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

        // user friendly
        $resVal = $statusResponse->getResultValue();
        $map = $resVal->getMap();
        if ($map != null && count($map) > 0) {
            foreach ($map as $entry) {
                print("{" . $entry->key->address->accountId->getAccountId() . ", " . strval($entry->val->u32) . "}".PHP_EOL);
            }
        }

        sleep(5);
        // check horizon response decoding.
        $transactionResponse = $this->sdk->requestTransaction($sendResponse->hash);
        $this->assertEquals(1, $transactionResponse->getOperationCount());
        $this->assertEquals($transctionEnvelopeXdr, $transactionResponse->getEnvelopeXdr()->toBase64Xdr());
        $meta = $transactionResponse->getResultMetaXdrBase64();

        // parsing meta is working
        $metaXdr = XdrTransactionMeta::fromBase64Xdr($meta);
        //$this->assertEquals($meta, $metaXdr->toBase64Xdr());
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

    private function restoreContractFootprint(SorobanServer $server, KeyPair $accountKeyPair, string $contractCodePath) : void {
        sleep(5);

        $contractCode = file_get_contents($contractCodePath, false);
        $uploadContractHostFunction = new UploadContractWasmHostFunction($contractCode);
        $op = (new InvokeHostFunctionOperationBuilder($uploadContractHostFunction))->build();

        $accountAId = $accountKeyPair->getAccountId();
        $getAccountResponse = $this->sdk->requestAccount($accountAId);
        $transaction = (new TransactionBuilder($getAccountResponse))
            ->addOperation($op)->build();

        $request = new SimulateTransactionRequest($transaction);
        $simulateResponse = $server->simulateTransaction($request);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);
        $this->assertNotNull($simulateResponse->getTransactionData());

        $transactionData = $simulateResponse->getTransactionData();
        $transactionData->resources->footprint->readWrite = $transactionData->resources->footprint->readWrite + $transactionData->resources->footprint->readOnly;
        $transactionData->resources->footprint->readOnly = array();

        $getAccountResponse = $this->sdk->requestAccount($accountAId);
        $restoreOp = (new RestoreFootprintOperationBuilder())->build();
        $transaction = (new TransactionBuilder($getAccountResponse))
            ->addOperation($restoreOp)->build();

        $transaction->setSorobanTransactionData($transactionData) ;
        $request = new SimulateTransactionRequest($transaction);
        $simulateResponse = $server->simulateTransaction($request);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);
        $this->assertNotNull($simulateResponse->getTransactionData());
        $this->assertNotNull($simulateResponse->getMinResourceFee());

        // set the transaction data + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->getMinResourceFee());
        $transaction->sign($accountKeyPair, $this->network);

        // check transaction xdr encoding back and forth
        $transctionEnvelopeXdr = $transaction->toEnvelopeXdrBase64();
        $this->assertEquals($transctionEnvelopeXdr,
            Transaction::fromEnvelopeBase64XdrString($transctionEnvelopeXdr)->toEnvelopeXdrBase64());

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNotNull($sendResponse->hash);
        $this->assertNotNull($sendResponse->status);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->hash);
        $this->assertNotNull($statusResponse);
        $this->assertEquals(GetTransactionResponse::STATUS_SUCCESS, $statusResponse->status);
    }

    private function bumpContractCodeFootprint(SorobanServer $server, KeyPair $accountKeyPair, string $wasmId, int $extendTo) : void {
        sleep(5);

        $builder = new ExtendFootprintTTLOperationBuilder($extendTo);
        $bumpOp = $builder->build();

        $accountAId = $accountKeyPair->getAccountId();
        $getAccountResponse = $this->sdk->requestAccount($accountAId);
        $transaction = (new TransactionBuilder($getAccountResponse))
            ->addOperation($bumpOp)->build();

        $readOnly = array();
        $readWrite = array();
        $codeKey = new XdrLedgerKey(XdrLedgerEntryType::CONTRACT_CODE());
        $codeKey->contractCode = new XdrLedgerKeyContractCode(hex2bin($wasmId));
        array_push($readOnly, $codeKey);

        $footprint = new XdrLedgerFootprint($readOnly, $readWrite);
        $resources = new XdrSorobanResources($footprint, 0,0,0);
        $transactionData = new XdrSorobanTransactionData(new XdrExtensionPoint(0), $resources, 0);

        $transaction->setSorobanTransactionData($transactionData) ;
        $request = new SimulateTransactionRequest($transaction);
        $simulateResponse = $server->simulateTransaction($request);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);
        $this->assertNotNull($simulateResponse->getTransactionData());
        $this->assertNotNull($simulateResponse->getMinResourceFee());

        // set the transaction data + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->getMinResourceFee());
        $transaction->sign($accountKeyPair, $this->network);

        // check transaction xdr encoding back and forth
        $transctionEnvelopeXdr = $transaction->toEnvelopeXdrBase64();
        $this->assertEquals($transctionEnvelopeXdr,
            Transaction::fromEnvelopeBase64XdrString($transctionEnvelopeXdr)->toEnvelopeXdrBase64());

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNotNull($sendResponse->hash);
        $this->assertNotNull($sendResponse->status);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->hash);
        $this->assertNotNull($statusResponse);
        $this->assertEquals(GetTransactionResponse::STATUS_SUCCESS, $statusResponse->status);
    }

    private function deployContract(SorobanServer $server, String $pathToCode, KeyPair $submitterKp) : String {
        sleep(5);

        $this->restoreContractFootprint($server, $submitterKp, $pathToCode);

        // upload contract wasm
        $contractCode = file_get_contents($pathToCode, false);

        $uploadContractHostFunction = new UploadContractWasmHostFunction($contractCode);
        $builder = new InvokeHostFunctionOperationBuilder($uploadContractHostFunction);
        $op = $builder->build();

        sleep(5);
        $submitterId = $submitterKp->getAccountId();
        $account = $this->sdk->requestAccount($submitterId);
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

        $this->bumpContractCodeFootprint($server, $submitterKp, $wasmId, 100000);

        // create contract
        $createContractHostFunction = new CreateContractHostFunction(Address::fromAccountId($submitterId), $wasmId);
        $builder = new InvokeHostFunctionOperationBuilder($createContractHostFunction);
        $op = $builder->build();

        sleep(5);
        $account = $this->sdk->requestAccount($submitterId);
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