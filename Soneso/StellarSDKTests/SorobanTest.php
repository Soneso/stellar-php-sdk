<?php  declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;
use Soneso\StellarSDK\BumpFootprintExpirationOperationBuilder;
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\CreateContractHostFunction;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\DeploySACWithAssetHostFunction;
use Soneso\StellarSDK\DeploySACWithSourceAccountHostFunction;
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\Responses\Operations\InvokeHostFunctionOperationResponse;
use Soneso\StellarSDK\RestoreFootprintOperationBuilder;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\Requests\TopicFilter;
use Soneso\StellarSDK\Soroban\Requests\TopicFilters;
use Soneso\StellarSDK\Soroban\Responses\GetHealthResponse;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use Soneso\StellarSDK\Soroban\Requests\EventFilter;
use Soneso\StellarSDK\Soroban\Requests\EventFilters;
use Soneso\StellarSDK\Soroban\Requests\GetEventsRequest;
use Soneso\StellarSDK\Soroban\Responses\SendTransactionResponse;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\UploadContractWasmHostFunction;
use Soneso\StellarSDK\Util\FuturenetFriendBot;
use Soneso\StellarSDK\Xdr\XdrContractEntryBodyType;
use Soneso\StellarSDK\Xdr\XdrDiagnosticEvent;
use Soneso\StellarSDK\Xdr\XdrExtensionPoint;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryType;
use Soneso\StellarSDK\Xdr\XdrLedgerFootprint;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyContractCode;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSorobanResources;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionData;
use Soneso\StellarSDK\Xdr\XdrTransactionMeta;

class SorobanTest extends TestCase
{

