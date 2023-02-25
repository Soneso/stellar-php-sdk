<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Responses\Operations\InvokeHostFunctionOperationResponse;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\AuthorizedInvocation;
use Soneso\StellarSDK\Soroban\ContractAuth;
use Soneso\StellarSDK\Soroban\Responses\GetHealthResponse;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionStatusResponse;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Util\FuturenetFriendBot;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrTransactionMeta;

class SorobanAuthTest extends TestCase
{

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function testSoroban(): void
    {
        $server = new SorobanServer("https://horizon-futurenet.stellar.cash/soroban/rpc");
        $server->enableLogging = true;
        $server->acknowledgeExperimental = true;
        $sdk = StellarSDK::getFutureNetInstance();

        $accountAKeyPair = KeyPair::random();
        $accountAId = $accountAKeyPair->getAccountId();
        $accountBKeyPair = KeyPair::random();
        $accountBId = $accountBKeyPair->getAccountId();

        // get health
        $getHealthResponse = $server->getHealth();
        $this->assertEquals(GetHealthResponse::HEALTHY, $getHealthResponse->status);

        FuturenetFriendBot::fundTestAccount($accountAId);
        FuturenetFriendBot::fundTestAccount($accountBId);
        sleep(5);

        $getAccountResponse = $server->getAccount($accountAId);
        $this->assertEquals($accountAId, $getAccountResponse->id);

        // install contract
        $contractCode = file_get_contents('./wasm/auth.wasm', false);
        $installContractOp = InvokeHostFunctionOperationBuilder::
        forInstallingContractCode($contractCode)->build();

        $transaction = (new TransactionBuilder($getAccountResponse))
            ->addOperation($installContractOp)->build();

        print($transaction->toEnvelopeXdrBase64() . PHP_EOL);

        // simulate first to get the footprint
        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);

        // set the footprint and sign
        $transaction->setFootprint($simulateResponse->getFootprint());
        $transaction->sign($accountAKeyPair, Network::futurenet());

        // check transaction xdr encoding back and forth
        $transctionEnvelopeXdr = $transaction->toEnvelopeXdrBase64();
        $this->assertEquals($transctionEnvelopeXdr,
            Transaction::fromEnvelopeBase64XdrString($transctionEnvelopeXdr)->toEnvelopeXdrBase64());

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNull($sendResponse->resultError);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->transactionId);
        $this->assertNotNull($statusResponse);
        $wasmId = $statusResponse->getWasmId();
        $this->assertNotNull($wasmId);


        // create contract
        $createContractOp = InvokeHostFunctionOperationBuilder::forCreatingContract($wasmId)->build();
        $accountA = $server->getAccount($accountAId);

        $transaction = (new TransactionBuilder($accountA))
            ->addOperation($createContractOp)->build();

        // simulate first to get the footprint
        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNotNull($simulateResponse->results);
        $this->assertNotNull($simulateResponse->getFootprint());

        // set the footprint and sign
        $transaction->setFootprint($simulateResponse->getFootprint());
        $transaction->sign($accountAKeyPair, Network::futurenet());

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNull($sendResponse->resultError);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->transactionId);
        $this->assertNotNull($statusResponse);
        $contractId = $statusResponse->getContractId();
        $this->assertNotNull($contractId);

        // invoke contract
        // # If tx_submitter_kp and op_invoker_kp use the same account, the submission will fail, a bug?
        $addressB = new Address(Address::TYPE_ACCOUNT, accountId: $accountBId);
        $nonce = $server->getNonce($accountBId, $contractId);
        print("Nonce 1: " . strval($nonce));

        $fnName = "auth";
        $args = array();

        array_push($args, $addressB->toXdrSCVal());
        array_push($args, XdrSCVal::fromU32(3));

        $authInvocation = new AuthorizedInvocation($contractId, $fnName, args: $args);
        $contractAuth = new ContractAuth($authInvocation, address: $addressB, nonce: $nonce);
        $contractAuth->sign($accountBKeyPair, Network::futurenet());
        $invokeContractOp = InvokeHostFunctionOperationBuilder::forInvokingContract($contractId, $fnName, $args, auth: [$contractAuth])->build();

        // simulate first to obtain the footprint
        $accountA = $server->getAccount($accountAId);
        $transaction = (new TransactionBuilder($accountA))
            ->addOperation($invokeContractOp)->build();

        // simulate first to get the footprint
        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNotNull($simulateResponse->results);

        // set the footprint and sign
        $transaction->setFootprint($simulateResponse->getFootprint());
        $transaction->sign($accountAKeyPair, Network::futurenet());

        // TODO: fix this
        // check transaction xdr encoding back and forth
        //$transctionEnvelopeXdr = $transaction->toEnvelopeXdrBase64();
        //$this->assertEquals($transctionEnvelopeXdr, Transaction::fromEnvelopeBase64XdrString($transctionEnvelopeXdr)->toEnvelopeXdrBase64());

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNull($sendResponse->resultError);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->transactionId);
        $this->assertNotNull($statusResponse);
        $this->assertNotNull($statusResponse->results);


        // user friendly
        $resVal = $statusResponse->getResultValue();
        $map = $resVal->getMap();
        if ($map != null && count($map) > 0) {
            foreach ($map as $entry) {
                print("{" . $entry->key->obj->address->accountId->getAccountId() . ", " . strval($entry->val->u32) . "}".PHP_EOL);
            }
        }

        $nonce = $server->getNonce($accountBId, $contractId);
        print("Nonce 2: " . strval($nonce));

        // check horizon response decoding.
        $transactionResponse = $sdk->requestTransaction($sendResponse->transactionId);
        $this->assertEquals(1, $transactionResponse->getOperationCount());
        // TODO: fix this
        //$this->assertEquals($transctionEnvelopeXdr, $transactionResponse->getEnvelopeXdr()->toBase64Xdr());
        $meta = $transactionResponse->getResultMetaXdrBase64();

        // parsing meta is working
        $metaXdr = XdrTransactionMeta::fromBase64Xdr($meta);
        $this->assertEquals($meta, $metaXdr->toBase64Xdr());

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