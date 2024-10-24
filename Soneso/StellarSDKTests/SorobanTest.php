<?php  declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

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
use Soneso\StellarSDK\DeploySACWithSourceAccountHostFunction;
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\Responses\Operations\InvokeHostFunctionOperationResponse;
use Soneso\StellarSDK\RestoreFootprintOperationBuilder;
use Soneso\StellarSDK\Soroban\Address;
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
use function PHPUnit\Framework\assertNotNull;

class SorobanTest extends TestCase
{

    const HELLO_CONTRACT_PATH = './wasm/soroban_hello_world_contract.wasm';
    const EVENTS_CONTRACT_PATH = './wasm/soroban_events_contract.wasm';

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
        assertNotNull($latestLedgerResponse->sequence);
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

        $this->restoreContractFootprint($this->server, $this->accountAKeyPair, self::HELLO_CONTRACT_PATH);

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
        $metaXdr = XdrTransactionMeta::fromBase64Xdr($meta);
        //$this->assertEquals($meta, $metaXdr->toBase64Xdr());

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
        $metaXdr = XdrTransactionMeta::fromBase64Xdr($meta);

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
        $metaXdr = XdrTransactionMeta::fromBase64Xdr($meta);
        //$this->assertEquals($meta, $metaXdr->toBase64Xdr());

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

        // deploy create token contract with source account
        $accountA = $this->server->getAccount($this->accountAId);
        assertNotNull($accountA);

        $deploySACWithSourceAccountHostFunction = new DeploySACWithSourceAccountHostFunction(Address::fromAccountId($this->accountAId));
        $builder = new InvokeHostFunctionOperationBuilder($deploySACWithSourceAccountHostFunction);
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
        $this->assertNotNull($simulateResponse->getSorobanAuth());
        $this->assertNotNull($simulateResponse->getFootprint());
        $this->assertGreaterThan(1, $simulateResponse->minResourceFee);
        if ($this->testOn === 'futurenet') {
            $this->assertNotNull($simulateResponse->stateChanges);
            $stateChange = $simulateResponse->stateChanges[0];
            $stateChange->getKeyXdr();
            $after = $stateChange->getAfterXdr();
            assertNotNull($after);
        }

        // set the transaction data and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
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
        $ctcId = $statusResponse->getCreatedContractId();
        $this->assertNotNull($ctcId);

        sleep(5);
        // check horizon response decoding.
        $transactionResponse = $this->sdk->requestTransaction($sendResponse->hash);
        $this->assertEquals(1, $transactionResponse->getOperationCount());
        $this->assertEquals($transctionEnvelopeXdr, $transactionResponse->getEnvelopeXdr()->toBase64Xdr());
        $meta = $transactionResponse->getResultMetaXdrBase64();

        // parsing meta is working
        $metaXdr = XdrTransactionMeta::fromBase64Xdr($meta);
        //$this->assertEquals($meta, $metaXdr->toBase64Xdr());

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
        $metaXdr = XdrTransactionMeta::fromBase64Xdr($meta);
        //$this->assertEquals($meta, $metaXdr->toBase64Xdr());

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
            filters: $eventFilters,
            paginationOptions: $paginationOptions,
        );
        $response = $this->server->getEvents($request);
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

        $this->restoreContractFootprint($this->server, $submitterKp, $pathToCode);

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
        $transactionData = new XdrSorobanTransactionData(new XdrExtensionPoint(0), $resources, 0);

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
}