    const HELLO_CONTRACT_PATH = './wasm/soroban_hello_world_contract.wasm';
    const EVENTS_CONTRACT_PATH = './wasm/soroban_events_contract.wasm';

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function testSoroban(): void
    {
        $server = new SorobanServer("https://rpc-futurenet.stellar.org:443");
        $server->enableLogging = true;
        $server->acknowledgeExperimental = true;
        $sdk = StellarSDK::getFutureNetInstance();

        $accountAKeyPair = KeyPair::random();
        $accountAId = $accountAKeyPair->getAccountId();

        // get health
        $getHealthResponse = $server->getHealth();
        $this->assertEquals(GetHealthResponse::HEALTHY, $getHealthResponse->status);

        // get network info
        $getNetworkResponse = $server->getNetwork();
        $this->assertEquals("https://friendbot-futurenet.stellar.org/", $getNetworkResponse->friendbotUrl);
        $this->assertEquals("Test SDF Future Network ; October 2022", $getNetworkResponse->passphrase);
        $this->assertNotNull($getNetworkResponse->protocolVersion);

        // fund account
        if (!$sdk->accountExists($accountAId)) {
            FuturenetFriendBot::fundTestAccount($accountAId);
            sleep(5);
        }

        $this->restoreContractFootprint($server, $accountAKeyPair, self::HELLO_CONTRACT_PATH);

        // upload contract wasm
        $contractCode = file_get_contents(self::HELLO_CONTRACT_PATH, false);
        $uploadContractHostFunction = new UploadContractWasmHostFunction($contractCode);
        $op = (new InvokeHostFunctionOperationBuilder($uploadContractHostFunction))->build();

        sleep(5);
        $getAccountResponse = $sdk->requestAccount($accountAId);
        $transaction = (new TransactionBuilder($getAccountResponse))
            ->addOperation($op)->build();

        // simulate first to get the transaction data and fee
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

        $transactionData = $simulateResponse->transactionData;
        $minResourceFee = $simulateResponse->minResourceFee;

        // set the transaction data + fee and sign
        $transaction->setSorobanTransactionData($transactionData);
        $transaction->addResourceFee($minResourceFee);
        $transaction->sign($accountAKeyPair, Network::futurenet());

        // check transaction xdr encoding back and forth
        $transctionEnvelopeXdr = $transaction->toEnvelopeXdrBase64();
        $this->assertEquals($transctionEnvelopeXdr,
            Transaction::fromEnvelopeBase64XdrString($transctionEnvelopeXdr)->toEnvelopeXdrBase64());

        print($transaction->toEnvelopeXdrBase64() . PHP_EOL);

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNotNull($sendResponse->hash);
        $this->assertNotNull($sendResponse->status);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);
        if ($sendResponse->error == null) {
            print("Transaction Id: " . $sendResponse->hash . PHP_EOL);
            print("Status: " . $sendResponse->status . PHP_EOL);
        }

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->hash);
        $this->assertNotNull($statusResponse);
        $helloContractWasmId = $statusResponse->getWasmId();
        $this->assertNotNull($helloContractWasmId);
        //print("wasm id: " . $helloContractWasmId . PHP_EOL);

        $contractCodeEntry = $server->loadContractCodeForWasmId($helloContractWasmId);
        $this->assertNotNull($contractCodeEntry);
        $loadedSourceCode = $contractCodeEntry->body->code->value;
        $this->assertEquals($contractCode, $loadedSourceCode);
        $this->assertGreaterThan(1, $contractCodeEntry->expirationLedgerSeq);

        // check horizon response decoding.
        sleep(5);
        $transactionResponse = $sdk->requestTransaction($sendResponse->hash);
        $this->assertEquals(1, $transactionResponse->getOperationCount());
        $this->assertEquals($transctionEnvelopeXdr, $transactionResponse->getEnvelopeXdr()->toBase64Xdr());
        $meta = $transactionResponse->getResultMetaXdrBase64();

        // parsing meta is working
        $metaXdr = XdrTransactionMeta::fromBase64Xdr($meta);
        //$this->assertEquals($meta, $metaXdr->toBase64Xdr());

        // check horizon operation response
        $operationsResponse = $sdk->operations()->forTransaction($sendResponse->hash)->limit(10)->order("desc")->execute();
        $this->assertTrue($operationsResponse->getOperations()->count() == 1);
        $firstOp = $operationsResponse->getOperations()->toArray()[0];
        if ($firstOp instanceof InvokeHostFunctionOperationResponse) {
            $hostFunctionType = $firstOp->function;
            $this->assertEquals("HostFunctionTypeHostFunctionTypeUploadContractWasm", $hostFunctionType);
        } else {
            $this->fail();
        }

        $this->bumpContractCodeFootprint($server, $accountAKeyPair, $helloContractWasmId, 100000);

        // create contract
        $createContractHostFunction = new CreateContractHostFunction(Address::fromAccountId($accountAId), $helloContractWasmId);
        $builder = new InvokeHostFunctionOperationBuilder($createContractHostFunction);
        $op = $builder->build();
        sleep(5);
        $accountA = $sdk->requestAccount($accountAId);
        $transaction = (new TransactionBuilder($accountA))
            ->addOperation($op)->build();

        // simulate first to get the transaction data + fee
        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);
        $this->assertNotNull($simulateResponse->results);
        $this->assertNotNull($simulateResponse->latestLedger);
        $this->assertEquals(1, $simulateResponse->results->count());
        $this->assertNotNull($simulateResponse->getTransactionData());
        $this->assertNotNull($simulateResponse->getSorobanAuth());
        $this->assertNotNull($simulateResponse->getFootprint());
        $this->assertGreaterThan(1, $simulateResponse->cost->cpuInsns);
        $this->assertGreaterThan(1, $simulateResponse->cost->memBytes);
        $this->assertGreaterThan(1, $simulateResponse->minResourceFee);

        // set the transaction data + fee  + auth and sign
        $transaction->setSorobanTransactionData($simulateResponse->transactionData);
        $transaction->setSorobanAuth($simulateResponse->getSorobanAuth());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->sign($accountAKeyPair, Network::futurenet());

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
        $helloContractId = $statusResponse->getCreatedContractId();
        $this->assertNotNull($helloContractId);

        //print("contract id: " . $helloContractId . PHP_EOL);

        $contractCodeEntry =  $server->loadContractCodeForContractId($helloContractId);
        $this->assertNotNull($contractCodeEntry);
        $loadedSourceCode = $contractCodeEntry->body->code->value;
        $this->assertEquals($contractCode, $loadedSourceCode);
        $this->assertGreaterThan(1, $contractCodeEntry->expirationLedgerSeq);

        sleep(5);

        // check horizon response decoding.
        $transactionResponse = $sdk->requestTransaction($sendResponse->hash);
        $this->assertEquals(1, $transactionResponse->getOperationCount());
        $this->assertEquals($transctionEnvelopeXdr, $transactionResponse->getEnvelopeXdr()->toBase64Xdr());
        $meta = $transactionResponse->getResultMetaXdrBase64();

        // parsing meta is working
        $metaXdr = XdrTransactionMeta::fromBase64Xdr($meta);

        // check horizon operation response
        $operationsResponse = $sdk->operations()->forTransaction($sendResponse->hash)->limit(10)->order("desc")->execute();
        $this->assertTrue($operationsResponse->getOperations()->count() == 1);
        $firstOp = $operationsResponse->getOperations()->toArray()[0];
        if ($firstOp instanceof InvokeHostFunctionOperationResponse) {
            $hostFunctionType = $firstOp->function;
            $this->assertEquals("HostFunctionTypeHostFunctionTypeCreateContract", $hostFunctionType);
        } else {
            $this->fail();
        }

        // test get ledger entry
        $footprint = $simulateResponse->getFootprint();
        $contractCodeKey = $footprint->getContractCodeLedgerKey();
        $this->assertNotNull($contractCodeKey);
        $contractDataKey = $footprint->getContractDataLedgerKey();
        $this->assertNotNull($contractDataKey);

        $contractCodeEntryResponse = $server->getLedgerEntry($contractCodeKey);
        $this->assertNotNull($contractCodeEntryResponse->ledgerEntryData);
        $this->assertNotNull($contractCodeEntryResponse->lastModifiedLedgerSeq);
        $this->assertNotNull($contractCodeEntryResponse->latestLedger);
        $this->assertNotNull($contractCodeEntryResponse->getLedgerEntryDataXdr());

        $contractDataEntryResponse = $server->getLedgerEntry($contractDataKey);
        $this->assertNotNull($contractDataEntryResponse->ledgerEntryData);
        $this->assertNotNull($contractDataEntryResponse->lastModifiedLedgerSeq);
        $this->assertNotNull($contractDataEntryResponse->latestLedger);
        $this->assertNotNull($contractDataEntryResponse->getLedgerEntryDataXdr());

        // invoke contract
        $argVal = XdrSCVal::forSymbol("friend");
        $accountA = $sdk->requestAccount($accountAId);
        $invokeContractHostFunction = new InvokeContractHostFunction($helloContractId, "hello", [$argVal]);
        $builder = new InvokeHostFunctionOperationBuilder($invokeContractHostFunction);
        $op = $builder->build();

        $transaction = (new TransactionBuilder($accountA))
            ->addOperation($op)->build();

        // simulate first to get the transaction data + fee
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

        // set the transaction data  + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->sign($accountAKeyPair, Network::futurenet());

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

        $resultValue = $statusResponse->getResultValue();
        $this->assertNotNull($resultValue);
        $resVec = $resultValue->vec;
        $this->assertNotNull($resVec);
        foreach ($resVec as $symVal) {
            print($symVal->sym . PHP_EOL);
        }

        // user friendly
        $resVal = $statusResponse->getResultValue();
        $vec = $resVal?->getVec();
        if ($vec != null && count($vec) > 1) {
            print("[" . $vec[0]->sym . ", " . $vec[1]->sym . "]" . PHP_EOL);
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

        // check horizon operation response
        $operationsResponse = $sdk->operations()->forTransaction($sendResponse->hash)->limit(10)->order("desc")->execute();
        $this->assertTrue($operationsResponse->getOperations()->count() == 1);
        $firstOp = $operationsResponse->getOperations()->toArray()[0];
        if ($firstOp instanceof InvokeHostFunctionOperationResponse) {
            $hostFunctionType = $firstOp->function;
            $this->assertEquals("HostFunctionTypeHostFunctionTypeInvokeContract", $hostFunctionType);
            foreach ($firstOp->getParameters() as $parameter) {
                $this->assertNotEquals("", trim($parameter->type));
                $this->assertNotNull($parameter->value);
                $this->assertNotEquals("", trim($parameter->value));
                print("Parameter type :" . $parameter->type . " value: " . $parameter->value . PHP_EOL);
            }
        } else {
            $this->fail();
        }

        // deploy create token contract with source account
        $accountA = $sdk->requestAccount($accountAId);
        $deploySACWithSourceAccountHostFunction = new DeploySACWithSourceAccountHostFunction(Address::fromAccountId($accountAId));
        $builder = new InvokeHostFunctionOperationBuilder($deploySACWithSourceAccountHostFunction);
        $op = $builder->build();

        $transaction = (new TransactionBuilder($accountA))
            ->addOperation($op)->build();

        // simulate first to get the transaction data + fee
        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);
        $this->assertNotNull($simulateResponse->results);
        $this->assertNotNull($simulateResponse->latestLedger);
        $this->assertEquals(1, $simulateResponse->results->count());
        $this->assertNotNull($simulateResponse->getTransactionData());
        $this->assertNotNull($simulateResponse->getSorobanAuth());
        $this->assertNotNull($simulateResponse->getFootprint());
        $this->assertGreaterThan(1, $simulateResponse->cost->cpuInsns);
        $this->assertGreaterThan(1, $simulateResponse->cost->memBytes);
        $this->assertGreaterThan(1, $simulateResponse->minResourceFee);
        /*print("Cost cpu: " . $simulateResponse->cost->cpuInsns . PHP_EOL);
        print("Cost mem: " . $simulateResponse->cost->memBytes . PHP_EOL);
        print("min res fee: " . $simulateResponse->minResourceFee . PHP_EOL);*/

        // set the transaction data and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->setSorobanAuth($simulateResponse->getSorobanAuth());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->sign($accountAKeyPair, Network::futurenet());

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
        $ctcId = $statusResponse->getCreatedContractId();
        $this->assertNotNull($ctcId);

        sleep(5);
        // check horizon response decoding.
        $transactionResponse = $sdk->requestTransaction($sendResponse->hash);
        $this->assertEquals(1, $transactionResponse->getOperationCount());
        $this->assertEquals($transctionEnvelopeXdr, $transactionResponse->getEnvelopeXdr()->toBase64Xdr());
        $meta = $transactionResponse->getResultMetaXdrBase64();

        // parsing meta is working
        $metaXdr = XdrTransactionMeta::fromBase64Xdr($meta);
        //$this->assertEquals($meta, $metaXdr->toBase64Xdr());

        // check horizon operation response
        $operationsResponse = $sdk->operations()->forTransaction($sendResponse->hash)->limit(10)->order("desc")->execute();
        $this->assertTrue($operationsResponse->getOperations()->count() == 1);
        $firstOp = $operationsResponse->getOperations()->toArray()[0];
        if ($firstOp instanceof InvokeHostFunctionOperationResponse) {
            $hostFunctionType = $firstOp->function;
            $this->assertEquals("HostFunctionTypeHostFunctionTypeCreateContract", $hostFunctionType);
        } else {
            $this->fail();
        }

        // test deploy create token contract with asset

        // prepare account and asset
        $accountBKeyPair = KeyPair::random();
        $accountBId = $accountBKeyPair->getAccountId();
        FuturenetFriendBot::fundTestAccount($accountBId);
        sleep(5);
        $accountB = $sdk->requestAccount($accountBId);
        $iomAsset = new AssetTypeCreditAlphanum4("IOM", $accountAId);
        $changeTrustBOperation = (new ChangeTrustOperationBuilder($iomAsset, "200999"))
            ->setSourceAccount($accountBId)->build();
        $paymentOperation = (new PaymentOperationBuilder($accountBId, $iomAsset, "100"))->setSourceAccount($accountAId)->build();
        $transaction = (new TransactionBuilder($accountB))
            ->addOperation($changeTrustBOperation)
            ->addOperation($paymentOperation)
            ->build();
        $transaction->sign($accountAKeyPair, Network::futurenet());
        $transaction->sign($accountBKeyPair, Network::futurenet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        // simulate
        sleep(5);
        $accountB = $sdk->requestAccount($accountBId);

        $deploySACWithAssetHostFunction = new DeploySACWithAssetHostFunction($iomAsset);
        $builder = new InvokeHostFunctionOperationBuilder($deploySACWithAssetHostFunction);
        $op = $builder->build();

        $transaction = (new TransactionBuilder($accountB))
            ->addOperation($op)->build();

        // simulate first to get the transaction data + fee
        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);
        $this->assertNotNull($simulateResponse->results);
        $this->assertNotNull($simulateResponse->latestLedger);
        $this->assertEquals(1, $simulateResponse->results->count());
        $this->assertNotNull($simulateResponse->results->toArray()[0]->getResultValue());
        $this->assertNotNull($simulateResponse->getTransactionData());
        $this->assertNotNull($simulateResponse->getFootprint());
        $this->assertGreaterThan(1, $simulateResponse->cost->cpuInsns);
        $this->assertGreaterThan(1, $simulateResponse->cost->memBytes);
        $this->assertGreaterThan(1, $simulateResponse->minResourceFee);


        // set the transaction data + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->sign($accountBKeyPair, Network::futurenet());

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
        $ctcId = $statusResponse->getCreatedContractId();
        $this->assertNotNull($ctcId);

        sleep(5);

        // check horizon response decoding.
        $transactionResponse = $sdk->requestTransaction($sendResponse->hash);
        $this->assertEquals(1, $transactionResponse->getOperationCount());
        $this->assertEquals($transctionEnvelopeXdr, $transactionResponse->getEnvelopeXdr()->toBase64Xdr());
        $meta = $transactionResponse->getResultMetaXdrBase64();

        // parsing meta is working
        $metaXdr = XdrTransactionMeta::fromBase64Xdr($meta);
        //$this->assertEquals($meta, $metaXdr->toBase64Xdr());

        // check horizon operation response
        $operationsResponse = $sdk->operations()->forTransaction($sendResponse->hash)->limit(10)->order("desc")->execute();
        $this->assertTrue($operationsResponse->getOperations()->count() == 1);
        $firstOp = $operationsResponse->getOperations()->toArray()[0];
        if ($firstOp instanceof InvokeHostFunctionOperationResponse) {
            $hostFunctionType =  $firstOp->function;
            $this->assertEquals("HostFunctionTypeHostFunctionTypeCreateContract", $hostFunctionType);
        } else {
            $this->fail();
        }
    }

    public function testSorobanEvents(): void
    {
        $server = new SorobanServer("https://rpc-futurenet.stellar.org:443");
        $server->enableLogging = true;
        $server->acknowledgeExperimental = true;
        $sdk = StellarSDK::getFutureNetInstance();

        $accountAKeyPair = KeyPair::random();
        $accountAId = $accountAKeyPair->getAccountId();

        FuturenetFriendBot::fundTestAccount($accountAId);
        $contractId = $this->deployContract($server, self::EVENTS_CONTRACT_PATH, $accountAKeyPair);

        sleep(3);

        // invoke
        $fnName = "increment";
        $accountA = $sdk->requestAccount($accountAId);
        $invokeContractHostFunction = new InvokeContractHostFunction($contractId, $fnName);
        $builder = new InvokeHostFunctionOperationBuilder($invokeContractHostFunction);
        $op = $builder->build();

        $transaction = (new TransactionBuilder($accountA))
            ->addOperation($op)->build();

        // simulate first to get the transaction data and fee
        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);
        $this->assertNotNull($simulateResponse->events);
        $this->assertCount(3, $simulateResponse->events);
        $xdrEvent = XdrDiagnosticEvent::fromBase64Xdr($simulateResponse->events[0]);
        $this->assertNotNull($xdrEvent);

        // set the transaction data  + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->sign($accountAKeyPair, Network::futurenet());

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->hash);
        $this->assertNotNull($statusResponse);

        sleep(5);
        $transactionResponse = $sdk->requestTransaction($sendResponse->hash);
        $this->assertEquals(1, $transactionResponse->getOperationCount());

        // get events
        $ledger = $transactionResponse->getLedger();
        $startLedger = strval($ledger);

        // seams that position of the topic in the filter must match event topics ...
        $topicFilter = new TopicFilter(["*", XdrSCVal::forSymbol("increment")->toBase64Xdr()]);
        //$topicFilter = new TopicFilter([XdrSCVal::forSymbol("COUNTER")->toBase64Xdr(), "*"]);
        $topicFilters = new TopicFilters($topicFilter);

        $eventFilter = new EventFilter("contract", [$contractId], $topicFilters);
        $eventFilters = new EventFilters();
        $eventFilters->add($eventFilter);

        $request = new GetEventsRequest($startLedger, $eventFilters);
        $response = $server->getEvents($request);
        $this->assertGreaterThan(0, count($response->events));

    }

    public function testStrKeyEncoding(): void
    {
        $contractIdA = "86efd9a9d6fbf70297294772c9676127e16a23c2141cab3e29be836bb537a9b9";
        $strEncodedA = "CCDO7WNJ2357OAUXFFDXFSLHMET6C2RDYIKBZKZ6FG7IG25VG6U3SLHT";
        $strEncodedB = StrKey::encodeContractIdHex($contractIdA);
        $this->assertEquals($strEncodedA, $strEncodedB);

        $contractIdB = StrKey::decodeContractIdHex($strEncodedB);
        $this->assertEquals($contractIdA,$contractIdB);

        $strEncodedC = StrKey::encodeContractId(hex2bin($contractIdA));
        $this->assertEquals($strEncodedA, $strEncodedC);
        $this->assertEquals($contractIdA , bin2hex(StrKey::decodeContractId($strEncodedC)));
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
        $sdk = StellarSDK::getFutureNetInstance();

        $this->restoreContractFootprint($server, $submitterKp, $pathToCode);

        // upload contract wasm
        $contractCode = file_get_contents($pathToCode, false);

        $uploadContractHostFunction = new UploadContractWasmHostFunction($contractCode);
        $builder = new InvokeHostFunctionOperationBuilder($uploadContractHostFunction);
        $op = $builder->build();

        sleep(5);
        $submitterId = $submitterKp->getAccountId();
        $account = $sdk->requestAccount($submitterId);
        $transaction = (new TransactionBuilder($account))->addOperation($op)->build();

        // simulate first to get the transaction data and resource fee
        $simulateResponse = $server->simulateTransaction($transaction);


        // set the transaction data + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->sign($submitterKp, Network::futurenet());

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
        $account = $sdk->requestAccount($submitterId);
        $transaction = (new TransactionBuilder($account))
            ->addOperation($op)->build();

        // simulate first to get the transaction data and resource fee
        $simulateResponse = $server->simulateTransaction($transaction);

        // set the transaction data + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->setSorobanAuth($simulateResponse->getSorobanAuth());
        $transaction->sign($submitterKp, Network::futurenet());

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

    private function restoreContractFootprint(SorobanServer $server, KeyPair $accountKeyPair, string $contractCodePath) : void {
        sleep(5);
        $sdk = StellarSDK::getFutureNetInstance();

        $contractCode = file_get_contents($contractCodePath, false);
        $uploadContractHostFunction = new UploadContractWasmHostFunction($contractCode);
        $op = (new InvokeHostFunctionOperationBuilder($uploadContractHostFunction))->build();

        $accountAId = $accountKeyPair->getAccountId();
        $getAccountResponse = $sdk->requestAccount($accountAId);
        $transaction = (new TransactionBuilder($getAccountResponse))
            ->addOperation($op)->build();

        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);
        $this->assertNotNull($simulateResponse->results);
        $this->assertNotNull($simulateResponse->getTransactionData());

        $transactionData = $simulateResponse->getTransactionData();
        $transactionData->resources->footprint->readWrite = $transactionData->resources->footprint->readWrite + $transactionData->resources->footprint->readOnly;
        $transactionData->resources->footprint->readOnly = array();

        $getAccountResponse = $sdk->requestAccount($accountAId);
        $restoreOp = (new RestoreFootprintOperationBuilder())->build();
        $transaction = (new TransactionBuilder($getAccountResponse))
            ->addOperation($restoreOp)->build();

        $transaction->setSorobanTransactionData($transactionData) ;
        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);
        $this->assertNotNull($simulateResponse->results);
        $this->assertNotNull($simulateResponse->getTransactionData());
        $this->assertNotNull($simulateResponse->getMinResourceFee());

        // set the transaction data + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->getMinResourceFee());
        $transaction->sign($accountKeyPair, Network::futurenet());

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

    private function bumpContractCodeFootprint(SorobanServer $server, KeyPair $accountKeyPair, string $wasmId, int $ledgersToExpire) : void {
        sleep(5);
        $sdk = StellarSDK::getFutureNetInstance();

        $builder = new BumpFootprintExpirationOperationBuilder($ledgersToExpire);
        $bumpOp = $builder->build();

        $accountAId = $accountKeyPair->getAccountId();
        $getAccountResponse = $sdk->requestAccount($accountAId);
        $transaction = (new TransactionBuilder($getAccountResponse))
            ->addOperation($bumpOp)->build();

        $readOnly = array();
        $readWrite = array();
        $codeKey = new XdrLedgerKey(XdrLedgerEntryType::CONTRACT_CODE());
        $codeKey->contractCode = new XdrLedgerKeyContractCode(hex2bin($wasmId), XdrContractEntryBodyType::DATA_ENTRY());
        array_push($readOnly, $codeKey);

        $footprint = new XdrLedgerFootprint($readOnly, $readWrite);
        $resources = new XdrSorobanResources($footprint, 0,0,0,0);
        $transactionData = new XdrSorobanTransactionData(new XdrExtensionPoint(0), $resources, 0);

        $transaction->setSorobanTransactionData($transactionData) ;
        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);
        $this->assertNotNull($simulateResponse->results);
        $this->assertNotNull($simulateResponse->getTransactionData());
        $this->assertNotNull($simulateResponse->getMinResourceFee());

        // set the transaction data + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->getMinResourceFee());
        $transaction->sign($accountKeyPair, Network::futurenet());

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
}