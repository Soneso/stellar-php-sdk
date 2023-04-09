<?php  declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Crypto\KeyPair;
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
use Soneso\StellarSDK\Util\FuturenetFriendBot;
use Soneso\StellarSDK\Xdr\XdrSCVal;

class SorobanAuthTest extends TestCase
{

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function testAuthAccount(): void
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

        // install contract
        $contractCode = file_get_contents('./wasm/auth.wasm', false);
        $installContractOp = InvokeHostFunctionOperationBuilder::
        forInstallingContractCode($contractCode)->build();

        $transaction = (new TransactionBuilder($getAccountResponse))
            ->addOperation($installContractOp)->build();

        // simulate first to get the footprint
        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);

        // set the footprint and sign
        $transaction->setFootprint($simulateResponse->getFootprint());
        $transaction->sign($submitterKeyPair, Network::futurenet());

        // check transaction xdr encoding back and forth
        $transctionEnvelopeXdr = $transaction->toEnvelopeXdrBase64();
        $this->assertEquals($transctionEnvelopeXdr,
            Transaction::fromEnvelopeBase64XdrString($transctionEnvelopeXdr)->toEnvelopeXdrBase64());

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->hash);
        $this->assertNotNull($statusResponse);
        $wasmId = $statusResponse->getWasmId();
        $this->assertNotNull($wasmId);


        // create contract
        $createContractOp = InvokeHostFunctionOperationBuilder::forCreatingContract($wasmId)->build();
        $submitterAccount = $sdk->requestAccount($submitterId);

        $transaction = (new TransactionBuilder($submitterAccount))
            ->addOperation($createContractOp)->build();

        // simulate first to get the footprint
        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNotNull($simulateResponse->results);
        $this->assertNotNull($simulateResponse->getFootprint());

        // set the footprint and sign
        $transaction->setFootprint($simulateResponse->getFootprint());
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

        $invokeOp = InvokeHostFunctionOperationBuilder::forInvokingContract($contractId,
            $functionName, $args, auth: [$contractAuth])->build();

        // simulate first to obtain the footprint
        $submitterAccount = $sdk->requestAccount($submitterId);
        $transaction = (new TransactionBuilder($submitterAccount))
            ->addOperation($invokeOp)->build();

        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNotNull($simulateResponse->results);

        $authResult = $simulateResponse->getAuth();
        $this->assertNotNull($authResult);
        $this->assertCount(1, $authResult);

        // set the footprint and sign
        $transaction->setFootprint($simulateResponse->getFootprint());
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


        // user friendly
        $resVal = $statusResponse->getResultValue();
        $map = $resVal->getMap();
        if ($map != null && count($map) > 0) {
            foreach ($map as $entry) {
                print("{" . $entry->key->address->accountId->getAccountId() . ", " . strval($entry->val->u32) . "}".PHP_EOL);
            }
        }

        // check horizon response decoding.
        $transactionResponse = $sdk->requestTransaction($sendResponse->hash);
        $this->assertEquals(1, $transactionResponse->getOperationCount());
        $this->assertEquals($transctionEnvelopeXdr, $transactionResponse->getEnvelopeXdr()->toBase64Xdr());
        $meta = $transactionResponse->getResultMetaXdrBase64();

        // parsing meta is working
        //$metaXdr = XdrTransactionMeta::fromBase64Xdr($meta);
        //$this->assertEquals($meta, $metaXdr->toBase64Xdr());

    }

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

        $getAccountResponse = $sdk->requestAccount($invokerId);
        $this->assertEquals($invokerId, $getAccountResponse->getAccountId());

        // install contract
        $contractCode = file_get_contents('./wasm/auth.wasm', false);
        $installContractOp = InvokeHostFunctionOperationBuilder::
        forInstallingContractCode($contractCode)->build();

        $transaction = (new TransactionBuilder($getAccountResponse))
            ->addOperation($installContractOp)->build();

        // simulate first to get the footprint
        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);

        // set the footprint and sign
        $transaction->setFootprint($simulateResponse->getFootprint());
        $transaction->sign($invokerKeyPair, Network::futurenet());

        // check transaction xdr encoding back and forth
        $transctionEnvelopeXdr = $transaction->toEnvelopeXdrBase64();
        $this->assertEquals($transctionEnvelopeXdr,
            Transaction::fromEnvelopeBase64XdrString($transctionEnvelopeXdr)->toEnvelopeXdrBase64());

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->hash);
        $this->assertNotNull($statusResponse);
        $wasmId = $statusResponse->getWasmId();
        $this->assertNotNull($wasmId);


        // create contract
        $createContractOp = InvokeHostFunctionOperationBuilder::forCreatingContract($wasmId)->build();
        $invokerAccount = $sdk->requestAccount($invokerId);

        $transaction = (new TransactionBuilder($invokerAccount))
            ->addOperation($createContractOp)->build();

        // simulate first to get the footprint
        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNotNull($simulateResponse->results);
        $this->assertNotNull($simulateResponse->getFootprint());

        // set the footprint and sign
        $transaction->setFootprint($simulateResponse->getFootprint());
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

        // invoke contract no auth needed
        // # If tx_submitter_kp and op_invoker_kp use are the same
        // so we should not need its address & nonce in contract auth and no need to sign
        // see https://discord.com/channels/897514728459468821/1078208197283807305
        // see https://soroban.stellar.org/docs/learn/authorization#transaction-invoker

        $functionName = "auth";

        $invokerAddress = new Address(Address::TYPE_ACCOUNT, accountId: $invokerId);
        $args = [$invokerAddress->toXdrSCVal(), XdrSCVal::forU32(3)];

        // we still need contract auth but we do not need to add the account and sign
        $authInvocation = new AuthorizedInvocation($contractId, $functionName, args: $args);
        $contractAuth = new ContractAuth($authInvocation);

        $invokeContractOp = InvokeHostFunctionOperationBuilder::forInvokingContract($contractId, $functionName, $args, auth: [$contractAuth])->build();

        // simulate first to obtain the footprint
        $invokerAccount = $sdk->requestAccount($invokerId);
        $transaction = (new TransactionBuilder($invokerAccount))
            ->addOperation($invokeContractOp)->build();

        // simulate first to get the footprint
        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNotNull($simulateResponse->results);

        $authResult = $simulateResponse->getAuth();
        $this->assertNotNull($authResult);

        // set the footprint and sign
        $transaction->setFootprint($simulateResponse->getFootprint());
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
        //$metaXdr = XdrTransactionMeta::fromBase64Xdr($meta);
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