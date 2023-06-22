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
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\UploadContractWasmHostFunction;
use Soneso\StellarSDK\Util\FuturenetFriendBot;
use Soneso\StellarSDK\Xdr\XdrInt128Parts;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryType;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrSCVal;

class SorobanCustomAccountTest extends TestCase
{

    public function testCustomAccount() {
        // https://github.com/Soneso/as-soroban-examples/tree/main/custom_account
        // https://soroban.stellar.org/docs/advanced-tutorials/custom-account
        // https://soroban.stellar.org/docs/learn/authorization

        $server = new SorobanServer("https://rpc-futurenet.stellar.org:443");
        $server->enableLogging = true;
        $server->acknowledgeExperimental = true;

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

        $deployAccRes = $this->deployContract($server,'./wasm/custom_account.wasm', $adminKeyPair);
        $accountContractWasmId = $deployAccRes[0];
        $accountContractId = $deployAccRes[1];

        $deployTokRes = $this->deployContract($server,'./wasm/token.wasm', $adminKeyPair);
        $tokenAContractId = $deployTokRes[1];

        $this->createToken($server, $adminKeyPair, $tokenAContractId, "TokenA", "TokenA");

        $accountContractAddressXdrSCVal = Address::fromContractId($accountContractId)->toXdrSCVal();
        $this->mint($server, $adminKeyPair, $tokenAContractId, $accountContractAddressXdrSCVal, 10000000000000);

        $accTokenABalance = $this->balance($server, $adminKeyPair, $tokenAContractId, $accountContractAddressXdrSCVal);
        $this->assertEquals(10000000000000, $accTokenABalance);

        $alicePk = XdrSCVal::forBytes($aliceKeyPair->getPublicKey());
        $bobPk = XdrSCVal::forBytes($bobKeyPair->getPublicKey());
        $signers = XdrSCVal::forVec([$bobPk, $alicePk]);
        $this->initAccContract($server, $adminKeyPair, $accountContractId, $signers);
        $this->addLimit($server, $adminKeyPair, $accountContractId, $tokenAContractId, 100, $aliceKeyPair, $bobKeyPair);

        $expectSuccess = true;
        $this->transfer($expectSuccess, $server, $adminKeyPair, $accountContractId, $accountContractWasmId, $tokenAContractId, 10, $aliceId, signer1: $aliceKeyPair);

        $expectSuccess = false;
        $this->transfer($expectSuccess, $server, $adminKeyPair, $accountContractId, $accountContractWasmId, $tokenAContractId, 101, $aliceId, signer1: $aliceKeyPair);

        $expectSuccess = true;
        $this->transfer($expectSuccess, $server, $adminKeyPair, $accountContractId, $accountContractWasmId, $tokenAContractId, 101, $aliceId, signer1: $aliceKeyPair, signer2: $bobKeyPair);

    }

    private function initAccContract(SorobanServer $server, Keypair $submitterKp, String $contractId, XdrSCVal $signers) : void
    {
        sleep(5);

        // https://soroban.stellar.org/docs/advanced-tutorials/custom-account
        $sdk = StellarSDK::getFutureNetInstance();
        $submitterId = $submitterKp->getAccountId();
        $account = $sdk->requestAccount($submitterId);

        $functionName = "init";

        $invokeContractHostFunction = new InvokeContractHostFunction($contractId, $functionName, [$signers]);
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

    private function addLimit(SorobanServer $server, Keypair $submitterKp, String $contractId, String $tokenContractId, int $limit, KeyPair $signer1, KeyPair $signer2) : void
    {
        sleep(5);
        $sdk = StellarSDK::getFutureNetInstance();
        $submitterId = $submitterKp->getAccountId();
        $account = $sdk->requestAccount($submitterId);

        $functionName = "add_limit";
        $token = XdrSCVal::forContractId($tokenContractId);
        $limitI128 = XdrSCVal::forI128Parts(0,$limit);

        $invokerAddress = Address::fromContractId($contractId);
        $nonce = $server->getNonceForAddress($invokerAddress, $contractId);
        $args = [$token, $limitI128];
        $authInvocation = new AuthorizedInvocation($contractId, $functionName, args: $args);

        $contractAuth = new ContractAuth($authInvocation, address: $invokerAddress, nonce: $nonce);
        $contractAuth->sign($signer1, Network::futurenet());
        $contractAuth->sign($signer2, Network::futurenet());

        $invokeContractHostFunction = new InvokeContractHostFunction($contractId, $functionName, $args, auth: [$contractAuth]);
        $builder = new InvokeHostFunctionOperationBuilder();
        $op = $builder->addFunction($invokeContractHostFunction)->build();

        $transaction = (new TransactionBuilder($account))
            ->addOperation($op)->build();

        $simulateResponse = $server->simulateTransaction($transaction);
        $transactionData = $simulateResponse->getTransactionData();
        //$transactionData->resources->writeBytes *= 10;
        $transactionData->resources->readBytes *= 2;
        $transactionData->resources->instructions *= 3;
        $simulateResponse->minResourceFee += 1200800;

        // set the transaction data  + fee and sign
        $transaction->setSorobanTransactionData($transactionData);
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->sign($submitterKp, Network::futurenet());

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->hash);
        $this->assertEquals(GetTransactionResponse::STATUS_SUCCESS, $statusResponse->status);
    }

