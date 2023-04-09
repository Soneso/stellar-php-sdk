<?php  declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\AuthorizedInvocation;
use Soneso\StellarSDK\Soroban\ContractAuth;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\SendTransactionResponse;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Util\FuturenetFriendBot;
use Soneso\StellarSDK\Xdr\XdrInt128Parts;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValType;

class SorobanAtomicSwapTest extends TestCase
{

    public function testAtomicSwap() {
        // See https://soroban.stellar.org/docs/how-to-guides/atomic-swap
        // https://soroban.stellar.org/docs/learn/authorization
        // https://github.com/StellarCN/py-stellar-base/blob/soroban/examples/soroban_auth_atomic_swap.py

        $server = new SorobanServer("https://rpc-futurenet.stellar.org:443");
        $server->enableLogging = true;
        $server->acknowledgeExperimental = true;

        $sdk = StellarSDK::getFutureNetInstance();

        $adminKeyPair = KeyPair::random();
        $adminId = $adminKeyPair->getAccountId();
        $aliceKeyPair = KeyPair::random();
        $aliceId = $aliceKeyPair->getAccountId();
        $bobKeyPair = KeyPair::random();
        $bobId = $bobKeyPair->getAccountId();

        FuturenetFriendBot::fundTestAccount($adminId);
        FuturenetFriendBot::fundTestAccount($aliceId);
        FuturenetFriendBot::fundTestAccount($bobId);
        sleep(5);


        $atomicSwapContractId = $this->deployContract($server,'./wasm/atomic_swap.wasm', $adminKeyPair);
        $tokenAContractId = $this->deployContract($server,'./wasm/token.wasm', $adminKeyPair);;
        $tokenBContractId = $this->deployContract($server,'./wasm/token.wasm', $adminKeyPair);;

        $this->createToken($server, $adminKeyPair, $tokenAContractId, "TokenA", "TokenA");
        $this->createToken($server, $adminKeyPair, $tokenBContractId, "TokenB", "TokenB");

        $this->mint($server, $adminKeyPair, $tokenAContractId, $aliceId, 10000000000000);
        $this->mint($server, $adminKeyPair, $tokenBContractId, $bobId, 10000000000000);

        $aliceTokenABalance = $this->balance($server, $adminKeyPair, $tokenAContractId, $aliceId);
        $this->assertEquals(10000000000000, $aliceTokenABalance);

        $bobTokenBBalance = $this->balance($server, $adminKeyPair, $tokenBContractId, $bobId);
        $this->assertEquals(10000000000000, $bobTokenBBalance);


        $addressAlice = Address::fromAccountId($aliceId)->toXdrSCVal();
        $addressBob = Address::fromAccountId($bobId)->toXdrSCVal();
        $addressSwapContract = Address::fromContractId($atomicSwapContractId)->toXdrSCVal();

        $tokenABytes = XdrSCVal::forBytes(hex2bin($tokenAContractId));
        $tokenBBytes = XdrSCVal::forBytes(hex2bin($tokenBContractId));

        $amountA = XdrSCVal::forI128(new XdrInt128Parts(1000,0));
        $minBForA = XdrSCVal::forI128(new XdrInt128Parts(4500,0));

        $amountB = XdrSCVal::forI128(new XdrInt128Parts(5000,0));
        $minAForB = XdrSCVal::forI128(new XdrInt128Parts(950,0));


        $swapFunctionName = "swap";
        $incrAllowFunctionName = "incr_allow";

        $aliceSubAuthArgs = [$addressAlice, $addressSwapContract, $amountA];
        $aliceSubAuthInvocation = new AuthorizedInvocation($tokenAContractId,$incrAllowFunctionName, $aliceSubAuthArgs);
        $aliceRootAuthArgs = [$tokenABytes, $tokenBBytes, $amountA, $minBForA];
        $aliceRootInvocation = new AuthorizedInvocation($atomicSwapContractId, $swapFunctionName, $aliceRootAuthArgs, [$aliceSubAuthInvocation]);

        $bobSubAuthArgs = [$addressBob, $addressSwapContract, $amountB];
        $bobSubAutInvocation = new AuthorizedInvocation($tokenBContractId,$incrAllowFunctionName, $bobSubAuthArgs);
        $bobRootAuthArgs = [$tokenBBytes, $tokenABytes, $amountB, $minAForB];
        $bobRootInvocation = new AuthorizedInvocation($atomicSwapContractId, $swapFunctionName, $bobRootAuthArgs, [$bobSubAutInvocation]);

        $aliceNonce = $server->getNonce($aliceId, $atomicSwapContractId);
        $aliceContractAuth = new ContractAuth($aliceRootInvocation, address: Address::fromAccountId($aliceId), nonce: $aliceNonce);
        $aliceContractAuth->sign($aliceKeyPair, Network::futurenet());

        $bobNonce = $server->getNonce($bobId, $atomicSwapContractId);
        $bobContractAuth = new ContractAuth($bobRootInvocation, address: Address::fromAccountId($bobId), nonce: $bobNonce);
        $bobContractAuth->sign($bobKeyPair, Network::futurenet());

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

        $source = $sdk->requestAccount($adminId);

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
        $transaction->sign($adminKeyPair, Network::futurenet());

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
        $this->assertEquals(GetTransactionResponse::STATUS_SUCCESS, $statusResponse->status);
        $this->assertNotNull($statusResponse->getResultValue());
        $result = $statusResponse->getResultValue();
        $this->assertEquals(XdrSCValType::SCV_VOID, $result->type->value);
    }

