<?php  declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;
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
use Soneso\StellarSDK\Xdr\XdrDiagnosticEvent;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrTransactionMeta;

class SorobanTest extends TestCase
{

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

        $getAccountResponse = $sdk->requestAccount($accountAId);

        $this->assertEquals($accountAId, $getAccountResponse->getAccountId());
        $this->assertNotNull($getAccountResponse->getSequenceNumber());

        // upload contract wasm
        $contractCode = file_get_contents('./wasm/hello.wasm', false);
        $uploadContractHostFunction = new UploadContractWasmHostFunction($contractCode);
        $builder = new InvokeHostFunctionOperationBuilder();
        $op = $builder->addFunction($uploadContractHostFunction)->build();

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
        /*print("Cost cpu: " . $simulateResponse->cost->cpuInsns . PHP_EOL);
        print("Cost mem: " . $simulateResponse->cost->memBytes . PHP_EOL);
        print("min res fee: " . $simulateResponse->minResourceFee . PHP_EOL);*/

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
            $hostFunctionType = $firstOp->getHostFunctions()?->toArray()[0]->type;
            $this->assertEquals("upload_wasm", $hostFunctionType);
        } else {
            $this->fail();
        }

        // create contract
        $accountA = $sdk->requestAccount($accountAId);
        $createContractHostFunction = new CreateContractHostFunction($helloContractWasmId);
        $builder = new InvokeHostFunctionOperationBuilder();
        $op = $builder->addFunction($createContractHostFunction)->build();

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
        /*print("Cost cpu: " . $simulateResponse->cost->cpuInsns . PHP_EOL);
        print("Cost mem: " . $simulateResponse->cost->memBytes . PHP_EOL);
        print("min res fee: " . $simulateResponse->minResourceFee . PHP_EOL);*/

        // set the transaction data + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->transactionData);
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
        $helloContractId = $statusResponse->getContractId();
        $this->assertNotNull($helloContractId);
        //print("contract id: " . $helloContractId . PHP_EOL);

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
            $hostFunctionType = $firstOp->getHostFunctions()?->toArray()[0]->type;
            $this->assertEquals("create_contract", $hostFunctionType);
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
        $builder = new InvokeHostFunctionOperationBuilder();
        $op = $builder->addFunction($invokeContractHostFunction)->build();

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
        /*print("Cost cpu: " . $simulateResponse->cost->cpuInsns . PHP_EOL);
        print("Cost mem: " . $simulateResponse->cost->memBytes . PHP_EOL);
        print("min res fee: " . $simulateResponse->minResourceFee . PHP_EOL);*/

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
            $hostFunction = $firstOp->getHostFunctions()?->toArray()[0];
            $hostFunctionType = $hostFunction->type;
            $this->assertEquals("invoke_contract", $hostFunctionType);
            foreach ($hostFunction->getParameters() as $parameter) {
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
        $deploySACWithSourceAccountHostFunction = new DeploySACWithSourceAccountHostFunction();
        $builder = new InvokeHostFunctionOperationBuilder();
        $op = $builder->addFunction($deploySACWithSourceAccountHostFunction)->build();

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
        /*print("Cost cpu: " . $simulateResponse->cost->cpuInsns . PHP_EOL);
        print("Cost mem: " . $simulateResponse->cost->memBytes . PHP_EOL);
        print("min res fee: " . $simulateResponse->minResourceFee . PHP_EOL);*/

        // set the transaction data and sign
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
        $ctcId = $statusResponse->getContractId();
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
            $hostFunction = $firstOp->getHostFunctions()?->toArray()[0];
            $hostFunctionType = $hostFunction->type;
            $this->assertEquals("create_contract", $hostFunctionType);
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
        $builder = new InvokeHostFunctionOperationBuilder();
        $op = $builder->addFunction($deploySACWithAssetHostFunction)->build();

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
        /*print("Cost cpu: " . $simulateResponse->cost->cpuInsns . PHP_EOL);
        print("Cost mem: " . $simulateResponse->cost->memBytes . PHP_EOL);
        print("min res fee: " . $simulateResponse->minResourceFee . PHP_EOL);*/

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
        $ctcId = $statusResponse->getContractId();
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
            $hostFunction = $firstOp->getHostFunctions()?->toArray()[0];
            $hostFunctionType = $hostFunction->type;
            $this->assertEquals("create_contract", $hostFunctionType);
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
        sleep(5);

        $getAccountResponse = $sdk->requestAccount($accountAId);
        $this->assertEquals($accountAId, $getAccountResponse->getAccountId());

        // upload contract wasm
        $contractCode = file_get_contents('./wasm/event.wasm', false);
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
        $transaction->sign($accountAKeyPair, Network::futurenet());

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
        $accountA = $sdk->requestAccount($accountAId);

        $createContractHostFunction = new CreateContractHostFunction($wasmId);
        $builder = new InvokeHostFunctionOperationBuilder();
        $op = $builder->addFunction($createContractHostFunction)->build();

        $transaction = (new TransactionBuilder($accountA))
            ->addOperation($op)->build();

        // simulate first to get the transaction data + fee
        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);

        // set the transaction data + fee and sign
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
        $contractId = $statusResponse->getContractId();
        $this->assertNotNull($contractId);
        //print("contract id: " . $contractId . PHP_EOL);

        sleep(3);

        // invoke
        $fnName = "events";
        $accountA = $sdk->requestAccount($accountAId);
        $invokeContractHostFunction = new InvokeContractHostFunction($contractId, $fnName);
        $builder = new InvokeHostFunctionOperationBuilder();
        $op = $builder->addFunction($invokeContractHostFunction)->build();

        $transaction = (new TransactionBuilder($accountA))
            ->addOperation($op)->build();

        // simulate first to get the transaction data and fee
        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);
        $this->assertNotNull($simulateResponse->events);
        $this->assertCount(1, $simulateResponse->events);
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

        $eventFilter = new EventFilter("contract", [$contractId]);
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
}