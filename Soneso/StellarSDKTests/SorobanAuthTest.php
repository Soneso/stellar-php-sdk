<?php  declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\CreateContractHostFunction;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\AuthorizedInvocation;
use Soneso\StellarSDK\Soroban\ContractAuth;
use Soneso\StellarSDK\Soroban\Responses\GetHealthResponse;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\SendTransactionResponse;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\UploadContractWasmHostFunction;
use Soneso\StellarSDK\Util\FuturenetFriendBot;
use Soneso\StellarSDK\Xdr\XdrContractAuth;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrTransactionMeta;

class SorobanAuthTest extends TestCase
{

    public function testAuthInvoker(): void
    {
        // see https://soroban.stellar.org/docs/learn/authorization#transaction-invoker

        $server = new SorobanServer("https://rpc-futurenet.stellar.org:443");
        $server->enableLogging = true;
        $server->acknowledgeExperimental = true;
        $sdk = StellarSDK::getFutureNetInstance();

        $invokerKeyPair = KeyPair::random();
        $invokerId = $invokerKeyPair->getAccountId();

        // get health
        $getHealthResponse = $server->getHealth();
        $this->assertEquals(GetHealthResponse::HEALTHY, $getHealthResponse->status);

        FuturenetFriendBot::fundTestAccount($invokerId);
        sleep(5);

        $invokerAccount = $sdk->requestAccount($invokerId);

        // upload contract wasm
        $contractCode = file_get_contents('./wasm/auth.wasm', false);
        $uploadContractHostFunction = new UploadContractWasmHostFunction($contractCode);
        $builder = new InvokeHostFunctionOperationBuilder();
        $op = $builder->addFunction($uploadContractHostFunction)->build();

        $transaction = (new TransactionBuilder($invokerAccount))
            ->addOperation($op)->build();

        //print($transaction->toEnvelopeXdrBase64() . PHP_EOL);

        // simulate first to get the transaction data + fee
        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);

