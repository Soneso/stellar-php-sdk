<?php  declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\CreateContractWithConstructorHostFunction;
use Soneso\StellarSDK\ExtendFootprintTTLOperationBuilder;
use Soneso\StellarSDK\CreateContractHostFunction;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\RestoreFootprintOperationBuilder;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\Requests\SimulateTransactionRequest;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\SendTransactionResponse;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\UploadContractWasmHostFunction;
use Soneso\StellarSDK\Util\FriendBot;
use Soneso\StellarSDK\Util\FuturenetFriendBot;
use Soneso\StellarSDK\Xdr\XdrContractDataDurability;
use Soneso\StellarSDK\Xdr\XdrInt128Parts;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryType;
use Soneso\StellarSDK\Xdr\XdrLedgerFootprint;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyContractCode;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyContractData;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSorobanResources;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionData;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionDataExt;
use function PHPUnit\Framework\assertNotNull;

class SorobanCustomAccountTest extends TestCase
{

    const CUSTOM_ACCOUNT_CONTRACT_PATH = './wasm/soroban_custom_account.wasm';
    const TOKEN_CONTRACT_PATH = './wasm/soroban_token_contract.wasm';

    const TESTNET_SERVER_URL = "https://soroban-testnet.stellar.org";
    const FUTURENET_SERVER_URL = "https://rpc-futurenet.stellar.org";

    private string $testOn = 'testnet'; // 'futurenet'
    private Network $network;
    private SorobanServer $server;

    public function setUp(): void
    {
        // Turn on error reporting
        error_reporting(E_ALL);
        if ($this->testOn === 'testnet') {
            $this->network = Network::testnet();
            $this->server = new SorobanServer(self::TESTNET_SERVER_URL);
            $this->server->enableLogging = true;
        } elseif ($this->testOn === 'futurenet') {
            $this->network = Network::futurenet();
            $this->server = new SorobanServer(self::FUTURENET_SERVER_URL);
            $this->server->enableLogging = true;
        }
    }

