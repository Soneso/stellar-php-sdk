<?php  declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Integration;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;
use Soneso\StellarSDK\ExtendFootprintTTLOperationBuilder;
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\CreateContractHostFunction;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\DeploySACWithAssetHostFunction;
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\Responses\Operations\InvokeHostFunctionOperationResponse;
use Soneso\StellarSDK\RestoreFootprintOperationBuilder;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\Requests\GetLedgersRequest;
use Soneso\StellarSDK\Soroban\Requests\GetTransactionsRequest;
use Soneso\StellarSDK\Soroban\Requests\PaginationOptions;
use Soneso\StellarSDK\Soroban\Requests\SimulateTransactionRequest;
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
use Soneso\StellarSDK\Util\FriendBot;
use Soneso\StellarSDK\Util\FuturenetFriendBot;
use Soneso\StellarSDK\Xdr\XdrContractDataDurability;
use Soneso\StellarSDK\Xdr\XdrContractEvent;
use Soneso\StellarSDK\Xdr\XdrDiagnosticEvent;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryType;
use Soneso\StellarSDK\Xdr\XdrLedgerFootprint;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyContractCode;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSorobanResources;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionData;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionDataExt;
use Soneso\StellarSDK\Xdr\XdrTransactionEvent;
use Soneso\StellarSDK\Xdr\XdrTransactionMeta;
use function PHPUnit\Framework\assertNotNull;

class SorobanTest extends TestCase
{

    const HELLO_CONTRACT_PATH = './../wasm/soroban_hello_world_contract.wasm';
    const EVENTS_CONTRACT_PATH = './../wasm/soroban_events_contract.wasm';

    const TESTNET_SERVER_URL = "https://soroban-testnet.stellar.org";
    const FUTURENET_SERVER_URL = "https://rpc-futurenet.stellar.org";

    private string $testOn = 'testnet'; // 'futurenet'
    private Network $network;
    private KeyPair $accountAKeyPair;
    private string $accountAId;
    private SorobanServer $server;
    private StellarSDK $sdk;
    public function setUp(): void
    {
        // Turn on error reporting
        error_reporting(E_ALL);
        $this->accountAKeyPair = KeyPair::random();
        $this->accountAId = $this->accountAKeyPair->getAccountId();
        if ($this->testOn === 'testnet') {
            FriendBot::fundTestAccount($this->accountAId);
            $this->network = Network::testnet();
            $this->server = new SorobanServer(self::TESTNET_SERVER_URL);
            $this->server->enableLogging = true;
            $this->sdk = StellarSDK::getTestNetInstance();
        } elseif ($this->testOn === 'futurenet') {
            FuturenetFriendBot::fundTestAccount($this->accountAId);
            $this->network = Network::futurenet();
            $this->server = new SorobanServer(self::FUTURENET_SERVER_URL);
            $this->server->enableLogging = true;
            $this->sdk = StellarSDK::getFutureNetInstance();
        }
        sleep(5);
    }

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function testSoroban(): void
    {
        // get health
        $getHealthResponse = $this->server->getHealth();
        $this->assertEquals(GetHealthResponse::HEALTHY, $getHealthResponse->status);
        $this->assertNotNull($getHealthResponse->ledgerRetentionWindow);
        $this->assertNotNull($getHealthResponse->latestLedger);
        $this->assertNotNull($getHealthResponse->oldestLedger);

        // get network info
        $getNetworkResponse = $this->server->getNetwork();
        if ($this->testOn === 'testnet') {
            $this->assertEquals("https://friendbot.stellar.org/", $getNetworkResponse->friendbotUrl);
            $this->assertEquals("Test SDF Network ; September 2015", $getNetworkResponse->passphrase);
        } elseif ($this->testOn === 'futurenet') {
            $this->assertEquals("https://friendbot-futurenet.stellar.org/", $getNetworkResponse->friendbotUrl);
            $this->assertEquals("Test SDF Future Network ; October 2022", $getNetworkResponse->passphrase);
        }

        $this->assertNotNull($getNetworkResponse->protocolVersion);

        // get fee stats
        $getFeeStatsResponse = $this->server->getFeeStats();
        $this->assertNotNull($getFeeStatsResponse->sorobanInclusionFee);
        $this->assertNotNull($getFeeStatsResponse->inclusionFee);
        $this->assertNotNull($getFeeStatsResponse->latestLedger);

        // get version info
        $getVersionInfoResponse = $this->server->getVersionInfo();
        $this->assertNotNull($getVersionInfoResponse->version);
        $this->assertNotNull($getVersionInfoResponse->commitHash);
        $this->assertNotNull($getVersionInfoResponse->buildTimeStamp);
        $this->assertNotNull($getVersionInfoResponse->captiveCoreVersion);
        $this->assertNotNull($getVersionInfoResponse->protocolVersion);

        // get transactions
        $latestLedgerResponse = $this->server->getLatestLedger();
        $this->assertNotNull($latestLedgerResponse->sequence);
        $this->assertNotNull($latestLedgerResponse->id);
        $this->assertNotNull($latestLedgerResponse->protocolVersion);
        // New fields added in RPC v25.0.0
        $this->assertNotNull($latestLedgerResponse->closeTime);
        $this->assertGreaterThan(0, $latestLedgerResponse->closeTime);
        $this->assertNotNull($latestLedgerResponse->headerXdr);
        $this->assertNotEmpty($latestLedgerResponse->headerXdr);
        $this->assertNotNull($latestLedgerResponse->metadataXdr);
        $this->assertNotEmpty($latestLedgerResponse->metadataXdr);
        $startLedger = $latestLedgerResponse->sequence - 200;
        $paginationOptions = new PaginationOptions(limit: 2);
        $getTransactionsRequest = new GetTransactionsRequest(
            startLedger: $startLedger,
            paginationOptions: $paginationOptions,
        );
        $getTransactionsResponse = $this->server->getTransactions($getTransactionsRequest);
        $this->assertNotNull($getTransactionsResponse->transactions);
        $this->assertNotNull($getTransactionsResponse->latestLedger);
        $this->assertNotNull($getTransactionsResponse->oldestLedger);
        $this->assertNotNull($getTransactionsResponse->latestLedgerCloseTimestamp);
        $this->assertNotNull($getTransactionsResponse->oldestLedgerCloseTimestamp);
        $this->assertNotNull($getTransactionsResponse->cursor);
        $this->assertGreaterThan(0, count($getTransactionsResponse->transactions));

        $paginationOptions = new PaginationOptions(
            cursor: $getTransactionsResponse->cursor,
            limit: 2,
        );
        $getTransactionsRequest = new GetTransactionsRequest(paginationOptions: $paginationOptions);
        $getTransactionsResponse = $this->server->getTransactions($getTransactionsRequest);
        $this->assertNotNull($getTransactionsResponse->transactions);
        $this->assertCount(2, $getTransactionsResponse->transactions);

        // upload contract wasm
        $contractCode = file_get_contents(self::HELLO_CONTRACT_PATH, false);
        $uploadContractHostFunction = new UploadContractWasmHostFunction($contractCode);
        $op = (new InvokeHostFunctionOperationBuilder($uploadContractHostFunction))->build();

        sleep(5);
        $account = $this->server->getAccount($this->accountAId);
        assertNotNull($account);
        $transaction = (new TransactionBuilder($account))
            ->addOperation($op)->build();

        // simulate first to get the transaction data and fee
        $request = new SimulateTransactionRequest($transaction);
        $simulateResponse = $this->server->simulateTransaction($request);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);
        $this->assertNotNull($simulateResponse->results);
        $this->assertNotNull($simulateResponse->latestLedger);
        $this->assertEquals(1, $simulateResponse->results->count());
        $this->assertNotNull($simulateResponse->getTransactionData());
        $this->assertNotNull($simulateResponse->getFootprint());
        $this->assertGreaterThan(1, $simulateResponse->minResourceFee);