    private function deployContract(SorobanServer $server, String $pathToCode, KeyPair $submitterKp) : String {
        $sdk = StellarSDK::getFutureNetInstance();
        $submitterId = $submitterKp->getAccountId();
        $account = $sdk->requestAccount($submitterId);

        // install contract
        $contractCode = file_get_contents($pathToCode, false);
        $installContractOp = InvokeHostFunctionOperationBuilder::
        forInstallingContractCode($contractCode)->build();

        $transaction = (new TransactionBuilder($account))->addOperation($installContractOp)->build();

        // simulate first to get the footprint
        $simulateResponse = $server->simulateTransaction($transaction);

        // set the footprint and sign
        $transaction->setFootprint($simulateResponse->getFootprint());
        $transaction->sign($submitterKp, Network::futurenet());

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->hash);
        $this->assertNotNull($statusResponse);
        $wasmId = $statusResponse->getWasmId();
        $this->assertNotNull($wasmId);

        // create contract
        $createContractOp = InvokeHostFunctionOperationBuilder::forCreatingContract($wasmId)->build();
        $account = $sdk->requestAccount($submitterId);

        $transaction = (new TransactionBuilder($account))->addOperation($createContractOp)->build();

        // simulate first to get the footprint
        $simulateResponse = $server->simulateTransaction($transaction);