        // set the transaction data and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->sign($invokerKeyPair, Network::futurenet());

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->hash);
        $this->assertNotNull($statusResponse);
        $wasmId = $statusResponse->getWasmId();
        $this->assertNotNull($wasmId);
        //print("wasm id: " . $wasmId . PHP_EOL);

        // create contract
        sleep(5);

        $invokerAccount = $sdk->requestAccount($invokerId);
        $createContractHostFunction = new CreateContractHostFunction($wasmId);
        $builder = new InvokeHostFunctionOperationBuilder();
        $op = $builder->addFunction($createContractHostFunction)->build();

        $transaction = (new TransactionBuilder($invokerAccount))
            ->addOperation($op)->build();

        // simulate first to get the transaction data + fee
        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);

        // set the transaction data + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->sign($invokerKeyPair, Network::futurenet());

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->hash);
        $this->assertNotNull($statusResponse);
        $contractId = $statusResponse->getContractId();
        $this->assertNotNull($contractId);
        //print("contract id: " . $contractId . PHP_EOL);

        // invoke contract no auth needed
        // # If tx_submitter_kp and op_invoker_kp use are the same
        // so we should not need its address & nonce in contract auth and no need to sign
        // see https://discord.com/channels/897514728459468821/1078208197283807305
        // see https://soroban.stellar.org/docs/learn/authorization#transaction-invoker

        $functionName = "auth";
        //$functionName = "increment";

        $invokerAddress = new Address(Address::TYPE_ACCOUNT, accountId: $invokerId);
        $args = [$invokerAddress->toXdrSCVal(), XdrSCVal::forU32(3)];

        // we still need contract auth but we do not need to add the account and sign
        $authInvocation = new AuthorizedInvocation($contractId, $functionName, args: $args);
        $contractAuth = new ContractAuth($authInvocation);

        $invokeHostFunction = new InvokeContractHostFunction($contractId, $functionName, $args, auth: [$contractAuth]);

        sleep(5);

        // simulate first to get the transaction data and resource fee
        $invokerAccount = $sdk->requestAccount($invokerId);

        $builder = new InvokeHostFunctionOperationBuilder();
        $op = $builder->addFunction($invokeHostFunction)->build();

        $transaction = (new TransactionBuilder($invokerAccount))
            ->addOperation($op)->build();

        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);
        $this->assertNotNull($simulateResponse->results);
        $this->assertNotNull($simulateResponse->latestLedger);
        $this->assertEquals(1, $simulateResponse->results->count());
        $this->assertNotNull($simulateResponse->getTransactionData());
        $this->assertNotNull($simulateResponse->getFootprint());
        $this->assertGreaterThan(1, $simulateResponse->cost->cpuInsns);
        $this->assertGreaterThan(1, $simulateResponse->cost->memBytes);
        $this->assertGreaterThan(1, $simulateResponse->minResourceFee);
        /*print("Cost cpu: " . $simulateResponse->cost->cpuInsns . PHP_EOL);
        print("Cost mem: " . $simulateResponse->cost->memBytes . PHP_EOL);
        print("min res fee: " . $simulateResponse->minResourceFee . PHP_EOL);*/

        $authResult = $simulateResponse->getAuth();
        $this->assertNotNull($authResult);
        $this->assertCount(1, $authResult);

        $transactionData = $simulateResponse->getTransactionData();
        /*print("res instructions: " . $transactionData->resources->getInstructions() . PHP_EOL);
        print("res read bytes: " . $transactionData->resources->getReadBytes() . PHP_EOL);
        print("res write bytes: " . $transactionData->resources->getWriteBytes() . PHP_EOL);
        print("res extended meta bytes: " . $transactionData->resources->getExtendedMetaDataSizeBytes() . PHP_EOL);*/


        // set the transaction data + fee and sign
        $transaction->setSorobanTransactionData($transactionData);
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->sign($invokerKeyPair, Network::futurenet());

        // check transaction xdr encoding back and forth
        $transctionEnvelopeXdr = $transaction->toEnvelopeXdrBase64();
        $this->assertEquals($transctionEnvelopeXdr, Transaction::fromEnvelopeBase64XdrString($transctionEnvelopeXdr)->toEnvelopeXdrBase64());

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->hash);
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
        $transactionResponse = $sdk->requestTransaction($sendResponse->hash);
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
        $server = new SorobanServer("https://rpc-futurenet.stellar.org:443");
        $server->enableLogging = true;
        $server->acknowledgeExperimental = true;
        $sdk = StellarSDK::getFutureNetInstance();

        $submitterKeyPair = KeyPair::random();
        $submitterId = $submitterKeyPair->getAccountId();
        $invokerKeyPair = KeyPair::random();
        $invokerId = $invokerKeyPair->getAccountId();

        // get health
        $getHealthResponse = $server->getHealth();
        $this->assertEquals(GetHealthResponse::HEALTHY, $getHealthResponse->status);

        FuturenetFriendBot::fundTestAccount($submitterId);
        FuturenetFriendBot::fundTestAccount($invokerId);
        sleep(5);

        $getAccountResponse = $sdk->requestAccount($submitterId);
        $this->assertEquals($submitterId, $getAccountResponse->getAccountId());

        // upload contract wasm
        $contractCode = file_get_contents('./wasm/auth.wasm', false);
        $uploadContractHostFunction = new UploadContractWasmHostFunction($contractCode);
        $builder = new InvokeHostFunctionOperationBuilder();
        $op = $builder->addFunction($uploadContractHostFunction)->build();

        $transaction = (new TransactionBuilder($getAccountResponse))
            ->addOperation($op)->build();

        //print($transaction->toEnvelopeXdrBase64() . PHP_EOL);

        // simulate first to get the transaction data + fee
        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);

        // set the transaction data and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->sign($submitterKeyPair, Network::futurenet());

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->hash);
        $this->assertNotNull($statusResponse);
        $wasmId = $statusResponse->getWasmId();
        $this->assertNotNull($wasmId);
        //print("wasm id: " . $wasmId . PHP_EOL);

        // create contract
        sleep(5);
        $submitterAccount = $sdk->requestAccount($submitterId);
        $createContractHostFunction = new CreateContractHostFunction($wasmId);
        $builder = new InvokeHostFunctionOperationBuilder();
        $op = $builder->addFunction($createContractHostFunction)->build();

        $transaction = (new TransactionBuilder($submitterAccount))
            ->addOperation($op)->build();

        // simulate first to get the transaction data + fee
        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);

        // set the transaction data + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->sign($submitterKeyPair, Network::futurenet());

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->hash);
        $this->assertNotNull($statusResponse);
        $contractId = $statusResponse->getContractId();
        $this->assertNotNull($contractId);
        //print("contract id: " . $contractId . PHP_EOL);

        // invoke contract
        // # If tx_submitter_kp and op_invoker_kp use the same account, the submission will fail
        // because in that case we do not need address, nonce and signature in auth or we have to change the footprint
        // See https://discord.com/channels/897514728459468821/1078208197283807305

        $invokerAddress = Address::fromAccountId($invokerId);
        $nonce = $server->getNonce($invokerId, $contractId);

        $functionName = "auth";
        $args = [$invokerAddress->toXdrSCVal(), XdrSCVal::forU32(3)];

        $authInvocation = new AuthorizedInvocation($contractId, $functionName, args: $args);

        $contractAuth = new ContractAuth($authInvocation, address: $invokerAddress, nonce: $nonce);
        $contractAuth->sign($invokerKeyPair, Network::futurenet());

        $invokeHostFunction = new InvokeContractHostFunction($contractId, $functionName, $args, auth: [$contractAuth]);

        // simulate first to get the transaction data and resource fee
        sleep(5);
        $submitterAccount = $sdk->requestAccount($submitterId);

        $builder = new InvokeHostFunctionOperationBuilder();
        $op = $builder->addFunction($invokeHostFunction)->build();

        $transaction = (new TransactionBuilder($submitterAccount))
            ->addOperation($op)->build();

        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);
        $this->assertNotNull($simulateResponse->results);
        $this->assertNotNull($simulateResponse->latestLedger);
        $this->assertEquals(1, $simulateResponse->results->count());
        $this->assertNotNull($simulateResponse->getTransactionData());
        $this->assertNotNull($simulateResponse->getFootprint());
        $this->assertGreaterThan(1, $simulateResponse->cost->cpuInsns);
        $this->assertGreaterThan(1, $simulateResponse->cost->memBytes);
        $this->assertGreaterThan(1, $simulateResponse->minResourceFee);
        /*print("Cost cpu: " . $simulateResponse->cost->cpuInsns . PHP_EOL);
        print("Cost mem: " . $simulateResponse->cost->memBytes . PHP_EOL);
        print("min res fee: " . $simulateResponse->minResourceFee . PHP_EOL);*/

        $authResult = $simulateResponse->getAuth();
        $this->assertNotNull($authResult);
        $this->assertCount(1, $authResult);
        /*$xdrAuth = XdrContractAuth::fromBase64Xdr($authResult[0]);
        $cAuth = ContractAuth::fromXdr($xdrAuth);
        $cAuth->sign($invokerKeyPair, Network::futurenet());
        $invokeHostFunction->auth = [$cAuth];*/

        // this is because in preview 9 the fee calculation from the simulation is not always accurate
        // see: https://discord.com/channels/897514728459468821/1112853306881081354
        $transactionData = $simulateResponse->getTransactionData();
        $transactionData->resources->instructions += intval($transactionData->resources->instructions / 4);
        $simulateResponse->minResourceFee += 2800;

        /*print("res instructions: " . $transactionData->resources->getInstructions() . PHP_EOL);
        print("res read bytes: " . $transactionData->resources->getReadBytes() . PHP_EOL);
        print("res write bytes: " . $transactionData->resources->getWriteBytes() . PHP_EOL);
        print("res extended meta bytes: " . $transactionData->resources->getExtendedMetaDataSizeBytes() . PHP_EOL);
        print("fee: " . $simulateResponse->minResourceFee . PHP_EOL);*/

        // set the transaction data + fee and sign
        $transaction->setSorobanTransactionData($transactionData);
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->sign($submitterKeyPair, Network::futurenet());

        // check transaction xdr encoding back and forth
        $transctionEnvelopeXdr = $transaction->toEnvelopeXdrBase64();
        $this->assertEquals($transctionEnvelopeXdr, Transaction::fromEnvelopeBase64XdrString($transctionEnvelopeXdr)->toEnvelopeXdrBase64());

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->hash);
        $this->assertNotNull($statusResponse);
        $this->assertNotNull($statusResponse->getResultValue());

        // seams to be an issue here, getting status failed: invokeHostFunctionResourceLimitExceeded
        // this is strange because the atomic swap test works.
        // see: https://discord.com/channels/897514728459468821/1112853306881081354

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
        $transactionResponse = $sdk->requestTransaction($sendResponse->hash);
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
    public function testSimulationContractAuth(): void
    {
        $server = new SorobanServer("https://rpc-futurenet.stellar.org:443");
        $server->enableLogging = true;
        $server->acknowledgeExperimental = true;
        $sdk = StellarSDK::getFutureNetInstance();

        $submitterKeyPair = KeyPair::random();
        $submitterId = $submitterKeyPair->getAccountId();
        $invokerKeyPair = KeyPair::random();
        $invokerId = $invokerKeyPair->getAccountId();

        // get health
        $getHealthResponse = $server->getHealth();
        $this->assertEquals(GetHealthResponse::HEALTHY, $getHealthResponse->status);

        FuturenetFriendBot::fundTestAccount($submitterId);
        FuturenetFriendBot::fundTestAccount($invokerId);
        sleep(5);

        $getAccountResponse = $sdk->requestAccount($submitterId);
        $this->assertEquals($submitterId, $getAccountResponse->getAccountId());

        // upload contract wasm
        $contractCode = file_get_contents('./wasm/auth.wasm', false);
        $uploadContractHostFunction = new UploadContractWasmHostFunction($contractCode);
        $builder = new InvokeHostFunctionOperationBuilder();
        $op = $builder->addFunction($uploadContractHostFunction)->build();

        $transaction = (new TransactionBuilder($getAccountResponse))
            ->addOperation($op)->build();

        //print($transaction->toEnvelopeXdrBase64() . PHP_EOL);

        // simulate first to get the transaction data + fee
        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);

        // set the transaction data and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->sign($submitterKeyPair, Network::futurenet());

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->hash);
        $this->assertNotNull($statusResponse);
        $wasmId = $statusResponse->getWasmId();
        $this->assertNotNull($wasmId);
        //print("wasm id: " . $wasmId . PHP_EOL);

        // create contract
        sleep(5);
        $submitterAccount = $sdk->requestAccount($submitterId);
        $createContractHostFunction = new CreateContractHostFunction($wasmId);
        $builder = new InvokeHostFunctionOperationBuilder();
        $op = $builder->addFunction($createContractHostFunction)->build();

        $transaction = (new TransactionBuilder($submitterAccount))
            ->addOperation($op)->build();

        // simulate first to get the transaction data + fee
        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);

        // set the transaction data + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->sign($submitterKeyPair, Network::futurenet());

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->hash);
        $this->assertNotNull($statusResponse);
        $contractId = $statusResponse->getContractId();
        $this->assertNotNull($contractId);
        //print("contract id: " . $contractId . PHP_EOL);

        $invokerAddress = Address::fromAccountId($invokerId);

        $functionName = "auth";
        $args = [$invokerAddress->toXdrSCVal(), XdrSCVal::forU32(3)];

        $invokeHostFunction = new InvokeContractHostFunction($contractId, $functionName, $args);

        // simulate first to get the transaction data and resource fee
        sleep(5);
        $submitterAccount = $sdk->requestAccount($submitterId);

        $builder = new InvokeHostFunctionOperationBuilder();
        $op = $builder->addFunction($invokeHostFunction)->build();

        $transaction = (new TransactionBuilder($submitterAccount))
            ->addOperation($op)->build();

        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);
        $this->assertNotNull($simulateResponse->results);
        $this->assertNotNull($simulateResponse->latestLedger);
        $this->assertEquals(1, $simulateResponse->results->count());
        $this->assertNotNull($simulateResponse->getTransactionData());
        $this->assertNotNull($simulateResponse->getFootprint());
        $this->assertGreaterThan(1, $simulateResponse->cost->cpuInsns);
        $this->assertGreaterThan(1, $simulateResponse->cost->memBytes);
        $this->assertGreaterThan(1, $simulateResponse->minResourceFee);
        /*print("Cost cpu: " . $simulateResponse->cost->cpuInsns . PHP_EOL);
        print("Cost mem: " . $simulateResponse->cost->memBytes . PHP_EOL);
        print("min res fee: " . $simulateResponse->minResourceFee . PHP_EOL);*/

        $authResult = $simulateResponse->getAuth();
        $this->assertNotNull($authResult);
        $this->assertCount(1, $authResult);

        // sign auth delivered by simulation response and set it to the transaction
        $xdrAuth = XdrContractAuth::fromBase64Xdr($authResult[0]);
        $cAuth = ContractAuth::fromXdr($xdrAuth);
        $cAuth->sign($invokerKeyPair, Network::futurenet());
        $invokeHostFunction->auth = [$cAuth];

        // this is because in preview 9 the fee calculation from the simulation is not always accurate
        // see: https://discord.com/channels/897514728459468821/1112853306881081354
        $transactionData = $simulateResponse->getTransactionData();
        $transactionData->resources->instructions += intval($transactionData->resources->instructions / 4);
        $simulateResponse->minResourceFee += 3000;

        /*print("res instructions: " . $transactionData->resources->getInstructions() . PHP_EOL);
        print("res read bytes: " . $transactionData->resources->getReadBytes() . PHP_EOL);
        print("res write bytes: " . $transactionData->resources->getWriteBytes() . PHP_EOL);
        print("res extended meta bytes: " . $transactionData->resources->getExtendedMetaDataSizeBytes() . PHP_EOL);
        print("fee: " . $simulateResponse->minResourceFee . PHP_EOL);*/

        // set the transaction data + fee and sign
        $transaction->setSorobanTransactionData($transactionData);
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->sign($submitterKeyPair, Network::futurenet());

        // check transaction xdr encoding back and forth
        $transctionEnvelopeXdr = $transaction->toEnvelopeXdrBase64();
        $this->assertEquals($transctionEnvelopeXdr, Transaction::fromEnvelopeBase64XdrString($transctionEnvelopeXdr)->toEnvelopeXdrBase64());

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->hash);
        $this->assertNotNull($statusResponse);
        $this->assertNotNull($statusResponse->getResultValue());

        // seams to be an issue here, getting status failed: invokeHostFunctionResourceLimitExceeded
        // this is strange because the atomic swap test works.
        // see: https://discord.com/channels/897514728459468821/1112853306881081354

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
        $transactionResponse = $sdk->requestTransaction($sendResponse->hash);
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

}