        $transactionData = $simulateResponse->transactionData;
        $minResourceFee = $simulateResponse->minResourceFee;

        // set the transaction data + fee and sign
        $transaction->setSorobanTransactionData($transactionData);
        $transaction->addResourceFee($minResourceFee);
        $transaction->sign($this->accountAKeyPair, $this->network);

        // check transaction xdr encoding back and forth
        $transctionEnvelopeXdr = $transaction->toEnvelopeXdrBase64();
        $this->assertEquals($transctionEnvelopeXdr,
            Transaction::fromEnvelopeBase64XdrString($transctionEnvelopeXdr)->toEnvelopeXdrBase64());

        print($transaction->toEnvelopeXdrBase64() . PHP_EOL);

        // send the transaction
        $sendResponse = $this->server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNotNull($sendResponse->hash);
        $this->assertNotNull($sendResponse->status);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);
        if ($sendResponse->error == null) {
            print("Transaction Id: " . $sendResponse->hash . PHP_EOL);
            print("Status: " . $sendResponse->status . PHP_EOL);
        }

        // poll until status is success or error
        $statusResponse = $this->pollStatus($this->server, $sendResponse->hash);
        $this->assertNotNull($statusResponse);
        $helloContractWasmId = $statusResponse->getWasmId();
        $this->assertNotNull($helloContractWasmId);
        //print("wasm id: " . $helloContractWasmId . PHP_EOL);

        $contractCodeEntry = $this->server->loadContractCodeForWasmId($helloContractWasmId);
        $this->assertNotNull($contractCodeEntry);
        $loadedSourceCode = $contractCodeEntry->code->value;
        $this->assertEquals($contractCode, $loadedSourceCode);

        // check horizon response decoding.
        sleep(5);
        $transactionResponse = $this->sdk->requestTransaction($sendResponse->hash);
        $this->assertEquals(1, $transactionResponse->getOperationCount());
        $this->assertEquals($transctionEnvelopeXdr, $transactionResponse->getEnvelopeXdr()->toBase64Xdr());
        $meta = $transactionResponse->getResultMetaXdrBase64();

        // parsing meta is working
        if ($meta !== null) {
            $metaXdr = XdrTransactionMeta::fromBase64Xdr($meta);
            $this->assertEquals($meta, $metaXdr->toBase64Xdr());
        }

        // check horizon operation response
        $operationsResponse = $this->sdk->operations()->forTransaction($sendResponse->hash)->limit(10)->order("desc")->execute();
        $this->assertTrue($operationsResponse->getOperations()->count() == 1);
        $firstOp = $operationsResponse->getOperations()->toArray()[0];
        if ($firstOp instanceof InvokeHostFunctionOperationResponse) {
            $hostFunctionType = $firstOp->function;
            $this->assertEquals("HostFunctionTypeHostFunctionTypeUploadContractWasm", $hostFunctionType);
        } else {
            $this->fail();
        }

        $this->bumpContractCodeFootprint($this->server, $this->accountAKeyPair, $helloContractWasmId, 100000);

        // create contract
        $createContractHostFunction = new CreateContractHostFunction(Address::fromAccountId($this->accountAId), $helloContractWasmId);
        $builder = new InvokeHostFunctionOperationBuilder($createContractHostFunction);
        $op = $builder->build();
        sleep(5);
        $accountA = $this->server->getAccount($this->accountAId);
        assertNotNull($accountA);
        $transaction = (new TransactionBuilder($accountA))
            ->addOperation($op)->build();

        // simulate first to get the transaction data + fee
        $request = new SimulateTransactionRequest($transaction);
        $simulateResponse = $this->server->simulateTransaction($request);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);
        $this->assertNotNull($simulateResponse->results);
        $this->assertNotNull($simulateResponse->latestLedger);
        $this->assertEquals(1, $simulateResponse->results->count());
        $this->assertNotNull($simulateResponse->getTransactionData());
        $this->assertNotNull($simulateResponse->getSorobanAuth());
        $this->assertNotNull($simulateResponse->getFootprint());
        $this->assertGreaterThan(1, $simulateResponse->minResourceFee);

        // set the transaction data + fee  + auth and sign
        $transaction->setSorobanTransactionData($simulateResponse->transactionData);
        $transaction->setSorobanAuth($simulateResponse->getSorobanAuth());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->sign($this->accountAKeyPair, $this->network);

        // check transaction xdr encoding back and forth
        $transctionEnvelopeXdr = $transaction->toEnvelopeXdrBase64();
        $this->assertEquals($transctionEnvelopeXdr,
            Transaction::fromEnvelopeBase64XdrString($transctionEnvelopeXdr)->toEnvelopeXdrBase64());

        // send the transaction
        $sendResponse = $this->server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNotNull($sendResponse->hash);
        $this->assertNotNull($sendResponse->status);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($this->server, $sendResponse->hash);
        $this->assertNotNull($statusResponse);
        $helloContractId = $statusResponse->getCreatedContractId();
        $this->assertNotNull($helloContractId);

        print("contract id: " . StrKey::encodeContractIdHex($helloContractId) . PHP_EOL);

        $contractCodeEntry =  $this->server->loadContractCodeForContractId($helloContractId);
        $this->assertNotNull($contractCodeEntry);
        $loadedSourceCode = $contractCodeEntry->code->value;
        $this->assertEquals($contractCode, $loadedSourceCode);

        $contractInfo = $this->server->loadContractInfoForContractId($helloContractId);
        $this->assertNotNull($contractInfo);
        $this->assertTrue(count($contractInfo->specEntries) > 0);
        $this->assertTrue(count($contractInfo->metaEntries) > 0);

        $contractInfo = $this->server->loadContractInfoForWasmId($helloContractWasmId);
        $this->assertNotNull($contractInfo);
        $this->assertTrue(count($contractInfo->specEntries) > 0);
        $this->assertTrue(count($contractInfo->metaEntries) > 0);

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

        // check horizon operation response
        $operationsResponse = $this->sdk->operations()->forTransaction($sendResponse->hash)->limit(10)->order("desc")->execute();
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

        $contractCodeEntryResponse = $this->server->getLedgerEntries([$contractCodeKey]);
        $this->assertNotNull($contractCodeEntryResponse->entries);
        $this->assertCount(1,$contractCodeEntryResponse->entries);
        $this->assertNotNull($contractCodeEntryResponse->latestLedger);
        $this->assertNotNull($contractCodeEntryResponse->entries[0]->key);
        $this->assertNotNull($contractCodeEntryResponse->entries[0]->lastModifiedLedgerSeq);
        $this->assertNotNull($contractCodeEntryResponse->entries[0]->getLedgerEntryDataXdr());

        $contractDataEntryResponse = $this->server->getLedgerEntries([$contractDataKey]);
        $this->assertNotNull($contractDataEntryResponse->entries);
        $this->assertCount(1,$contractDataEntryResponse->entries);
        $this->assertNotNull($contractDataEntryResponse->latestLedger);
        $this->assertNotNull($contractDataEntryResponse->entries[0]->key);
        $this->assertNotNull($contractDataEntryResponse->entries[0]->lastModifiedLedgerSeq);
        $this->assertNotNull($contractDataEntryResponse->entries[0]->getLedgerEntryDataXdr());

        // test restore
        $this->restoreContractFootprint($this->server, $this->accountAKeyPair, self::HELLO_CONTRACT_PATH);

        // invoke contract
        $argVal = XdrSCVal::forSymbol("friend");
        $accountA = $this->server->getAccount($this->accountAId);
        assertNotNull($accountA);

        $invokeContractHostFunction = new InvokeContractHostFunction($helloContractId, "hello", [$argVal]);
        $builder = new InvokeHostFunctionOperationBuilder($invokeContractHostFunction);
        $op = $builder->build();

        $transaction = (new TransactionBuilder($accountA))
            ->addOperation($op)->build();

        // simulate first to get the transaction data + fee
        $request = new SimulateTransactionRequest($transaction);
        $simulateResponse = $this->server->simulateTransaction($request);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);
        $this->assertNotNull($simulateResponse->results);
        $this->assertNotNull($simulateResponse->latestLedger);
        $this->assertEquals(1, $simulateResponse->results->count());
        $this->assertNotNull($simulateResponse->getTransactionData());
        $this->assertNotNull($simulateResponse->getFootprint());
        $this->assertGreaterThan(1, $simulateResponse->minResourceFee);

        // set the transaction data  + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->sign($this->accountAKeyPair, $this->network);

        // check transaction xdr encoding back and forth
        $transctionEnvelopeXdr = $transaction->toEnvelopeXdrBase64();
        $this->assertEquals($transctionEnvelopeXdr,
            Transaction::fromEnvelopeBase64XdrString($transctionEnvelopeXdr)->toEnvelopeXdrBase64());

        // send the transaction
        $sendResponse = $this->server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNotNull($sendResponse->hash);
        $this->assertNotNull($sendResponse->status);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($this->server, $sendResponse->hash);
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
        $transactionResponse = $this->sdk->requestTransaction($sendResponse->hash);
        $this->assertEquals(1, $transactionResponse->getOperationCount());
        $this->assertEquals($transctionEnvelopeXdr, $transactionResponse->getEnvelopeXdr()->toBase64Xdr());
        $meta = $transactionResponse->getResultMetaXdrBase64();

        // parsing meta is working
        if ($meta !== null) {
            $metaXdr = XdrTransactionMeta::fromBase64Xdr($meta);
            $this->assertEquals($meta, $metaXdr->toBase64Xdr());
        }

        // check horizon operation response
        $operationsResponse = $this->sdk->operations()->forTransaction($sendResponse->hash)->limit(10)->order("desc")->execute();
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

        // test deploy create token contract with asset

        // prepare account and asset
        $accountBKeyPair = KeyPair::random();
        $accountBId = $accountBKeyPair->getAccountId();
        // fund account
        if ($this->testOn == 'testnet') {
            FriendBot::fundTestAccount($accountBId);
        } elseif ($this->testOn == 'futurenet') {
            FuturenetFriendBot::fundTestAccount($accountBId);
        }
        sleep(5);

        $accountB = $this->server->getAccount($accountBId);
        assertNotNull($accountB);
        $iomAsset = new AssetTypeCreditAlphanum4("IOM", $this->accountAId);
        $changeTrustBOperation = (new ChangeTrustOperationBuilder($iomAsset, "200999"))
            ->setSourceAccount($accountBId)->build();
        $paymentOperation = (new PaymentOperationBuilder($accountBId, $iomAsset, "100"))->setSourceAccount($this->accountAId)->build();
        $transaction = (new TransactionBuilder($accountB))
            ->addOperation($changeTrustBOperation)
            ->addOperation($paymentOperation)
            ->build();
        $transaction->sign($this->accountAKeyPair, $this->network);
        $transaction->sign($accountBKeyPair, $this->network);
        $response = $this->sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());

        // simulate
        sleep(5);
        $accountB = $this->server->getAccount($accountBId);
        assertNotNull($accountB);

        $deploySACWithAssetHostFunction = new DeploySACWithAssetHostFunction($iomAsset);
        $builder = new InvokeHostFunctionOperationBuilder($deploySACWithAssetHostFunction);
        $op = $builder->build();

        $transaction = (new TransactionBuilder($accountB))
            ->addOperation($op)->build();

        // simulate first to get the transaction data + fee
        $request = new SimulateTransactionRequest($transaction);
        $simulateResponse = $this->server->simulateTransaction($request);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);
        $this->assertNotNull($simulateResponse->results);
        $this->assertNotNull($simulateResponse->latestLedger);
        $this->assertEquals(1, $simulateResponse->results->count());
        $this->assertNotNull($simulateResponse->results->toArray()[0]->getResultValue());
        $this->assertNotNull($simulateResponse->getTransactionData());
        $this->assertNotNull($simulateResponse->getFootprint());
        $this->assertGreaterThan(1, $simulateResponse->minResourceFee);


        // set the transaction data + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->sign($accountBKeyPair, $this->network);

        // check transaction xdr encoding back and forth
        $transctionEnvelopeXdr = $transaction->toEnvelopeXdrBase64();
        $this->assertEquals($transctionEnvelopeXdr,
            Transaction::fromEnvelopeBase64XdrString($transctionEnvelopeXdr)->toEnvelopeXdrBase64());

        // send the transaction
        $sendResponse = $this->server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNotNull($sendResponse->hash);
        $this->assertNotNull($sendResponse->status);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($this->server, $sendResponse->hash);
        $this->assertNotNull($statusResponse);
        $ctcId = $statusResponse->getCreatedContractId();
        $this->assertNotNull($ctcId);

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

        // check horizon operation response
        $operationsResponse = $this->sdk->operations()->forTransaction($sendResponse->hash)->limit(10)->order("desc")->execute();
        $this->assertTrue($operationsResponse->getOperations()->count() == 1);
        $firstOp = $operationsResponse->getOperations()->toArray()[0];
        if ($firstOp instanceof InvokeHostFunctionOperationResponse) {
            $hostFunctionType =  $firstOp->function;
            $this->assertEquals("HostFunctionTypeHostFunctionTypeCreateContract", $hostFunctionType);
        } else {
            $this->fail();
        }

        // test contract data fetching
        $entry = $this->server->getContractData(
            $helloContractId,
            XdrSCVal::forLedgerKeyContractInstance(),
            XdrContractDataDurability::PERSISTENT(),
        );
        assertNotNull($entry);
    }

    public function testSorobanEvents(): void
    {

        $contractId = $this->deployContract($this->server, self::EVENTS_CONTRACT_PATH, $this->accountAKeyPair);

        $contractInfo = $this->server->loadContractInfoForContractId(StrKey::encodeContractIdHex($contractId));
        $this->assertNotNull($contractInfo);
        $this->assertTrue(count($contractInfo->specEntries) > 0);
        $this->assertTrue(count($contractInfo->metaEntries) > 0);

        sleep(3);

        // invoke
        $fnName = "increment";
        $accountA = $this->server->getAccount($this->accountAId);
        assertNotNull($accountA);

        $invokeContractHostFunction = new InvokeContractHostFunction($contractId, $fnName);
        $builder = new InvokeHostFunctionOperationBuilder($invokeContractHostFunction);
        $op = $builder->build();

        $transaction = (new TransactionBuilder($accountA))
            ->addOperation($op)->build();

        // simulate first to get the transaction data and fee
        $request = new SimulateTransactionRequest($transaction);
        $simulateResponse = $this->server->simulateTransaction($request);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);
        $this->assertNotNull($simulateResponse->events);
        $this->assertCount(3, $simulateResponse->events);
        $xdrEvent = XdrDiagnosticEvent::fromBase64Xdr($simulateResponse->events[0]);
        $this->assertNotNull($xdrEvent);

        // set the transaction data  + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->sign($this->accountAKeyPair, $this->network);

        // send the transaction
        $sendResponse = $this->server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($this->server, $sendResponse->hash);
        $this->assertNotNull($statusResponse);

        sleep(5);
        $transactionResponse = $this->sdk->requestTransaction($sendResponse->hash);
        $this->assertEquals(1, $transactionResponse->getOperationCount());

        // get events
        $ledger = $transactionResponse->getLedger();
        $startLedger = $ledger;

        // seams that position of the topic in the filter must match event topics ...
        $topicFilter = new TopicFilter(["*", XdrSCVal::forSymbol("increment")->toBase64Xdr()]);
        //$topicFilter = new TopicFilter([XdrSCVal::forSymbol("COUNTER")->toBase64Xdr(), "*"]);
        $topicFilters = new TopicFilters($topicFilter);

        $eventFilter = new EventFilter("contract", [StrKey::encodeContractIdHex($contractId)], $topicFilters);
        $eventFilters = new EventFilters();
        $eventFilters->add($eventFilter);

        $paginationOptions = new PaginationOptions(limit: 2);
        $request = new GetEventsRequest(
            startLedger: $startLedger,
            endLedger: $startLedger + 5,
            filters: $eventFilters,
            paginationOptions: $paginationOptions,
        );
        $response = $this->server->getEvents($request);
        $this->assertGreaterThan(0, count($response->events));

        // Verify the new fields are populated
        $this->assertNotNull($response->oldestLedger);
        $this->assertGreaterThan(0, $response->oldestLedger);
        $this->assertNotNull($response->latestLedgerCloseTime);
        $this->assertGreaterThan(0, $response->latestLedgerCloseTime);
        $this->assertNotNull($response->oldestLedgerCloseTime);
        $this->assertGreaterThan(0, $response->oldestLedgerCloseTime);
        $this->assertLessThanOrEqual($response->latestLedger, $response->oldestLedger);

    }

    public function testSorobanTransactionEventsParsing(): void
    {
        $body = "{  \"jsonrpc\": \"2.0\",  \"id\": 8675309,  \"result\": {    \"latestLedger\": 1317536,    \"latestLedgerCloseTime\": \"1748999063\",    \"oldestLedger\": 1297535,    \"oldestLedgerCloseTime\": \"1748898975\",    \"status\": \"SUCCESS\",    \"txHash\": \"2e29cacfc90565027c44bb9477f58af7c179309b5234d6742cd7e7301fcd847f\",    \"applicationOrder\": 1,    \"feeBump\": false,    \"envelopeXdr\": \"AAAAAgAAAADte5nJrehJq/pu3qlV/bASRSOiJVXdNC+gQW/nxVNWuQBY644AEETyAAAOngAAAAAAAAAAAAAAAQAAAAAAAAAYAAAAAAAAAAHf65G24dyt1q+Xu3xFX5fzdHcKf3j2lXO5n11b+EnOfAAAAA1jcmVhdGVfZXNjcm93AAAAAAAABQAAAAUAAAAAAAaRmQAAABIAAAAAAAAAACcMY2GvjF3igK326WyiU8hv107p9YxvAS29gt1fml2WAAAAEgAAAAAAAAAAyewwXk7lqpxiQNYP3VlZ1EEprNK+dSBV4KQ9iluwbx8AAAASAAAAAAAAAAAY2Rm1IXXndEI0rYg2bt1/rw2mi1SYOUT2qeKPvf56cgAAABIAAAABusKzizgXRsUWKJQRrpWHAWG/yujQ6LBT/pMDljEiAegAAAAAAAAAAQAAAAAAAAACAAAABgAAAAHf65G24dyt1q+Xu3xFX5fzdHcKf3j2lXO5n11b+EnOfAAAABQAAAABAAAABw92WUOXbPOCn5SPHsgIOq8K1UypMpJe18Eh5s6eH8KeAAAAAQAAAAYAAAAB3+uRtuHcrdavl7t8RV+X83R3Cn949pVzuZ9dW/hJznwAAAAQAAAAAQAAAAIAAAAPAAAABVN0YXRlAAAAAAAABQAAAAAABpGZAAAAAQA1p5gAAEC0AAABuAAAAAAAWOsqAAAAAcVTVrkAAABAkR3EyCbHmZqEzQ1hvb1u2zY8PMqfhm7Z8zULGlpdNV0rWSbchA/NDudYEYrQKdA0qy647T+ojtdMfwLrfHELCA==\",    \"resultXdr\": \"AAAAAABM5iEAAAAAAAAAAQAAAAAAAAAYAAAAANkPSp3CD6fXFropzD1Dse4sGrxEO/NPfv6SvhMR1kNkAAAAAA==\",    \"resultMetaXdr\": \"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\",    \"diagnosticEventsXdr\": [      \"AAAAAQAAAAAAAAAAAAAAAgAAAAAAAAADAAAADwAAAAdmbl9jYWxsAAAAAA0AAAAg3+uRtuHcrdavl7t8RV+X83R3Cn949pVzuZ9dW/hJznwAAAAPAAAADWNyZWF0ZV9lc2Nyb3cAAAAAAAAQAAAAAQAAAAUAAAAFAAAAAAAGkZkAAAASAAAAAAAAAAAnDGNhr4xd4oCt9ulsolPIb9dO6fWMbwEtvYLdX5pdlgAAABIAAAAAAAAAAMnsMF5O5aqcYkDWD91ZWdRBKazSvnUgVeCkPYpbsG8fAAAAEgAAAAAAAAAAGNkZtSF153RCNK2INm7df68NpotUmDlE9qnij73+enIAAAASAAAAAbrCs4s4F0bFFiiUEa6VhwFhv8ro0OiwU/6TA5YxIgHo\",      \"AAAAAQAAAAAAAAAB3+uRtuHcrdavl7t8RV+X83R3Cn949pVzuZ9dW/hJznwAAAABAAAAAAAAAAEAAAAPAAAABGluaXQAAAAQAAAAAQAAAAUAAAAFAAAAAAAGkZkAAAASAAAAAAAAAAAnDGNhr4xd4oCt9ulsolPIb9dO6fWMbwEtvYLdX5pdlgAAABIAAAAAAAAAAMnsMF5O5aqcYkDWD91ZWdRBKazSvnUgVeCkPYpbsG8fAAAAEgAAAAAAAAAAGNkZtSF153RCNK2INm7df68NpotUmDlE9qnij73+enIAAAASAAAAAbrCs4s4F0bFFiiUEa6VhwFhv8ro0OiwU/6TA5YxIgHo\",      \"AAAAAQAAAAAAAAAB3+uRtuHcrdavl7t8RV+X83R3Cn949pVzuZ9dW/hJznwAAAACAAAAAAAAAAIAAAAPAAAACWZuX3JldHVybgAAAAAAAA8AAAANY3JlYXRlX2VzY3JvdwAAAAAAAAE=\",      \"AAAAAAAAAAAAAAAAAAAAAgAAAAAAAAACAAAADwAAAAxjb3JlX21ldHJpY3MAAAAPAAAACnJlYWRfZW50cnkAAAAAAAUAAAAAAAAAAw==\",      \"AAAAAAAAAAAAAAAAAAAAAgAAAAAAAAACAAAADwAAAAxjb3JlX21ldHJpY3MAAAAPAAAAC3dyaXRlX2VudHJ5AAAAAAUAAAAAAAAAAQ==\",      \"AAAAAAAAAAAAAAAAAAAAAgAAAAAAAAACAAAADwAAAAxjb3JlX21ldHJpY3MAAAAPAAAAEGxlZGdlcl9yZWFkX2J5dGUAAAAFAAAAAAAAQLQ=\",      \"AAAAAAAAAAAAAAAAAAAAAgAAAAAAAAACAAAADwAAAAxjb3JlX21ldHJpY3MAAAAPAAAAEWxlZGdlcl93cml0ZV9ieXRlAAAAAAAABQAAAAAAAAG4\",      \"AAAAAAAAAAAAAAAAAAAAAgAAAAAAAAACAAAADwAAAAxjb3JlX21ldHJpY3MAAAAPAAAADXJlYWRfa2V5X2J5dGUAAAAAAAAFAAAAAAAAAKg=\",      \"AAAAAAAAAAAAAAAAAAAAAgAAAAAAAAACAAAADwAAAAxjb3JlX21ldHJpY3MAAAAPAAAADndyaXRlX2tleV9ieXRlAAAAAAAFAAAAAAAAAAA=\",      \"AAAAAAAAAAAAAAAAAAAAAgAAAAAAAAACAAAADwAAAAxjb3JlX21ldHJpY3MAAAAPAAAADnJlYWRfZGF0YV9ieXRlAAAAAAAFAAAAAAAAAGg=\",      \"AAAAAAAAAAAAAAAAAAAAAgAAAAAAAAACAAAADwAAAAxjb3JlX21ldHJpY3MAAAAPAAAAD3dyaXRlX2RhdGFfYnl0ZQAAAAAFAAAAAAAAAbg=\",      \"AAAAAAAAAAAAAAAAAAAAAgAAAAAAAAACAAAADwAAAAxjb3JlX21ldHJpY3MAAAAPAAAADnJlYWRfY29kZV9ieXRlAAAAAAAFAAAAAAAAQEw=\",      \"AAAAAAAAAAAAAAAAAAAAAgAAAAAAAAACAAAADwAAAAxjb3JlX21ldHJpY3MAAAAPAAAAD3dyaXRlX2NvZGVfYnl0ZQAAAAAFAAAAAAAAAAA=\",      \"AAAAAAAAAAAAAAAAAAAAAgAAAAAAAAACAAAADwAAAAxjb3JlX21ldHJpY3MAAAAPAAAACmVtaXRfZXZlbnQAAAAAAAUAAAAAAAAAAQ==\",      \"AAAAAAAAAAAAAAAAAAAAAgAAAAAAAAACAAAADwAAAAxjb3JlX21ldHJpY3MAAAAPAAAAD2VtaXRfZXZlbnRfYnl0ZQAAAAAFAAAAAAAAAQQ=\",      \"AAAAAAAAAAAAAAAAAAAAAgAAAAAAAAACAAAADwAAAAxjb3JlX21ldHJpY3MAAAAPAAAACGNwdV9pbnNuAAAABQAAAAAAM2BC\",      \"AAAAAAAAAAAAAAAAAAAAAgAAAAAAAAACAAAADwAAAAxjb3JlX21ldHJpY3MAAAAPAAAACG1lbV9ieXRlAAAABQAAAAAAGwer\",      \"AAAAAAAAAAAAAAAAAAAAAgAAAAAAAAACAAAADwAAAAxjb3JlX21ldHJpY3MAAAAPAAAAEWludm9rZV90aW1lX25zZWNzAAAAAAAABQAAAAAACD99\",      \"AAAAAAAAAAAAAAAAAAAAAgAAAAAAAAACAAAADwAAAAxjb3JlX21ldHJpY3MAAAAPAAAAD21heF9yd19rZXlfYnl0ZQAAAAAFAAAAAAAAAFQ=\",      \"AAAAAAAAAAAAAAAAAAAAAgAAAAAAAAACAAAADwAAAAxjb3JlX21ldHJpY3MAAAAPAAAAEG1heF9yd19kYXRhX2J5dGUAAAAFAAAAAAAAAbg=\",      \"AAAAAAAAAAAAAAAAAAAAAgAAAAAAAAACAAAADwAAAAxjb3JlX21ldHJpY3MAAAAPAAAAEG1heF9yd19jb2RlX2J5dGUAAAAFAAAAAAAAQEw=\",      \"AAAAAAAAAAAAAAAAAAAAAgAAAAAAAAACAAAADwAAAAxjb3JlX21ldHJpY3MAAAAPAAAAE21heF9lbWl0X2V2ZW50X2J5dGUAAAAABQAAAAAAAAEE\",      \"AAAAAQAAAAAAAAAB15KLcsJwPM/q9+uf9O9NUEpVqLl5/JtFDqLIQrTRzmEAAAABAAAAAAAAAAIAAAAPAAAAA2ZlZQAAAAASAAAAAAAAAADte5nJrehJq/pu3qlV/bASRSOiJVXdNC+gQW/nxVNWuQAAAAoAAAAAAAAAAAAAAAAAWOuO\",      \"AAAAAQAAAAAAAAAB15KLcsJwPM/q9+uf9O9NUEpVqLl5/JtFDqLIQrTRzmEAAAABAAAAAAAAAAIAAAAPAAAAA2ZlZQAAAAASAAAAAAAAAADte5nJrehJq/pu3qlV/bASRSOiJVXdNC+gQW/nxVNWuQAAAAr/////////////////8/qT\"    ],    \"events\": {      \"transactionEventsXdr\": [        \"AAAAAAAAAAAAAAAB15KLcsJwPM/q9+uf9O9NUEpVqLl5/JtFDqLIQrTRzmEAAAABAAAAAAAAAAIAAAAPAAAAA2ZlZQAAAAASAAAAAAAAAADte5nJrehJq/pu3qlV/bASRSOiJVXdNC+gQW/nxVNWuQAAAAoAAAAAAAAAAAAAAAAAWOuO\",        \"AAAAAQAAAAAAAAAB15KLcsJwPM/q9+uf9O9NUEpVqLl5/JtFDqLIQrTRzmEAAAABAAAAAAAAAAIAAAAPAAAAA2ZlZQAAAAASAAAAAAAAAADte5nJrehJq/pu3qlV/bASRSOiJVXdNC+gQW/nxVNWuQAAAAr/////////////////8/qT\"      ],      \"contractEventsXdr\": [        [          \"AAAAAAAAAAHf65G24dyt1q+Xu3xFX5fzdHcKf3j2lXO5n11b+EnOfAAAAAEAAAAAAAAAAQAAAA8AAAAEaW5pdAAAABAAAAABAAAABQAAAAUAAAAAAAaRmQAAABIAAAAAAAAAACcMY2GvjF3igK326WyiU8hv107p9YxvAS29gt1fml2WAAAAEgAAAAAAAAAAyewwXk7lqpxiQNYP3VlZ1EEprNK+dSBV4KQ9iluwbx8AAAASAAAAAAAAAAAY2Rm1IXXndEI0rYg2bt1/rw2mi1SYOUT2qeKPvf56cgAAABIAAAABusKzizgXRsUWKJQRrpWHAWG/yujQ6LBT/pMDljEiAeg=\",           \"AAAAAAAAAAHf65G24dyt1q+Xu3xFX5fzdHcKf3j2lXO5n11b+EnOfAAAAAEAAAAAAAAAAQAAAA8AAAAEaW5pdAAAABAAAAABAAAABQAAAAUAAAAAAAaRmQAAABIAAAAAAAAAACcMY2GvjF3igK326WyiU8hv107p9YxvAS29gt1fml2WAAAAEgAAAAAAAAAAyewwXk7lqpxiQNYP3VlZ1EEprNK+dSBV4KQ9iluwbx8AAAASAAAAAAAAAAAY2Rm1IXXndEI0rYg2bt1/rw2mi1SYOUT2qeKPvf56cgAAABIAAAABusKzizgXRsUWKJQRrpWHAWG/yujQ6LBT/pMDljEiAeg=\"        ],        [          \"AAAAAAAAAAHf65G24dyt1q+Xu3xFX5fzdHcKf3j2lXO5n11b+EnOfAAAAAEAAAAAAAAAAQAAAA8AAAAEaW5pdAAAABAAAAABAAAABQAAAAUAAAAAAAaRmQAAABIAAAAAAAAAACcMY2GvjF3igK326WyiU8hv107p9YxvAS29gt1fml2WAAAAEgAAAAAAAAAAyewwXk7lqpxiQNYP3VlZ1EEprNK+dSBV4KQ9iluwbx8AAAASAAAAAAAAAAAY2Rm1IXXndEI0rYg2bt1/rw2mi1SYOUT2qeKPvf56cgAAABIAAAABusKzizgXRsUWKJQRrpWHAWG/yujQ6LBT/pMDljEiAeg=\",           \"AAAAAAAAAAHf65G24dyt1q+Xu3xFX5fzdHcKf3j2lXO5n11b+EnOfAAAAAEAAAAAAAAAAQAAAA8AAAAEaW5pdAAAABAAAAABAAAABQAAAAUAAAAAAAaRmQAAABIAAAAAAAAAACcMY2GvjF3igK326WyiU8hv107p9YxvAS29gt1fml2WAAAAEgAAAAAAAAAAyewwXk7lqpxiQNYP3VlZ1EEprNK+dSBV4KQ9iluwbx8AAAASAAAAAAAAAAAY2Rm1IXXndEI0rYg2bt1/rw2mi1SYOUT2qeKPvf56cgAAABIAAAABusKzizgXRsUWKJQRrpWHAWG/yujQ6LBT/pMDljEiAeg=\"        ]      ]    },    \"ledger\": 1303424,    \"createdAt\": \"1748928444\"  }}";
        $jsonData = @json_decode($body, true);
        $txResponse = GetTransactionResponse::fromJson($jsonData);

        // diagnosticEventsXdr is now at the top level of GetTransactionResponse, not in events
        $this->assertCount(24, $txResponse->diagnosticEventsXdr);
        $this->assertCount(2, $txResponse->events->transactionEventsXdr);
        $this->assertCount(2, $txResponse->events->contractEventsXdr);
        $this->assertCount(2, $txResponse->events->contractEventsXdr[0]);

        foreach ($txResponse->diagnosticEventsXdr as $eventXdr) {
            XdrDiagnosticEvent::fromBase64Xdr($eventXdr);
        }
        foreach ($txResponse->events->transactionEventsXdr as $eventXdr) {
            XdrTransactionEvent::fromBase64Xdr($eventXdr);
        }
        foreach ($txResponse->events->contractEventsXdr as $eventsList) {
            foreach ($eventsList as $eventXdr) {
                XdrContractEvent::fromBase64Xdr($eventXdr);
            }
        }
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
            $statusResponse = $this->server->getTransaction($transactionId);
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
        $simulateResponse = $this->server->simulateTransaction($request);


        // set the transaction data + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->sign($submitterKp, $this->network);

        // send the transaction
        $sendResponse = $this->server->sendTransaction($transaction);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($this->server, $sendResponse->hash);
        $this->assertNotNull($statusResponse);
        $wasmId = $statusResponse->getWasmId();
        $this->assertNotNull($wasmId);

        $this->bumpContractCodeFootprint($this->server, $submitterKp, $wasmId, 100000);

        // create contract
        $createContractHostFunction = new CreateContractHostFunction(Address::fromAccountId($submitterId), $wasmId);
        $builder = new InvokeHostFunctionOperationBuilder($createContractHostFunction);
        $op = $builder->build();

        sleep(5);
        $account = $this->server->getAccount($submitterId);
        assertNotNull($account);

        $transaction = (new TransactionBuilder($account))
            ->addOperation($op)->build();

        // simulate first to get the transaction data and resource fee
        $request = new SimulateTransactionRequest($transaction);
        $simulateResponse = $this->server->simulateTransaction($request);

        // set the transaction data + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->setSorobanAuth($simulateResponse->getSorobanAuth());
        $transaction->sign($submitterKp, $this->network);

        // send the transaction
        $sendResponse = $this->server->sendTransaction($transaction);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($this->server, $sendResponse->hash);
        $this->assertNotNull($statusResponse);
        $contractId = $statusResponse->getCreatedContractId();
        $this->assertNotNull($contractId);
        return $contractId;
    }

    private function restoreContractFootprint(SorobanServer $server, KeyPair $accountKeyPair, string $contractCodePath) : void {
        sleep(5);

        $contractCode = file_get_contents($contractCodePath, false);
        $uploadContractHostFunction = new UploadContractWasmHostFunction($contractCode);
        $op = (new InvokeHostFunctionOperationBuilder($uploadContractHostFunction))->build();

        $this->accountAId = $accountKeyPair->getAccountId();
        //$account = $this->sdk->requestAccount($this->accountAId);
        $account = $this->server->getAccount($this->accountAId);
        assertNotNull($account);

        $transaction = (new TransactionBuilder($account))
            ->addOperation($op)->build();

        $request = new SimulateTransactionRequest($transaction);
        $simulateResponse = $this->server->simulateTransaction($request);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);
        $this->assertNotNull($simulateResponse->getTransactionData());

        $transactionData = $simulateResponse->getTransactionData();
        $transactionData->resources->footprint->readWrite = $transactionData->resources->footprint->readWrite + $transactionData->resources->footprint->readOnly;
        $transactionData->resources->footprint->readOnly = array();

        $account = $this->server->getAccount($this->accountAId);
        assertNotNull($account);
        $restoreOp = (new RestoreFootprintOperationBuilder())->build();
        $transaction = (new TransactionBuilder($account))
            ->addOperation($restoreOp)->build();

        $transaction->setSorobanTransactionData($transactionData) ;
        $request = new SimulateTransactionRequest($transaction);
        $simulateResponse = $this->server->simulateTransaction($request);

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
        $sendResponse = $this->server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNotNull($sendResponse->hash);
        $this->assertNotNull($sendResponse->status);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($this->server, $sendResponse->hash);
        $this->assertNotNull($statusResponse);
        $this->assertEquals(GetTransactionResponse::STATUS_SUCCESS, $statusResponse->status);
    }

    private function bumpContractCodeFootprint(SorobanServer $server, KeyPair $accountKeyPair, string $wasmId, int $extendTo) : void {
        sleep(5);

        $builder = new ExtendFootprintTTLOperationBuilder($extendTo);
        $bumpOp = $builder->build();

        $this->accountAId = $accountKeyPair->getAccountId();
        $account = $this->server->getAccount($this->accountAId);
        assertNotNull($account);
        $transaction = (new TransactionBuilder($account))
            ->addOperation($bumpOp)->build();

        $readOnly = array();
        $readWrite = array();
        $codeKey = new XdrLedgerKey(XdrLedgerEntryType::CONTRACT_CODE());
        $codeKey->contractCode = new XdrLedgerKeyContractCode(hex2bin($wasmId));
        array_push($readOnly, $codeKey);

        $footprint = new XdrLedgerFootprint($readOnly, $readWrite);
        $resources = new XdrSorobanResources($footprint, 0,0,0);
        $transactionData = new XdrSorobanTransactionData(new XdrSorobanTransactionDataExt(0), $resources, 0);

        $transaction->setSorobanTransactionData($transactionData) ;
        //$resourceConfig = new ResourceConfig(4000000);
        //$request = new SimulateTransactionRequest($transaction, $resourceConfig);
        $request = new SimulateTransactionRequest($transaction);
        $simulateResponse = $this->server->simulateTransaction($request);

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
        $sendResponse = $this->server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNotNull($sendResponse->hash);
        $this->assertNotNull($sendResponse->status);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($this->server, $sendResponse->hash);
        $this->assertNotNull($statusResponse);
        $this->assertEquals(GetTransactionResponse::STATUS_SUCCESS, $statusResponse->status);
    }

    public function testGetLedgers(): void
    {
        // Get the latest ledger first
        $latestLedgerResponse = $this->server->getLatestLedger();
        $this->assertNotNull($latestLedgerResponse);
        $this->assertNotNull($latestLedgerResponse->sequence);
        $this->assertNull($latestLedgerResponse->error);

        // Assert new fields added in RPC v25.0.0
        $this->assertNotNull($latestLedgerResponse->closeTime, 'closeTime should not be null');
        $this->assertIsInt($latestLedgerResponse->closeTime, 'closeTime should be an integer');
        $this->assertGreaterThan(0, $latestLedgerResponse->closeTime, 'closeTime should be a valid Unix timestamp');

        $this->assertNotNull($latestLedgerResponse->headerXdr, 'headerXdr should not be null');
        $this->assertIsString($latestLedgerResponse->headerXdr, 'headerXdr should be a string');
        $this->assertNotEmpty($latestLedgerResponse->headerXdr, 'headerXdr should not be empty');

        $this->assertNotNull($latestLedgerResponse->metadataXdr, 'metadataXdr should not be null');
        $this->assertIsString($latestLedgerResponse->metadataXdr, 'metadataXdr should be a string');
        $this->assertNotEmpty($latestLedgerResponse->metadataXdr, 'metadataXdr should not be empty');

        // Calculate a start ledger a few ledgers back
        $startLedger = $latestLedgerResponse->sequence - 10;

        // Test basic getLedgers request with pagination limit
        $paginationOptions = new PaginationOptions(limit: 3);
        $request = new GetLedgersRequest(
            startLedger: $startLedger,
            paginationOptions: $paginationOptions
        );
        $response = $this->server->getLedgers($request);

        // Assert response is not null and has no error
        $this->assertNotNull($response);
        $this->assertNull($response->error);

        // Assert ledgers array is not empty and respects limit
        $this->assertNotNull($response->ledgers);
        $this->assertIsArray($response->ledgers);
        $this->assertGreaterThan(0, count($response->ledgers));
        $this->assertLessThanOrEqual(3, count($response->ledgers));

        // Assert all required fields are populated
        $this->assertNotNull($response->latestLedger);
        $this->assertGreaterThan(0, $response->latestLedger);
        $this->assertNotNull($response->latestLedgerCloseTime);
        $this->assertGreaterThan(0, $response->latestLedgerCloseTime);
        $this->assertNotNull($response->oldestLedger);
        $this->assertGreaterThan(0, $response->oldestLedger);
        $this->assertNotNull($response->oldestLedgerCloseTime);
        $this->assertGreaterThan(0, $response->oldestLedgerCloseTime);
        $this->assertNotNull($response->cursor);

        // Assert each LedgerInfo has required fields
        foreach ($response->ledgers as $ledgerInfo) {
            $this->assertNotNull($ledgerInfo->hash);
            $this->assertIsString($ledgerInfo->hash);
            $this->assertGreaterThan(0, strlen($ledgerInfo->hash));

            $this->assertNotNull($ledgerInfo->sequence);
            $this->assertGreaterThan(0, $ledgerInfo->sequence);

            $this->assertNotNull($ledgerInfo->ledgerCloseTime);
            $this->assertIsString($ledgerInfo->ledgerCloseTime);
            $this->assertGreaterThan(0, strlen($ledgerInfo->ledgerCloseTime));
        }

        // Test pagination by using cursor from first response
        // Only test if cursor is not at the latest ledger (to avoid boundary errors)
        if ($response->cursor !== null && intval($response->cursor) < $response->latestLedger) {
            $paginationOptions = new PaginationOptions(
                cursor: $response->cursor,
                limit: 2
            );
            $paginatedRequest = new \Soneso\StellarSDK\Soroban\Requests\GetLedgersRequest(
                paginationOptions: $paginationOptions
            );
            $paginatedResponse = $this->server->getLedgers($paginatedRequest);

            // Assert paginated response
            $this->assertNotNull($paginatedResponse);
            $this->assertNull($paginatedResponse->error);
            $this->assertNotNull($paginatedResponse->ledgers);
            $this->assertGreaterThan(0, count($paginatedResponse->ledgers));
            $this->assertLessThanOrEqual(2, count($paginatedResponse->ledgers));
        }
    }
}