    public function testCustomAccount() {
        // https://github.com/Soneso/as-soroban-examples/tree/main/custom_account
        // https://soroban.stellar.org/docs/advanced-tutorials/custom-account
        // https://soroban.stellar.org/docs/fundamentals-and-concepts/authorization

        $adminKeyPair =  KeyPair::random();
        $adminId = $adminKeyPair->getAccountId();
        //print("ADMIN: " . $adminKeyPair->getSecretSeed() . PHP_EOL);

        $aliceKeyPair = KeyPair::random();
        $aliceId = $aliceKeyPair->getAccountId();
        //print("ALICE: " . $aliceKeyPair->getSecretSeed() . PHP_EOL);

        $bobKeyPair = KeyPair::random();
        $bobId = $bobKeyPair->getAccountId();
        //print("BOB: " . $bobKeyPair->getSecretSeed() . PHP_EOL);

        if ($this->testOn === 'testnet') {
            FriendBot::fundTestAccount($adminId);
            FriendBot::fundTestAccount($aliceId);
            FriendBot::fundTestAccount($bobId);
        } elseif ($this->testOn === 'futurenet') {
            FuturenetFriendBot::fundTestAccount($adminId);
            FuturenetFriendBot::fundTestAccount($aliceId);
            FuturenetFriendBot::fundTestAccount($bobId);
        }

        sleep(5);

        $deployAccRes = $this->deployContract($this->server,self::CUSTOM_ACCOUNT_CONTRACT_PATH, $adminKeyPair);
        $accountContractWasmId = $deployAccRes[0];
        $accountContractId = $deployAccRes[1];
        //print("accountContractWasmId : " . $accountContractWasmId . PHP_EOL);
        //print("accountContractId : " . $accountContractId . PHP_EOL);

        $contractInfo = $this->server->loadContractInfoForContractId($accountContractId);
        $this->assertNotNull($contractInfo);
        $this->assertTrue(count($contractInfo->specEntries) > 0);
        $this->assertTrue(count($contractInfo->metaEntries) > 0);

        $adminAddress = Address::fromAccountId($adminKeyPair->getAccountId())->toXdrSCVal();
        $tokenName = XdrSCVal::forString("TokenA");
        $tokenSymbol = XdrSCVal::forString("TokenA");
        $decimal = XdrSCVal::forU32(8);

        $deployTokRes = $this->deployContract($this->server,self::TOKEN_CONTRACT_PATH, $adminKeyPair,
            constructorArgs: [$adminAddress, $decimal, $tokenName, $tokenSymbol]);

        $tokenAContractId = $deployTokRes[1];

        //print("tokenAContractId: " . $tokenAContractId . PHP_EOL);

        $accountContractAddressXdrSCVal = Address::fromContractId($accountContractId)->toXdrSCVal();
        $this->mint($this->server, $adminKeyPair, $tokenAContractId, $accountContractAddressXdrSCVal, 10000000000000);

        $accTokenABalance = $this->balance($this->server, $adminKeyPair, $tokenAContractId, $accountContractAddressXdrSCVal);
        $this->assertEquals(10000000000000, $accTokenABalance);

        $alicePk = XdrSCVal::forBytes($aliceKeyPair->getPublicKey());
        $bobPk = XdrSCVal::forBytes($bobKeyPair->getPublicKey());
        $signers = XdrSCVal::forVec([$bobPk, $alicePk]);
        $this->initAccContract($this->server, $adminKeyPair, $accountContractId, $signers);

        $this->addLimit($this->server, $adminKeyPair, $accountContractId, $tokenAContractId, 100, $aliceKeyPair, $bobKeyPair);

        $expectSuccess = true;
        $this->transfer($expectSuccess, $this->server, $adminKeyPair, $accountContractId, $accountContractWasmId, $tokenAContractId, 10, $aliceId, signer1: $aliceKeyPair);

        $expectSuccess = false;
        $this->transfer($expectSuccess, $this->server, $adminKeyPair, $accountContractId, $accountContractWasmId, $tokenAContractId, 101, $aliceId, signer1: $aliceKeyPair);

        $expectSuccess = true;
        $this->transfer($expectSuccess, $this->server, $adminKeyPair, $accountContractId, $accountContractWasmId, $tokenAContractId, 101, $aliceId, signer1: $aliceKeyPair, signer2: $bobKeyPair);
    }

    private function initAccContract(SorobanServer $server, Keypair $submitterKp, String $contractId, XdrSCVal $signers) : void
    {
        sleep(5);

        // https://soroban.stellar.org/docs/advanced-tutorials/custom-account
        $submitterId = $submitterKp->getAccountId();
        $account = $this->server->getAccount($submitterId);
        assertNotNull($account);

        $functionName = "init";

        $invokeContractHostFunction = new InvokeContractHostFunction($contractId, $functionName, [$signers]);
        $builder = new InvokeHostFunctionOperationBuilder($invokeContractHostFunction);
        $op = $builder->build();

        $transaction = (new TransactionBuilder($account))->addOperation($op)->build();

        $request = new SimulateTransactionRequest($transaction);
        $simulateResponse = $server->simulateTransaction($request);
        // set the transaction data  + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->setSorobanAuth($simulateResponse->getSorobanAuth());
        $transaction->sign($submitterKp, $this->network);

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
        $submitterId = $submitterKp->getAccountId();
        $account = $this->server->getAccount($submitterId);
        assertNotNull($account);

        $functionName = "add_limit";
        $token = Address::fromContractId($tokenContractId)->toXdrSCVal();
        $limitI128 = XdrSCVal::forI128Parts(0,$limit);

        $invokerAddress = Address::fromContractId($contractId);
        $args = [$token, $limitI128];

        $invokeContractHostFunction = new InvokeContractHostFunction($contractId, $functionName, $args);
        $builder = new InvokeHostFunctionOperationBuilder($invokeContractHostFunction);
        $op = $builder->build();

        $transaction = (new TransactionBuilder($account))
            ->addOperation($op)->build();

        $request = new SimulateTransactionRequest($transaction);
        $simulateResponse = $server->simulateTransaction($request);
        $transactionData = $simulateResponse->getTransactionData();

        // add some resources because preflight can not take __check_auth into account
        $transactionData->resources->diskReadBytes *= 3;
        $transactionData->resources->instructions *= 4;
        $transactionData->resourceFee += 1500800;
        $simulateResponse->minResourceFee += 1500800;

        // set the transaction data  + fee and sign
        $transaction->setSorobanTransactionData($transactionData);
        $transaction->addResourceFee($simulateResponse->minResourceFee);

        $auth = $simulateResponse->getSorobanAuth();
        $this->assertNotNull($auth);

        $latestLedgerResponse = $server->getLatestLedger();
        $this->assertNotNull($latestLedgerResponse->sequence);
        foreach ($auth as $a) {
            if ($a instanceof  SorobanAuthorizationEntry) {
                $this->assertNotNull($a->credentials->addressCredentials);
                // increase signature expiration ledger
                $a->credentials->addressCredentials->signatureExpirationLedger = $latestLedgerResponse->sequence + 10;
                // sign
                $a->sign($signer1, $this->network);
                $a->sign($signer2, $this->network);

            } else {
                self::fail("invalid auth");
            }
        }
        $transaction->setSorobanAuth($auth);
        $transaction->sign($submitterKp, $this->network);

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->hash);
        if ($statusResponse->status == GetTransactionResponse::STATUS_FAILED) {
            print("RESULT XDR: " . $statusResponse->resultXdr . PHP_EOL);
        }
        print("Meta XDR: " . $statusResponse->resultMetaXdr . PHP_EOL);