    private function transfer(bool $expectSuccess, SorobanServer $server, Keypair $submitterKp, String $accContractId, String $accContractWasmId, String $tokenContractId, int $amount, string $toAccountId, ?KeyPair $signer1 = null, ?KeyPair $signer2 = null) : void
    {
        sleep(5);
        // see https://soroban.stellar.org/docs/reference/interfaces/token-interface
        $sdk = StellarSDK::getFutureNetInstance();
        $submitterId = $submitterKp->getAccountId();
        $account = $sdk->requestAccount($submitterId);

        $functionName = "transfer";

        $invokerAddress = Address::fromContractId($accContractId);
        $toAddress = Address::fromAccountId($toAccountId);
        $amountI128 = XdrSCVal::forI128Parts(0,$amount);
        $args = [$invokerAddress->toXdrSCVal(), $toAddress->toXdrSCVal(), $amountI128];
        $authInvocation = new AuthorizedInvocation($tokenContractId, $functionName, args: $args);

        $nonce = $server->getNonceForAddress($invokerAddress, $tokenContractId);
        $contractAuth = new ContractAuth($authInvocation, address: $invokerAddress, nonce: $nonce);
        if($signer1 != null) {
            $contractAuth->sign($signer1, Network::futurenet());
        }
        if($signer2 != null) {
            $contractAuth->sign($signer2, Network::futurenet());
        }

        $invokeContractHostFunction = new InvokeContractHostFunction($tokenContractId, $functionName, $args, auth: [$contractAuth]);
        $builder = new InvokeHostFunctionOperationBuilder();
        $op = $builder->addFunction($invokeContractHostFunction)->build();

        $transaction = (new TransactionBuilder($account))
            ->addOperation($op)->build();

        $simulateResponse = $server->simulateTransaction($transaction);
        $transactionData = $simulateResponse->getTransactionData();

        // add some resources because preflight can not take __check_auth into account
        $transactionData->resources->readBytes *= 2;
        $transactionData->resources->instructions *= 3;
        $simulateResponse->minResourceFee += 1200800;

        // we need to extend the footprint, so that __check_auth can read from storage
        $readOwnersKey = new XdrLedgerKey(XdrLedgerEntryType::CONTRACT_DATA());
        $readOwnersKey->contractID = $accContractId;
        $readOwnersKey->contractDataKey = XdrSCVal::forSymbol("Owners");
        $readLimitKey = new XdrLedgerKey(XdrLedgerEntryType::CONTRACT_DATA());
        $readLimitKey->contractID = $accContractId;
        $readLimitKey->contractDataKey = XdrSCVal::forSymbol("SpendMax");
        $execKey = new XdrLedgerKey(XdrLedgerEntryType::CONTRACT_DATA());
        $execKey->contractID = $accContractId;
        $execKey->contractDataKey = XdrSCVal::forLedgerKeyContractExecutable();
        $accContractCodeKey = new XdrLedgerKey(XdrLedgerEntryType::CONTRACT_CODE());
        $accContractCodeKey->contractCodeHash = hex2bin($accContractWasmId);

        array_push($transactionData->resources->footprint->readOnly , $readOwnersKey);
        array_push($transactionData->resources->footprint->readOnly , $readLimitKey);
        array_push($transactionData->resources->footprint->readOnly , $execKey);
        array_push($transactionData->resources->footprint->readOnly , $accContractCodeKey);


        // set the transaction data  + fee and sign
        $transaction->setSorobanTransactionData($transactionData);
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->sign($submitterKp, Network::futurenet());

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->hash);
        if ($expectSuccess) {
            $this->assertEquals(GetTransactionResponse::STATUS_SUCCESS, $statusResponse->status);
        } else {
            $this->assertEquals(GetTransactionResponse::STATUS_FAILED, $statusResponse->status);
        }
    }


    private function deployContract(SorobanServer $server, String $pathToCode, KeyPair $submitterKp) : array {
        sleep(5);
        $result = array();
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
        array_push($result, $wasmId);
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
        array_push($result, $contractId);
        return $result;
    }

    private function createToken(SorobanServer $server, Keypair $submitterKp, String $contractId, String $name, String $symbol) : void
    {
        // see https://soroban.stellar.org/docs/reference/interfaces/token-interface
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

    private function mint(SorobanServer $server, Keypair $submitterKp, String $contractId, XdrSCVal $toAddress, int $amount) : void
    {
        sleep(5);

        // see https://soroban.stellar.org/docs/reference/interfaces/token-interface
        $sdk = StellarSDK::getFutureNetInstance();
        $submitterId = $submitterKp->getAccountId();
        $account = $sdk->requestAccount($submitterId);

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

    private function balance(SorobanServer $server, Keypair $submitterKp, String $contractId, XdrSCVal $address) : int
    {
        sleep(5);

        // see https://soroban.stellar.org/docs/reference/interfaces/token-interface
        $sdk = StellarSDK::getFutureNetInstance();
        $submitterId = $submitterKp->getAccountId();
        $account = $sdk->requestAccount($submitterId);

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