        // set the footprint and sign
        $transaction->setFootprint($simulateResponse->getFootprint());
        $transaction->sign($submitterKp, Network::futurenet());

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->hash);
        $this->assertNotNull($statusResponse);
        $contractId = $statusResponse->getContractId();
        $this->assertNotNull($contractId);
        return $contractId;
    }

    private function createToken(SorobanServer $server, Keypair $submitterKp, String $contractId, String $name, String $symbol) : void
    {
        // see https://soroban.stellar.org/docs/reference/interfaces/token-interface
        // reload account for sequence number
        $sdk = StellarSDK::getFutureNetInstance();
        $submitterId = $submitterKp->getAccountId();
        $account = $sdk->requestAccount($submitterId);

        $adminAddress = Address::fromAccountId($submitterId)->toXdrSCVal();
        $functionName = "initialize";

        $tokenNameHex = pack("H*", bin2hex($name));
        $tokenName = XdrSCVal::forBytes($tokenNameHex);

        $symbolHex = pack("H*", bin2hex($symbol));
        $tokenSymbol = XdrSCVal::forBytes($symbolHex);

        $args = [$adminAddress, XdrSCVal::forU32(8), $tokenName, $tokenSymbol];
        $rootInvocation = new AuthorizedInvocation($contractId, $functionName, $args);
        $contractAuth = new ContractAuth($rootInvocation);

        $invokeContractOp = InvokeHostFunctionOperationBuilder::forInvokingContract($contractId,
            $functionName, $args, auth: [$contractAuth])->build();


        // simulate first to obtain the footprint
        $transaction = (new TransactionBuilder($account))
            ->addOperation($invokeContractOp)->build();

        // simulate first to get the footprint
        $simulateResponse = $server->simulateTransaction($transaction);

        // set the footprint and sign
        $transaction->setFootprint($simulateResponse->getFootprint());
        $transaction->sign($submitterKp, Network::futurenet());

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->hash);
        $this->assertEquals(GetTransactionResponse::STATUS_SUCCESS, $statusResponse->status);
    }

    private function mint(SorobanServer $server, Keypair $submitterKp, String $contractId, String $toAccountId, int $amount) : void
    {
        // see https://soroban.stellar.org/docs/reference/interfaces/token-interface
        // reload account for sequence number
        $sdk = StellarSDK::getFutureNetInstance();
        $submitterId = $submitterKp->getAccountId();
        $account = $sdk->requestAccount($submitterId);

        $adminAddress = Address::fromAccountId($submitterId)->toXdrSCVal();
        $toAddress = Address::fromAccountId($toAccountId)->toXdrSCVal();
        $amountValue = XdrSCVal::forI128(new XdrInt128Parts($amount,0));
        $functionName = "mint";

        $args = [$adminAddress, $toAddress, $amountValue];
        $rootInvocation = new AuthorizedInvocation($contractId, $functionName, $args);
        $contractAuth = new ContractAuth($rootInvocation);

        $invokeContractOp = InvokeHostFunctionOperationBuilder::forInvokingContract($contractId,
            $functionName, $args, auth: [$contractAuth])->build();

        // simulate first to obtain the footprint
        $transaction = (new TransactionBuilder($account))
            ->addOperation($invokeContractOp)->build();

        // simulate first to get the footprint
        $simulateResponse = $server->simulateTransaction($transaction);

        // set the footprint and sign
        $transaction->setFootprint($simulateResponse->getFootprint());
        $transaction->sign($submitterKp, Network::futurenet());

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->hash);
        $this->assertEquals(GetTransactionResponse::STATUS_SUCCESS, $statusResponse->status);
    }

    private function balance(SorobanServer $server, Keypair $submitterKp, String $contractId, String $accountId) : int
    {
        // see https://soroban.stellar.org/docs/reference/interfaces/token-interface
        // reload account for sequence number
        $sdk = StellarSDK::getFutureNetInstance();
        $submitterId = $submitterKp->getAccountId();
        $account = $sdk->requestAccount($submitterId);

        $address = Address::fromAccountId($accountId)->toXdrSCVal();
        $functionName = "balance";

        $args = [$address];

        $invokeContractOp = InvokeHostFunctionOperationBuilder::forInvokingContract($contractId,
            $functionName, $args)->build();

        // simulate first to obtain the footprint
        $transaction = (new TransactionBuilder($account))->addOperation($invokeContractOp)->build();

        // simulate first to get the footprint
        $simulateResponse = $server->simulateTransaction($transaction);

        // set the footprint and sign
        $transaction->setFootprint($simulateResponse->getFootprint());
        $transaction->sign($submitterKp, Network::futurenet());

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->hash);
        $this->assertEquals(GetTransactionResponse::STATUS_SUCCESS, $statusResponse->status);
        $this->assertNotNull($statusResponse->getResultValue());
        $resultVal = $statusResponse->getResultValue();
        $this->assertNotNull($resultVal->getI128());
        return $resultVal->getI128()->lo;
    }

    private function pollStatus(SorobanServer $server, string $transactionId) : ?GetTransactionResponse {
        $statusResponse = null;
        $status = GetTransactionResponse::STATUS_NOT_FOUND;
        $count = 15;
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
            $count -= 1;
            $this->assertGreaterThan(0, $count);
        }
        return $statusResponse;
    }
}