        $this->assertEquals(GetTransactionResponse::STATUS_SUCCESS, $statusResponse->status);

    }

    private function transfer(bool $expectSuccess, SorobanServer $server, Keypair $submitterKp,
                              String $accContractId, String $accContractWasmId, String $tokenContractId,
                              int $amount, string $toAccountId, ?KeyPair $signer1 = null,
                              ?KeyPair $signer2 = null) : void
    {
        sleep(5);
        // see https://soroban.stellar.org/docs/reference/interfaces/token-interface
        $submitterId = $submitterKp->getAccountId();
        $account = $this->server->getAccount($submitterId);
        assertNotNull($account);

        $functionName = "transfer";

        $invokerAddress = Address::fromContractId($accContractId);
        $toAddress = Address::fromAccountId($toAccountId);
        $amountI128 = XdrSCVal::forI128Parts(0,$amount);
        $args = [$invokerAddress->toXdrSCVal(), $toAddress->toXdrSCVal(), $amountI128];

        $invokeContractHostFunction = new InvokeContractHostFunction($tokenContractId, $functionName, $args);
        $builder = new InvokeHostFunctionOperationBuilder($invokeContractHostFunction);
        $op = $builder->build();

        $transaction = (new TransactionBuilder($account))
            ->addOperation($op)->build();

        $request = new SimulateTransactionRequest($transaction);
        $simulateResponse = $server->simulateTransaction($request);
        $transactionData = $simulateResponse->getTransactionData();

        // add some resources because preflight can not take __check_auth into account
        $transactionData->resources->diskReadBytes *= 3;
        $transactionData->resources->instructions *= 4;
        $transactionData->resourceFee += 1500800;
        $simulateResponse->minResourceFee += 1500800;

        // we need to extend the footprint, so that __check_auth can read from storage
        $readOwnersKey = new XdrLedgerKey(XdrLedgerEntryType::CONTRACT_DATA());
        $addr = Address::fromContractId($accContractId)->toXdr();
        $readOwnersKey->contractData = new XdrLedgerKeyContractData($addr, XdrSCVal::forSymbol("Owners"),
            XdrContractDataDurability::PERSISTENT());

        $readLimitKey = new XdrLedgerKey(XdrLedgerEntryType::CONTRACT_DATA());
        $readLimitKey->contractData = new XdrLedgerKeyContractData($addr, XdrSCVal::forSymbol("SpendMax"),
            XdrContractDataDurability::PERSISTENT());

        array_push($transactionData->resources->footprint->readOnly , $readOwnersKey);
        array_push($transactionData->resources->footprint->readOnly , $readLimitKey);

        // set the transaction data  + fee and sign
        $transaction->setSorobanTransactionData($transactionData);
        $transaction->addResourceFee($simulateResponse->minResourceFee);

        $auth = $simulateResponse->getSorobanAuth();
        $this->assertNotNull($auth);

        $latestLedgerResponse = $server->getLatestLedger();
        $this->assertNotNull($latestLedgerResponse->sequence);
        foreach ($auth as $a) {
            if ($a instanceof  SorobanAuthorizationEntry) {
                $this->assertNotNull($a->credentials->addressCredentials);
                // increase signature expiration ledger
                $a->credentials->addressCredentials->signatureExpirationLedger = $latestLedgerResponse->sequence + 10;
                // sign
                if ($signer1 != null) {
                    $a->sign($signer1, $this->network);
                }
                if ($signer2 != null) {
                    $a->sign($signer2, $this->network);
                }
            } else {
                self::fail("invalid auth");
            }
        }
        $transaction->setSorobanAuth($auth);

        $transaction->sign($submitterKp, $this->network);

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->hash);
        /*if ($statusResponse->status == GetTransactionResponse::STATUS_FAILED) {
            print("ERR RESULT XDR: " . $statusResponse->resultXdr . PHP_EOL);
        }*/
        if ($expectSuccess) {
            $this->assertEquals(GetTransactionResponse::STATUS_SUCCESS, $statusResponse->status);
        } else {
            $this->assertEquals(GetTransactionResponse::STATUS_FAILED, $statusResponse->status);
        }
    }

    private function deployContract(SorobanServer $server, String $pathToCode, KeyPair $submitterKp, ?array $constructorArgs = null) : array {
        sleep(5);
        $result = array();

        $this->restoreContractFootprint($server, $submitterKp, $pathToCode);

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
        $simulateResponse = $server->simulateTransaction($request);


        // set the transaction data + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->sign($submitterKp, $this->network);

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->hash);
        $this->assertNotNull($statusResponse);
        $wasmId = $statusResponse->getWasmId();
        $this->assertNotNull($wasmId);
        array_push($result, $wasmId);

        $this->bumpContractCodeFootprint($server, $submitterKp, $wasmId, 100000);

        // create contract
        if ($constructorArgs != null) {
            $createContractHostFunction = new CreateContractWithConstructorHostFunction(Address::fromAccountId($submitterId), $wasmId, $constructorArgs);
        } else {
            $createContractHostFunction = new CreateContractHostFunction(Address::fromAccountId($submitterId), $wasmId);
        }

        $builder = new InvokeHostFunctionOperationBuilder($createContractHostFunction);
        $op = $builder->build();

        sleep(5);
        $account = $this->server->getAccount($submitterId);
        assertNotNull($account);
        $transaction = (new TransactionBuilder($account))
            ->addOperation($op)->build();

        // simulate first to get the transaction data and resource fee
        $request = new SimulateTransactionRequest($transaction);
        $simulateResponse = $server->simulateTransaction($request);

        // set the transaction data + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->setSorobanAuth($simulateResponse->getSorobanAuth());
        $transaction->sign($submitterKp, $this->network);

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->hash);
        $this->assertNotNull($statusResponse);
        $contractId = $statusResponse->getCreatedContractId();
        $this->assertNotNull($contractId);
        array_push($result, $contractId);
        return $result;
    }

    private function mint(SorobanServer $server, Keypair $submitterKp, String $contractId, XdrSCVal $toAddress, int $amount) : void
    {
        sleep(5);

        // see https://soroban.stellar.org/docs/reference/interfaces/token-interface
        $submitterId = $submitterKp->getAccountId();

        $amountValue = XdrSCVal::forI128(new XdrInt128Parts(0, $amount));
        $functionName = "mint";

        $args = [$toAddress, $amountValue];

        $invokeContractHostFunction = new InvokeContractHostFunction($contractId, $functionName, $args);
        $builder = new InvokeHostFunctionOperationBuilder($invokeContractHostFunction);
        $op = $builder->build();

        // reload account for sequence number
        $account = $this->server->getAccount($submitterId);
        assertNotNull($account);
        $transaction = (new TransactionBuilder($account))->addOperation($op)->build();

        $request = new SimulateTransactionRequest($transaction);
        $simulateResponse = $server->simulateTransaction($request);
        // set the transaction data  + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->setSorobanAuth($simulateResponse->getSorobanAuth());
        $transaction->sign($submitterKp, $this->network);


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
        $submitterId = $submitterKp->getAccountId();

        $functionName = "balance";

        $args = [$address];

        $invokeContractHostFunction = new InvokeContractHostFunction($contractId, $functionName, $args);
        $builder = new InvokeHostFunctionOperationBuilder($invokeContractHostFunction);
        $op = $builder->build();

        $account = $this->server->getAccount($submitterId);
        assertNotNull($account);
        $transaction = (new TransactionBuilder($account))
            ->addOperation($op)->build();

        $request = new SimulateTransactionRequest($transaction);
        $simulateResponse = $server->simulateTransaction($request);
        // set the transaction data  + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->sign($submitterKp, $this->network);

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


    private function restoreContractFootprint(SorobanServer $server, KeyPair $accountKeyPair, string $contractCodePath) : void {
        sleep(5);

        $contractCode = file_get_contents($contractCodePath, false);
        $uploadContractHostFunction = new UploadContractWasmHostFunction($contractCode);
        $op = (new InvokeHostFunctionOperationBuilder($uploadContractHostFunction))->build();

        $accountAId = $accountKeyPair->getAccountId();
        $account = $this->server->getAccount($accountAId);
        assertNotNull($account);
        $transaction = (new TransactionBuilder($account))
            ->addOperation($op)->build();

        $request = new SimulateTransactionRequest($transaction);
        $simulateResponse = $server->simulateTransaction($request);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);
        $this->assertNotNull($simulateResponse->getTransactionData());

        $transactionData = $simulateResponse->getTransactionData();
        $transactionData->resources->footprint->readWrite = $transactionData->resources->footprint->readWrite + $transactionData->resources->footprint->readOnly;
        $transactionData->resources->footprint->readOnly = array();

        $account = $this->server->getAccount($accountAId);
        assertNotNull($account);
        $restoreOp = (new RestoreFootprintOperationBuilder())->build();
        $transaction = (new TransactionBuilder($account))
            ->addOperation($restoreOp)->build();

        $transaction->setSorobanTransactionData($transactionData) ;
        $request = new SimulateTransactionRequest($transaction);
        $simulateResponse = $server->simulateTransaction($request);

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
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNotNull($sendResponse->hash);
        $this->assertNotNull($sendResponse->status);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->hash);
        $this->assertNotNull($statusResponse);
        $this->assertEquals(GetTransactionResponse::STATUS_SUCCESS, $statusResponse->status);
    }

    private function bumpContractCodeFootprint(SorobanServer $server, KeyPair $accountKeyPair, string $wasmId, int $extendTo) : void {
        sleep(5);

        $builder = new ExtendFootprintTTLOperationBuilder($extendTo);
        $bumpOp = $builder->build();

        $accountAId = $accountKeyPair->getAccountId();
        $account = $this->server->getAccount($accountAId);
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

        $transaction->setSorobanTransactionData($transactionData);
        $request = new SimulateTransactionRequest($transaction);
        $simulateResponse = $server->simulateTransaction($request);

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
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNull($sendResponse->error);
        $this->assertNotNull($sendResponse->hash);
        $this->assertNotNull($sendResponse->status);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->hash);
        $this->assertNotNull($statusResponse);
        $this->assertEquals(GetTransactionResponse::STATUS_SUCCESS, $statusResponse->status);
    }
}