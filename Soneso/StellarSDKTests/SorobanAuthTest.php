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
use Soneso\StellarSDK\Soroban\Responses\GetTransactionStatusResponse;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Util\FuturenetFriendBot;
use Soneso\StellarSDK\Xdr\XdrInt128Parts;
use Soneso\StellarSDK\Xdr\XdrSCObject;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrTransactionMeta;

class SorobanAuthTest extends TestCase
{

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function testAuthAccount(): void
    {
        $server = new SorobanServer("https://horizon-futurenet.stellar.cash/soroban/rpc");
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

        $getAccountResponse = $server->getAccount($submitterId);
        $this->assertEquals($submitterId, $getAccountResponse->id);

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
        $this->assertNull($sendResponse->resultError);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->transactionId);
        $this->assertNotNull($statusResponse);
        $wasmId = $statusResponse->getWasmId();
        $this->assertNotNull($wasmId);


        // create contract
        $createContractOp = InvokeHostFunctionOperationBuilder::forCreatingContract($wasmId)->build();
        $submitterAccount = $server->getAccount($submitterId);

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
        $this->assertNull($sendResponse->resultError);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->transactionId);
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
        $args = [$invokerAddress->toXdrSCVal(), XdrSCVal::fromU32(3)];

        $authInvocation = new AuthorizedInvocation($contractId, $functionName, args: $args);

        $contractAuth = new ContractAuth($authInvocation, address: $invokerAddress, nonce: $nonce);
        $contractAuth->sign($invokerKeyPair, Network::futurenet());

        $invokeOp = InvokeHostFunctionOperationBuilder::forInvokingContract($contractId,
            $functionName, $args, auth: [$contractAuth])->build();

        // simulate first to obtain the footprint
        $submitterAccount = $server->getAccount($submitterId);
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

        // check horizon response decoding.
        $transactionResponse = $sdk->requestTransaction($sendResponse->transactionId);
        $this->assertEquals(1, $transactionResponse->getOperationCount());
        $this->assertEquals($transctionEnvelopeXdr, $transactionResponse->getEnvelopeXdr()->toBase64Xdr());
        $meta = $transactionResponse->getResultMetaXdrBase64();

        // parsing meta is working
        $metaXdr = XdrTransactionMeta::fromBase64Xdr($meta);
        $this->assertEquals($meta, $metaXdr->toBase64Xdr());

    }

    public function testAuthInvoker(): void
    {
        // see https://soroban.stellar.org/docs/learn/authorization#transaction-invoker

        $server = new SorobanServer("https://horizon-futurenet.stellar.cash/soroban/rpc");
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

        $getAccountResponse = $server->getAccount($invokerId);
        $this->assertEquals($invokerId, $getAccountResponse->id);

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
        $this->assertNull($sendResponse->resultError);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->transactionId);
        $this->assertNotNull($statusResponse);
        $wasmId = $statusResponse->getWasmId();
        $this->assertNotNull($wasmId);


        // create contract
        $createContractOp = InvokeHostFunctionOperationBuilder::forCreatingContract($wasmId)->build();
        $invokerAccount = $server->getAccount($invokerId);

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
        $this->assertNull($sendResponse->resultError);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->transactionId);
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
        $args = [$invokerAddress->toXdrSCVal(), XdrSCVal::fromU32(3)];

        // we still need contract auth but we do not need to add the account and sign
        $authInvocation = new AuthorizedInvocation($contractId, $functionName, args: $args);
        $contractAuth = new ContractAuth($authInvocation);

        $invokeContractOp = InvokeHostFunctionOperationBuilder::forInvokingContract($contractId, $functionName, $args, auth: [$contractAuth])->build();

        // simulate first to obtain the footprint
        $invokerAccount = $server->getAccount($invokerId);
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

        // check horizon response decoding.
        $transactionResponse = $sdk->requestTransaction($sendResponse->transactionId);
        $this->assertEquals(1, $transactionResponse->getOperationCount());
        $this->assertEquals($transctionEnvelopeXdr, $transactionResponse->getEnvelopeXdr()->toBase64Xdr());
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

    public function testAtomicSwap() {
        // See https://soroban.stellar.org/docs/how-to-guides/atomic-swap
        // https://soroban.stellar.org/docs/learn/authorization
        // https://github.com/StellarCN/py-stellar-base/blob/soroban/examples/soroban_auth_atomic_swap.py

        $server = new SorobanServer("https://horizon-futurenet.stellar.cash/soroban/rpc");
        $server->enableLogging = true;
        $server->acknowledgeExperimental = true;

        $submitterKp = KeyPair::fromSeed("SBPTTA3D3QYQ6E2GSACAZDUFH2UILBNG3EBJCK3NNP7BE4O757KGZUGA");
        $submitterAccountId = $submitterKp->getAccountId();
        // GAERW3OYAVYMZMPMVKHSCDS4ORFPLT5Z3YXA4VM3BVYEA2W7CG3V6YYB

        $aliceKp = KeyPair::fromSeed("SAAPYAPTTRZMCUZFPG3G66V4ZMHTK4TWA6NS7U4F7Z3IMUD52EK4DDEV");
        $aliceAccountId = $aliceKp->getAccountId();
        // GDAT5HWTGIU4TSSZ4752OUC4SABDLTLZFRPZUJ3D6LKBNEPA7V2CIG54

        $bobKp = KeyPair::fromSeed("SAEZSI6DY7AXJFIYA4PM6SIBNEYYXIEM2MSOTHFGKHDW32MBQ7KVO6EN");
        $bobAccountId = $bobKp->getAccountId();
        // GBMLPRFCZDZJPKUPHUSHCKA737GOZL7ERZLGGMJ6YGHBFJZ6ZKMKCZTM

        $atomicSwapContractId = "828e7031194ec4fb9461d8283b448d3eaf5e36357cf465d8db6021ded6eff05c";
        $nativeTokenContractId = "d93f5c7bb0ebc4a9c8f727c5cebc4e41194d38257e1d0d910356b43bfc528813";
        $catTokenContractId = "8dc97b166bd98c755b0e881ee9bd6d0b45e797ec73671f30e026f14a0f1cce67";

        $addressAlice = Address::fromAccountId($aliceAccountId)->toXdrSCVal();
        $addressBob = Address::fromAccountId($bobAccountId)->toXdrSCVal();
        $addressSwapContract = Address::fromContractId($atomicSwapContractId)->toXdrSCVal();

        $tokenABytes = XdrSCVal::fromObject(XdrSCObject::forBytes(hex2bin($nativeTokenContractId)));
        $tokenBBytes = XdrSCVal::fromObject(XdrSCObject::forBytes(hex2bin($catTokenContractId)));

        $amountA = XdrSCVal::fromObject(XdrSCObject::forI128(new XdrInt128Parts(1000,0)));
        $minBForA = XdrSCVal::fromObject(XdrSCObject::forI128(new XdrInt128Parts(4500,0)));

        $amountB = XdrSCVal::fromObject(XdrSCObject::forI128(new XdrInt128Parts(5000,0)));
        $minAForB = XdrSCVal::fromObject(XdrSCObject::forI128(new XdrInt128Parts(950,0)));


        $swapFunctionName = "swap";
        $incrAllowFunctionName = "incr_allow";

        $aliceSubAuthArgs = [$addressAlice, $addressSwapContract, $amountA];
        $aliceSubAuthInvocation = new AuthorizedInvocation($nativeTokenContractId,$incrAllowFunctionName, $aliceSubAuthArgs);
        $aliceRootAuthArgs = [$tokenABytes, $tokenBBytes, $amountA, $minBForA];
        $aliceRootInvocation = new AuthorizedInvocation($atomicSwapContractId, $swapFunctionName, $aliceRootAuthArgs, [$aliceSubAuthInvocation]);

        $bobSubAuthArgs = [$addressBob, $addressSwapContract, $amountB];
        $bobSubAutInvocation = new AuthorizedInvocation($catTokenContractId,$incrAllowFunctionName, $bobSubAuthArgs);
        $bobRootAuthArgs = [$tokenBBytes, $tokenABytes, $amountB, $minAForB];
        $bobRootInvocation = new AuthorizedInvocation($atomicSwapContractId, $swapFunctionName, $bobRootAuthArgs, [$bobSubAutInvocation]);

        $aliceNonce = $server->getNonce($aliceAccountId, $atomicSwapContractId);
        $aliceContractAuth = new ContractAuth($aliceRootInvocation, address: Address::fromAccountId($aliceAccountId), nonce: $aliceNonce);
        $aliceContractAuth->sign($aliceKp, Network::futurenet());

        $bobNonce = $server->getNonce($bobAccountId, $atomicSwapContractId);
        $bobContractAuth = new ContractAuth($bobRootInvocation, address: Address::fromAccountId($bobAccountId), nonce: $bobNonce);
        $bobContractAuth->sign($bobKp, Network::futurenet());

        $invokeArgs = [
            $addressAlice,
            $addressBob,
            $tokenABytes,
            $tokenBBytes,
            $amountA,
            $minBForA,
            $amountB,
            $minAForB
        ];

        $invokeContractOp = InvokeHostFunctionOperationBuilder::forInvokingContract($atomicSwapContractId,
            $swapFunctionName, $invokeArgs, auth: [$aliceContractAuth, $bobContractAuth])->build();

        $source = $server->getAccount($submitterAccountId);

        // simulate first to obtain the footprint

        $transaction = (new TransactionBuilder($source))
            ->addOperation($invokeContractOp)->build();

        // simulate first to get the footprint
        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNotNull($simulateResponse->results);

        $authResult = $simulateResponse->getAuth();
        $this->assertNotNull($authResult);

        // set the footprint and sign
        $transaction->setFootprint($simulateResponse->getFootprint());
        $transaction->sign($submitterKp, Network::futurenet());

        // check transaction xdr encoding back and forth
        $transctionEnvelopeXdr = $transaction->toEnvelopeXdrBase64();
        $this->assertEquals($transctionEnvelopeXdr, Transaction::fromEnvelopeBase64XdrString($transctionEnvelopeXdr)->toEnvelopeXdrBase64());

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNull($sendResponse->resultError);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->transactionId);
        $this->assertNotNull($statusResponse);
        $this->assertNotNull($statusResponse->results);
        print("Result " . $statusResponse->results->toArray()[0]->xdr);
    }

    public function testAuthNotDecodable () {

        $server = new SorobanServer("https://horizon-futurenet.stellar.cash/soroban/rpc");
        $server->enableLogging = true;
        $server->acknowledgeExperimental = true;

        $accountAKeyPair = KeyPair::fromSeed("SCMMXSA2737KCTWPN5I6ZCZ3XVEPFCHWUNCWGQDBVRRAC36MKTWIOSA6");
        // GDXGLNETC3TEMG4PBPEVUCTO6ZUF5TXQX73SDZXYUWPARCIKA3VNPV45
        $accountAId = $accountAKeyPair->getAccountId();
        //$contractId = "21686555b85f194cdade3c54a6637d59cb48c376102b9992a5b43652d8dd629e"; // auth from sim transaction response is not decodable
        $contractId = "c6c4cefec3281a4adc93b2e46ff2d87913294e6197905d6bcfa55b9a0d4ece2e"; // auth from sim transaction response is decodable

        $fnName = "auth";
        $args = array();

        $addressA = new Address(Address::TYPE_ACCOUNT, accountId: $accountAId);
        array_push($args, $addressA->toXdrSCVal());
        array_push($args, XdrSCVal::fromU32(5));

        $authInvocation = new AuthorizedInvocation($contractId, $fnName, args: $args);
        $contractAuth = new ContractAuth($authInvocation);

        $invokeContractOp = InvokeHostFunctionOperationBuilder::forInvokingContract($contractId, $fnName, $args, auth: [$contractAuth])->build();

        // simulate first to obtain the footprint
        $accountA = $server->getAccount($accountAId);
        $transaction = (new TransactionBuilder($accountA))
            ->addOperation($invokeContractOp)->build();

        // simulate first to get the footprint
        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNotNull($simulateResponse->results);

        $authResult = $simulateResponse->getAuth();
        $this->assertNotNull($authResult);

        // set the footprint and sign
        $transaction->setFootprint($simulateResponse->getFootprint());
        $transaction->sign($accountAKeyPair, Network::futurenet());

        // check transaction xdr encoding back and forth
        $transctionEnvelopeXdr = $transaction->toEnvelopeXdrBase64();
        $this->assertEquals($transctionEnvelopeXdr, Transaction::fromEnvelopeBase64XdrString($transctionEnvelopeXdr)->toEnvelopeXdrBase64());

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNull($sendResponse->resultError);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->transactionId);
        $this->assertNotNull($statusResponse);
        $this->assertNotNull($statusResponse->results);

        // print result
        $resVal = $statusResponse->getResultValue();
        $map = $resVal->getMap();
        if ($map != null && count($map) > 0) {
            foreach ($map as $entry) {
                print("{" . $entry->key->obj->address->accountId->getAccountId() . ", " . strval($entry->val->u32) . "}".PHP_EOL);
            }
        }
    }

}