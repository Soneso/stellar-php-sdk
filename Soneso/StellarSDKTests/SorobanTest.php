<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\Responses\Operations\InvokeHostFunctionOperationResponse;
use Soneso\StellarSDK\Soroban\GetHealthResponse;
use Soneso\StellarSDK\Soroban\GetTransactionStatusResponse;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Util\FuturenetFriendBot;
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
        $server = new SorobanServer("https://futurenet.sorobandev.com/soroban/rpc");
        $server->enableLogging = true;
        $server->acknowledgeExperimental = true;
        $sdk = StellarSDK::getFutureNetInstance();

        $accountAKeyPair = KeyPair::random();
        $accountAId = $accountAKeyPair->getAccountId();

        // get health
        $getHealthResponse = $server->getHealth();
        $this->assertEquals(GetHealthResponse::HEALTHY, $getHealthResponse->status);

        // get account
        $getAccountResponse = $server->getAccount($accountAId);
        $this->assertEquals(-32600, $getAccountResponse->error->code); // acc not found

        FuturenetFriendBot::fundTestAccount($accountAId);

        $getAccountResponse = $server->getAccount($accountAId);
        $this->assertEquals($accountAId, $getAccountResponse->id);
        $this->assertNotNull($getAccountResponse->sequence);

        // install contract
        $contractCode = file_get_contents('./wasm/hello.wasm', false);
        $installContractOp = InvokeHostFunctionOperationBuilder::
            forInstallingContractCode($contractCode)->build();

        $accountA = $sdk->requestAccount($accountAId);

        $transaction = (new TransactionBuilder($accountA))
            ->addOperation($installContractOp)->build();

        // simulate first to get the footprint
        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNotNull($simulateResponse->footprint);
        $this->assertNotNull($simulateResponse->results);
        $this->assertNotNull($simulateResponse->latestLedger);
        $this->assertEquals(1, $simulateResponse->results->count());
        $this->assertGreaterThan(1, intval($simulateResponse->cost->cpuInsns));
        $this->assertGreaterThan(1, intval($simulateResponse->cost->memBytes));

        // set the footprint and sign
        $transaction->setFootprint($simulateResponse->footprint);
        $transaction->sign($accountAKeyPair, Network::futurenet());

        // check transaction xdr encoding back and forth
        $transctionEnvelopeXdr = $transaction->toEnvelopeXdrBase64();
        $this->assertEquals($transctionEnvelopeXdr,
            Transaction::fromEnvelopeBase64XdrString($transctionEnvelopeXdr)->toEnvelopeXdrBase64());

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNull($sendResponse->resultError);
        $this->assertNotNull($sendResponse->transactionId);
        $this->assertNotNull($sendResponse->status);
        if ($sendResponse->error == null) {
            print("Transaction Id: ".$sendResponse->transactionId);
            print("Status: ".$sendResponse->status); // pending
        }
        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->transactionId);
        $this->assertNotNull($statusResponse);
        $helloContractWasmId = $statusResponse->getWasmId();
        $this->assertNotNull($helloContractWasmId);

        // check horizon response decoding.
        $transactionResponse = $sdk->requestTransaction($sendResponse->transactionId);
        $this->assertEquals(1, $transactionResponse->getOperationCount());
        $this->assertEquals($transctionEnvelopeXdr, $transactionResponse->getEnvelopeXdr()->toBase64Xdr());
        $meta = $transactionResponse->getResultMetaXdrBase64();

        // parsing meta is working
        $metaXdr = XdrTransactionMeta::fromBase64Xdr($meta);
        $this->assertEquals($meta, $metaXdr->toBase64Xdr());

        // check horizon operation response
        $operationsResponse = $sdk->operations()->forTransaction($sendResponse->transactionId)->limit(10)->order("desc")->execute();
        $this->assertTrue($operationsResponse->getOperations()->count() == 1);
        $firstOp = $operationsResponse->getOperations()->toArray()[0];
        if ($firstOp instanceof InvokeHostFunctionOperationResponse) {
            $this->assertEquals($firstOp->getFootprint(), $simulateResponse->getFootprint()->toBase64Xdr());
        } else {
            $this->fail();
        }

        // create contract
        $createContractOp = InvokeHostFunctionOperationBuilder::forCreatingContract($helloContractWasmId)->build();
        $accountA = $sdk->requestAccount($accountAId);

        $transaction = (new TransactionBuilder($accountA))
            ->addOperation($createContractOp)->build();

        // simulate first to get the footprint
        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNotNull($simulateResponse->footprint);
        $this->assertNotNull($simulateResponse->results);
        $this->assertNotNull($simulateResponse->latestLedger);
        $this->assertEquals(1, $simulateResponse->results->count());
        $this->assertGreaterThan(1, intval($simulateResponse->cost->cpuInsns));
        $this->assertGreaterThan(1, intval($simulateResponse->cost->memBytes));

        // set the footprint and sign
        $transaction->setFootprint($simulateResponse->footprint);
        $transaction->sign($accountAKeyPair, Network::futurenet());

        // check transaction xdr encoding back and forth
        $transctionEnvelopeXdr = $transaction->toEnvelopeXdrBase64();
        $this->assertEquals($transctionEnvelopeXdr,
            Transaction::fromEnvelopeBase64XdrString($transctionEnvelopeXdr)->toEnvelopeXdrBase64());

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNull($sendResponse->resultError);
        $this->assertNotNull($sendResponse->transactionId);
        $this->assertNotNull($sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->transactionId);
        $this->assertNotNull($statusResponse);
        $helloContractId = $statusResponse->getContractId();
        $this->assertNotNull($helloContractId);

        // check horizon response decoding.
        $transactionResponse = $sdk->requestTransaction($sendResponse->transactionId);
        $this->assertEquals(1, $transactionResponse->getOperationCount());
        $this->assertEquals($transctionEnvelopeXdr, $transactionResponse->getEnvelopeXdr()->toBase64Xdr());
        $meta = $transactionResponse->getResultMetaXdrBase64();

        // parsing meta is working
        $metaXdr = XdrTransactionMeta::fromBase64Xdr($meta);

        // check horizon operation response
        $operationsResponse = $sdk->operations()->forTransaction($sendResponse->transactionId)->limit(10)->order("desc")->execute();
        $this->assertTrue($operationsResponse->getOperations()->count() == 1);
        $firstOp = $operationsResponse->getOperations()->toArray()[0];
        if ($firstOp instanceof InvokeHostFunctionOperationResponse) {
            $this->assertEquals($firstOp->getFootprint(), $simulateResponse->getFootprint()->toBase64Xdr());
        } else {
            $this->fail();
        }

        // test get ledger entry
        $footprint = $simulateResponse->footprint;
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
        $argVal = XdrSCVal::fromSymbol("friend");
        $invokeContractOp = InvokeHostFunctionOperationBuilder::forInvokingContract($helloContractId, "hello", [$argVal])->build();

        // simulate first to obtain the footprint
        $accountA = $sdk->requestAccount($accountAId);
        $transaction = (new TransactionBuilder($accountA))
            ->addOperation($invokeContractOp)->build();

        // simulate first to get the footprint
        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNotNull($simulateResponse->footprint);
        $this->assertNotNull($simulateResponse->results);
        $this->assertNotNull($simulateResponse->latestLedger);
        $this->assertEquals(1, $simulateResponse->results->count());
        $this->assertGreaterThan(1, intval($simulateResponse->cost->cpuInsns));
        $this->assertGreaterThan(1, intval($simulateResponse->cost->memBytes));

        // set the footprint and sign
        $transaction->setFootprint($simulateResponse->footprint);
        $transaction->sign($accountAKeyPair, Network::futurenet());

        // check transaction xdr encoding back and forth
        $transctionEnvelopeXdr = $transaction->toEnvelopeXdrBase64();
        $this->assertEquals($transctionEnvelopeXdr,
            Transaction::fromEnvelopeBase64XdrString($transctionEnvelopeXdr)->toEnvelopeXdrBase64());

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNull($sendResponse->resultError);
        $this->assertNotNull($sendResponse->transactionId);
        $this->assertNotNull($sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->transactionId);
        $this->assertNotNull($statusResponse);
        $this->assertNotNull($statusResponse->results);
        foreach ($statusResponse->results as $result) {
            $val = XdrSCVal::fromBase64Xdr($result->xdr);
            $resVec = $val->obj?->vec;
            $this->assertNotNull($resVec);
            foreach ($resVec as $symVal) {
                print($symVal->sym . PHP_EOL);
            }
        }

        // user friendly
        $resVal = $statusResponse->getResultValue();
        $vec = $resVal->getVec();
        if ($vec != null && count($vec) > 1) {
            print("[".$vec[0]->sym.", ".$vec[1]->sym."]".PHP_EOL);
        }

        // check horizon response decoding.
        $transactionResponse = $sdk->requestTransaction($sendResponse->transactionId);
        $this->assertEquals(1, $transactionResponse->getOperationCount());
        $this->assertEquals($transctionEnvelopeXdr, $transactionResponse->getEnvelopeXdr()->toBase64Xdr());
        $meta = $transactionResponse->getResultMetaXdrBase64();

        // parsing meta is working
        $metaXdr = XdrTransactionMeta::fromBase64Xdr($meta);
        $this->assertEquals($meta, $metaXdr->toBase64Xdr());

        // check horizon operation response
        $operationsResponse = $sdk->operations()->forTransaction($sendResponse->transactionId)->limit(10)->order("desc")->execute();
        $this->assertTrue($operationsResponse->getOperations()->count() == 1);
        $firstOp = $operationsResponse->getOperations()->toArray()[0];
        if ($firstOp instanceof InvokeHostFunctionOperationResponse) {
            $this->assertEquals($firstOp->getFootprint(), $simulateResponse->getFootprint()->toBase64Xdr());
            $this->assertNotNull($firstOp->getParameters());
            $this->assertEquals(3, $firstOp->getParameters()->count());
            foreach ($firstOp->getParameters() as $parameter) {
                $this->assertNotEquals("", trim($parameter->type));
                $this->assertNotEquals("", trim($parameter->value));
                //print("Parameter type :" . $parameter->type . " value: " . $parameter->value . PHP_EOL);
            }
        } else {
            $this->fail();
        }

        // deploy create token contract with source account
        $deployCTContractSAOp = InvokeHostFunctionOperationBuilder::forDeploySACWithSourceAccount()->build();

        // simulate first to obtain the footprint
        $accountA = $sdk->requestAccount($accountAId);
        $transaction = (new TransactionBuilder($accountA))
            ->addOperation($deployCTContractSAOp)->build();

        // simulate first to get the footprint
        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNotNull($simulateResponse->footprint);
        $this->assertNotNull($simulateResponse->results);
        $this->assertNotNull($simulateResponse->latestLedger);
        $this->assertEquals(1, $simulateResponse->results->count());
        $this->assertGreaterThan(1, intval($simulateResponse->cost->cpuInsns));
        $this->assertGreaterThan(1, intval($simulateResponse->cost->memBytes));

        // set the footprint and sign
        $transaction->setFootprint($simulateResponse->footprint);
        $transaction->sign($accountAKeyPair, Network::futurenet());

        // check transaction xdr encoding back and forth
        $transctionEnvelopeXdr = $transaction->toEnvelopeXdrBase64();
        $this->assertEquals($transctionEnvelopeXdr,
            Transaction::fromEnvelopeBase64XdrString($transctionEnvelopeXdr)->toEnvelopeXdrBase64());

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNull($sendResponse->resultError);
        $this->assertNotNull($sendResponse->transactionId);
        $this->assertNotNull($sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->transactionId);
        $this->assertNotNull($statusResponse);
        $this->assertNotNull($statusResponse->results);
        $ctcId = $statusResponse->getContractId();
        $this->assertNotNull($ctcId);

        // check horizon response decoding.
        $transactionResponse = $sdk->requestTransaction($sendResponse->transactionId);
        $this->assertEquals(1, $transactionResponse->getOperationCount());
        $this->assertEquals($transctionEnvelopeXdr, $transactionResponse->getEnvelopeXdr()->toBase64Xdr());
        $meta = $transactionResponse->getResultMetaXdrBase64();

        // parsing meta is working
        $metaXdr = XdrTransactionMeta::fromBase64Xdr($meta);
        $this->assertEquals($meta, $metaXdr->toBase64Xdr());

        // check horizon operation response
        $operationsResponse = $sdk->operations()->forTransaction($sendResponse->transactionId)->limit(10)->order("desc")->execute();
        $this->assertTrue($operationsResponse->getOperations()->count() == 1);
        $firstOp = $operationsResponse->getOperations()->toArray()[0];
        if ($firstOp instanceof InvokeHostFunctionOperationResponse) {
            $this->assertEquals($firstOp->getFootprint(), $simulateResponse->getFootprint()->toBase64Xdr());
        } else {
            $this->fail();
        }

        // test deploy create token contract with asset

        // prepare account and asset
        $accountBKeyPair = KeyPair::random();
        $accountBId = $accountBKeyPair->getAccountId();
        FuturenetFriendBot::fundTestAccount($accountBId);
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
        $accountB = $sdk->requestAccount($accountBId);
        $deployCTContractAssetOp = InvokeHostFunctionOperationBuilder::forDeploySACWithAsset($iomAsset)->build();
        $transaction = (new TransactionBuilder($accountB))
            ->addOperation($deployCTContractAssetOp)
            ->build();
        // simulate first to get the footprint
        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNotNull($simulateResponse->footprint);
        $this->assertNotNull($simulateResponse->results);
        $this->assertNotNull($simulateResponse->latestLedger);
        $this->assertEquals(1, $simulateResponse->results->count());
        $this->assertGreaterThan(1, intval($simulateResponse->cost->cpuInsns));
        $this->assertGreaterThan(1, intval($simulateResponse->cost->memBytes));

        // set the footprint and sign
        $transaction->setFootprint($simulateResponse->footprint);
        $transaction->sign($accountBKeyPair, Network::futurenet());

        // check transaction xdr encoding back and forth
        $transctionEnvelopeXdr = $transaction->toEnvelopeXdrBase64();
        $this->assertEquals($transctionEnvelopeXdr,
            Transaction::fromEnvelopeBase64XdrString($transctionEnvelopeXdr)->toEnvelopeXdrBase64());

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNull($sendResponse->resultError);
        $this->assertNotNull($sendResponse->transactionId);
        $this->assertNotNull($sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->transactionId);
        $this->assertNotNull($statusResponse);
        $this->assertNotNull($statusResponse->results);
        $ctcId = $statusResponse->getContractId();
        $this->assertNotNull($ctcId);

        // check horizon response decoding.
        $transactionResponse = $sdk->requestTransaction($sendResponse->transactionId);
        $this->assertEquals(1, $transactionResponse->getOperationCount());
        $this->assertEquals($transctionEnvelopeXdr, $transactionResponse->getEnvelopeXdr()->toBase64Xdr());
        $meta = $transactionResponse->getResultMetaXdrBase64();

        // parsing meta is working
        $metaXdr = XdrTransactionMeta::fromBase64Xdr($meta);
        $this->assertEquals($meta, $metaXdr->toBase64Xdr());

        // check horizon operation response
        $operationsResponse = $sdk->operations()->forTransaction($sendResponse->transactionId)->limit(10)->order("desc")->execute();
        $this->assertTrue($operationsResponse->getOperations()->count() == 1);
        $firstOp = $operationsResponse->getOperations()->toArray()[0];
        if ($firstOp instanceof InvokeHostFunctionOperationResponse) {
            $this->assertEquals($firstOp->getFootprint(), $simulateResponse->getFootprint()->toBase64Xdr());
        } else {
            $this->fail();
        }
    }

    private function pollStatus(SorobanServer $server, string $transactionId) : ?GetTransactionStatusResponse {
        $statusResponse = null;
        $status = GetTransactionStatusResponse::STATUS_PENDING;
        while ($status == GetTransactionStatusResponse::STATUS_PENDING) {
            sleep(3);
            $statusResponse = $server->getTransactionStatus($transactionId);
            $this->assertNull($statusResponse->error);
            $this->assertNotNull($statusResponse->id);
            $this->assertNotNull($statusResponse->status);
            $status = $statusResponse->status;
            if ($status == GetTransactionStatusResponse::STATUS_ERROR) {
                $this->assertNotNull($statusResponse->resultError);
                print($statusResponse->resultError->message . PHP_EOL);
            } else if ($status == GetTransactionStatusResponse::STATUS_SUCCESS) {
                $this->assertNotNull($statusResponse->results);
            }
        }
        return $statusResponse;
    }
}