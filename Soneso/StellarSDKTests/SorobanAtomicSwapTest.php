<?php  declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\CreateContractHostFunction;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\InvokeContractHostFunction;
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
use Soneso\StellarSDK\UploadContractWasmHostFunction;
use Soneso\StellarSDK\Util\FuturenetFriendBot;
use Soneso\StellarSDK\Xdr\XdrInt128Parts;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValType;

class SorobanAtomicSwapTest extends TestCase
{

    public function testAtomicSwap() {
        // See https://soroban.stellar.org/docs/how-to-guides/atomic-swap
        // https://soroban.stellar.org/docs/learn/authorization

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
        //print("atomic swap cid: " . $atomicSwapContractId . PHP_EOL);

        $tokenAContractId = $this->deployContract($server,'./wasm/token.wasm', $adminKeyPair);
        //print("token a cid: " . $tokenAContractId . PHP_EOL);
        $tokenBContractId = $this->deployContract($server,'./wasm/token.wasm', $adminKeyPair);
        //print("token b cid: " . $tokenBContractId . PHP_EOL);

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

        $amountA = XdrSCVal::forI128(new XdrInt128Parts(0,1000));
        $minBForA = XdrSCVal::forI128(new XdrInt128Parts(0,4500));

        $amountB = XdrSCVal::forI128(new XdrInt128Parts(0,5000));
        $minAForB = XdrSCVal::forI128(new XdrInt128Parts(0,950));

        $swapFunctionName = "swap";
        $incrAllowFunctionName = "increase_allowance";

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

         $invokeContract = [
             $addressAlice,
             $addressBob,
             $tokenABytes,
             $tokenBBytes,
             $amountA,
             $minBForA,
             $amountB,
             $minAForB
         ];

        $invokeContractHostFunction = new InvokeContractHostFunction($atomicSwapContractId, $swapFunctionName, $invokeContract, auth: [$aliceContractAuth, $bobContractAuth]);
        $builder = new InvokeHostFunctionOperationBuilder();
        $op = $builder->addFunction($invokeContractHostFunction)->build();

        $source = $sdk->requestAccount($adminId);
        $transaction = (new TransactionBuilder($source))->addOperation($op)->build();

        $simulateResponse = $server->simulateTransaction($transaction);

        $authResult = $simulateResponse->getAuth();
        $this->assertNotNull($authResult);

        // set the transaction data  + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
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
        sleep(5);
        $sdk = StellarSDK::getFutureNetInstance();
        $submitterId = $submitterKp->getAccountId();
        $account = $sdk->requestAccount($submitterId);

        // upload contract wasm
        $contractCode = file_get_contents($pathToCode, false);

        $uploadContractHostFunction = new UploadContractWasmHostFunction($contractCode);
        $builder = new InvokeHostFunctionOperationBuilder();
        $op = $builder->addFunction($uploadContractHostFunction)->build();

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

        // create contract
        sleep(5);

        $createContractHostFunction = new CreateContractHostFunction($wasmId);
        $builder = new InvokeHostFunctionOperationBuilder();
        $op = $builder->addFunction($createContractHostFunction)->build();

        $account = $sdk->requestAccount($submitterId);
        $transaction = (new TransactionBuilder($account))
            ->addOperation($op)->build();

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

        $adminAddress = Address::fromAccountId($submitterId)->toXdrSCVal();
        $functionName = "initialize";

        $tokenNameHex = pack("H*", bin2hex($name));
        $tokenName = XdrSCVal::forBytes($tokenNameHex);

        $symbolHex = pack("H*", bin2hex($symbol));
        $tokenSymbol = XdrSCVal::forBytes($symbolHex);

        $args = [$adminAddress, XdrSCVal::forU32(8), $tokenName, $tokenSymbol];
        $rootInvocation = new AuthorizedInvocation($contractId, $functionName, $args);
        $contractAuth = new ContractAuth($rootInvocation);


        $invokeContractHostFunction = new InvokeContractHostFunction($contractId, $functionName, $args, auth: [$contractAuth]);
        $builder = new InvokeHostFunctionOperationBuilder();
        $op = $builder->addFunction($invokeContractHostFunction)->build();

        sleep(5);
        $account = $sdk->requestAccount($submitterId);
        $transaction = (new TransactionBuilder($account))
            ->addOperation($op)->build();

        $simulateResponse = $server->simulateTransaction($transaction);

        // set the transaction data  + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
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
        sleep(5);

        // see https://soroban.stellar.org/docs/reference/interfaces/token-interface
        // reload account for sequence number
        $sdk = StellarSDK::getFutureNetInstance();
        $submitterId = $submitterKp->getAccountId();
        $account = $sdk->requestAccount($submitterId);

        $adminAddress = Address::fromAccountId($submitterId)->toXdrSCVal();
        $toAddress = Address::fromAccountId($toAccountId)->toXdrSCVal();
        $amountValue = XdrSCVal::forI128(new XdrInt128Parts(0,$amount));
        $functionName = "mint";

        $args = [$toAddress, $amountValue];
        $rootInvocation = new AuthorizedInvocation($contractId, $functionName, $args);
        $contractAuth = new ContractAuth($rootInvocation);

        $invokeContractHostFunction = new InvokeContractHostFunction($contractId, $functionName, $args, auth: [$contractAuth]);
        $builder = new InvokeHostFunctionOperationBuilder();
        $op = $builder->addFunction($invokeContractHostFunction)->build();

        $transaction = (new TransactionBuilder($account))->addOperation($op)->build();

        $simulateResponse = $server->simulateTransaction($transaction);
        // set the transaction data  + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
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
        sleep(5);

        // see https://soroban.stellar.org/docs/reference/interfaces/token-interface
        // reload account for sequence number
        $sdk = StellarSDK::getFutureNetInstance();
        $submitterId = $submitterKp->getAccountId();
        $account = $sdk->requestAccount($submitterId);

        $address = Address::fromAccountId($accountId)->toXdrSCVal();
        $functionName = "balance";

        $args = [$address];

        $invokeContractHostFunction = new InvokeContractHostFunction($contractId, $functionName, $args);
        $builder = new InvokeHostFunctionOperationBuilder();
        $op = $builder->addFunction($invokeContractHostFunction)->build();

        $transaction = (new TransactionBuilder($account))
            ->addOperation($op)->build();

        $simulateResponse = $server->simulateTransaction($transaction);
        // set the transaction data  + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
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