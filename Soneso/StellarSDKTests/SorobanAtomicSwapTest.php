<?php  declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\BumpFootprintExpirationOperationBuilder;
use Soneso\StellarSDK\CreateContractHostFunction;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\RestoreFootprintOperationBuilder;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\SendTransactionResponse;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\UploadContractWasmHostFunction;
use Soneso\StellarSDK\Util\FuturenetFriendBot;
use Soneso\StellarSDK\Xdr\XdrContractEntryBodyType;
use Soneso\StellarSDK\Xdr\XdrExtensionPoint;
use Soneso\StellarSDK\Xdr\XdrInt128Parts;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryType;
use Soneso\StellarSDK\Xdr\XdrLedgerFootprint;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyContractCode;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValType;
use Soneso\StellarSDK\Xdr\XdrSorobanResources;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionData;

// See https://soroban.stellar.org/docs/advanced-tutorials/atomic-swap
// See: https://soroban.stellar.org/docs/fundamentals-and-concepts/authorization
class SorobanAtomicSwapTest extends TestCase
{

    const SWAP_CONTRACT_PATH = './wasm/soroban_atomic_swap_contract.wasm';
    const TOKEN_CONTRACT_PATH = './wasm/soroban_token_contract.wasm';

    public function testAtomicSwap() {

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

        $atomicSwapContractId = $this->deployContract($server,self::SWAP_CONTRACT_PATH, $adminKeyPair);
        //print("atomic swap cid: " . $atomicSwapContractId . PHP_EOL);

        $tokenAContractId = $this->deployContract($server,self::TOKEN_CONTRACT_PATH, $adminKeyPair);
        //print("token a cid: " . $tokenAContractId . PHP_EOL);
        $tokenBContractId = $this->deployContract($server,self::TOKEN_CONTRACT_PATH, $adminKeyPair);
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

        $amountA = XdrSCVal::forI128(new XdrInt128Parts(0,1000));
        $minBForA = XdrSCVal::forI128(new XdrInt128Parts(0,4500));

        $amountB = XdrSCVal::forI128(new XdrInt128Parts(0,5000));
        $minAForB = XdrSCVal::forI128(new XdrInt128Parts(0,950));

        $swapFunctionName = "swap";

        $invokeContract = [
            $addressAlice,
            $addressBob,
            Address::fromContractId($tokenAContractId)->toXdrSCVal(),
            Address::fromContractId($tokenBContractId)->toXdrSCVal(),
            $amountA,
            $minBForA,
            $amountB,
            $minAForB
         ];

        $invokeContractHostFunction = new InvokeContractHostFunction($atomicSwapContractId, $swapFunctionName, $invokeContract);
        $builder = new InvokeHostFunctionOperationBuilder($invokeContractHostFunction);
        $op = $builder->build();

        $source = $sdk->requestAccount($adminId);
        $transaction = (new TransactionBuilder($source))->addOperation($op)->build();

        $simulateResponse = $server->simulateTransaction($transaction);

        // set the transaction data  + fee and sign
        // this is because in preview 9 the fee calculation from the simulation is not always accurate
        // see: https://discord.com/channels/897514728459468821/1112853306881081354
        $transactionData = $simulateResponse->getTransactionData();
        $transactionData->resources->instructions += intval($transactionData->resources->instructions / 4);
        $simulateResponse->minResourceFee += 1005000;

        $transaction->setSorobanTransactionData($transactionData);
        $transaction->addResourceFee($simulateResponse->minResourceFee);

        // sign auth
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
                if ($a->credentials->addressCredentials->address->accountId == $aliceId) {
                    $a->sign($aliceKeyPair, Network::futurenet());
                }
                if ($a->credentials->addressCredentials->address->accountId == $bobId) {
                    $a->sign($bobKeyPair, Network::futurenet());
                }
            } else {
                self::fail("invalid auth");
            }
        }
        $transaction->setSorobanAuth($auth);

        // sign transaction
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

        $this->restoreContractFootprint($server, $submitterKp, $pathToCode);

        // upload contract wasm
        $contractCode = file_get_contents($pathToCode, false);

        $uploadContractHostFunction = new UploadContractWasmHostFunction($contractCode);
        $builder = new InvokeHostFunctionOperationBuilder($uploadContractHostFunction);
        $op = $builder->build();

        sleep(5);
        $submitterId = $submitterKp->getAccountId();
        $account = $sdk->requestAccount($submitterId);
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

        $this->bumpContractCodeFootprint($server, $submitterKp, $wasmId, 100000);

        // create contract
        $createContractHostFunction = new CreateContractHostFunction(Address::fromAccountId($submitterId), $wasmId);
        $builder = new InvokeHostFunctionOperationBuilder($createContractHostFunction);
        $op = $builder->build();

        sleep(5);
        $account = $sdk->requestAccount($submitterId);
        $transaction = (new TransactionBuilder($account))
            ->addOperation($op)->build();

        // simulate first to get the transaction data and resource fee
        $simulateResponse = $server->simulateTransaction($transaction);

        // set the transaction data + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->setSorobanAuth($simulateResponse->getSorobanAuth());
        $transaction->sign($submitterKp, Network::futurenet());

        // send the transaction
        $sendResponse = $server->sendTransaction($transaction);
        $this->assertNotEquals(SendTransactionResponse::STATUS_ERROR, $sendResponse->status);

        // poll until status is success or error
        $statusResponse = $this->pollStatus($server, $sendResponse->hash);
        $this->assertNotNull($statusResponse);
        $contractId = $statusResponse->getCreatedContractId();
        $this->assertNotNull($contractId);
        return $contractId;
    }

    private function createToken(SorobanServer $server, Keypair $submitterKp, String $contractId, String $name, String $symbol) : void
    {
        // see https://soroban.stellar.org/docs/reference/interfaces/token-interface
        $sdk = StellarSDK::getFutureNetInstance();
        $submitterId = $submitterKp->getAccountId();

        $adminAddress = Address::fromAccountId($submitterId)->toXdrSCVal();
        $functionName = "initialize";

        $tokenName = XdrSCVal::forString($name);
        $tokenSymbol = XdrSCVal::forString($symbol);

        $args = [$adminAddress, XdrSCVal::forU32(8), $tokenName, $tokenSymbol];

        $invokeContractHostFunction = new InvokeContractHostFunction($contractId, $functionName, $args);
        $builder = new InvokeHostFunctionOperationBuilder($invokeContractHostFunction);
        $op = $builder->build();

        sleep(5);
        // reload account for sequence number
        $account = $sdk->requestAccount($submitterId);
        $transaction = (new TransactionBuilder($account))
            ->addOperation($op)->build();

        $simulateResponse = $server->simulateTransaction($transaction);

        // set the transaction data  + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->setSorobanAuth($simulateResponse->getSorobanAuth());
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
        $sdk = StellarSDK::getFutureNetInstance();
        $submitterId = $submitterKp->getAccountId();


        $adminAddress = Address::fromAccountId($submitterId)->toXdrSCVal();
        $toAddress = Address::fromAccountId($toAccountId)->toXdrSCVal();
        $amountValue = XdrSCVal::forI128(new XdrInt128Parts(0,$amount));
        $functionName = "mint";

        $args = [$toAddress, $amountValue];

        $invokeContractHostFunction = new InvokeContractHostFunction($contractId, $functionName, $args);
        $builder = new InvokeHostFunctionOperationBuilder($invokeContractHostFunction);
        $op = $builder->build();

        // reload account for sequence number
        $account = $sdk->requestAccount($submitterId);
        $transaction = (new TransactionBuilder($account))->addOperation($op)->build();

        $simulateResponse = $server->simulateTransaction($transaction);
        // set the transaction data  + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->minResourceFee);
        $transaction->setSorobanAuth($simulateResponse->getSorobanAuth());
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
        $sdk = StellarSDK::getFutureNetInstance();
        $submitterId = $submitterKp->getAccountId();

        $address = Address::fromAccountId($accountId)->toXdrSCVal();
        $functionName = "balance";

        $args = [$address];

        $invokeContractHostFunction = new InvokeContractHostFunction($contractId, $functionName, $args);
        $builder = new InvokeHostFunctionOperationBuilder($invokeContractHostFunction);
        $op = $builder->build();

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
        $sdk = StellarSDK::getFutureNetInstance();

        $contractCode = file_get_contents($contractCodePath, false);
        $uploadContractHostFunction = new UploadContractWasmHostFunction($contractCode);
        $op = (new InvokeHostFunctionOperationBuilder($uploadContractHostFunction))->build();

        $accountAId = $accountKeyPair->getAccountId();
        $getAccountResponse = $sdk->requestAccount($accountAId);
        $transaction = (new TransactionBuilder($getAccountResponse))
            ->addOperation($op)->build();

        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);
        $this->assertNotNull($simulateResponse->results);
        $this->assertNotNull($simulateResponse->getTransactionData());

        $transactionData = $simulateResponse->getTransactionData();
        $transactionData->resources->footprint->readWrite = $transactionData->resources->footprint->readWrite + $transactionData->resources->footprint->readOnly;
        $transactionData->resources->footprint->readOnly = array();

        $getAccountResponse = $sdk->requestAccount($accountAId);
        $restoreOp = (new RestoreFootprintOperationBuilder())->build();
        $transaction = (new TransactionBuilder($getAccountResponse))
            ->addOperation($restoreOp)->build();

        $transaction->setSorobanTransactionData($transactionData) ;
        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);
        $this->assertNotNull($simulateResponse->results);
        $this->assertNotNull($simulateResponse->getTransactionData());
        $this->assertNotNull($simulateResponse->getMinResourceFee());

        // set the transaction data + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->getMinResourceFee());
        $transaction->sign($accountKeyPair, Network::futurenet());

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

    private function bumpContractCodeFootprint(SorobanServer $server, KeyPair $accountKeyPair, string $wasmId, int $ledgersToExpire) : void {
        sleep(5);
        $sdk = StellarSDK::getFutureNetInstance();

        $builder = new BumpFootprintExpirationOperationBuilder($ledgersToExpire);
        $bumpOp = $builder->build();

        $accountAId = $accountKeyPair->getAccountId();
        $getAccountResponse = $sdk->requestAccount($accountAId);
        $transaction = (new TransactionBuilder($getAccountResponse))
            ->addOperation($bumpOp)->build();

        $readOnly = array();
        $readWrite = array();
        $codeKey = new XdrLedgerKey(XdrLedgerEntryType::CONTRACT_CODE());
        $codeKey->contractCode = new XdrLedgerKeyContractCode(hex2bin($wasmId), XdrContractEntryBodyType::DATA_ENTRY());
        array_push($readOnly, $codeKey);

        $footprint = new XdrLedgerFootprint($readOnly, $readWrite);
        $resources = new XdrSorobanResources($footprint, 0,0,0,0);
        $transactionData = new XdrSorobanTransactionData(new XdrExtensionPoint(0), $resources, 0);

        $transaction->setSorobanTransactionData($transactionData) ;
        $simulateResponse = $server->simulateTransaction($transaction);

        $this->assertNull($simulateResponse->error);
        $this->assertNull($simulateResponse->resultError);
        $this->assertNotNull($simulateResponse->results);
        $this->assertNotNull($simulateResponse->getTransactionData());
        $this->assertNotNull($simulateResponse->getMinResourceFee());

        // set the transaction data + fee and sign
        $transaction->setSorobanTransactionData($simulateResponse->getTransactionData());
        $transaction->addResourceFee($simulateResponse->getMinResourceFee());
        $transaction->sign($accountKeyPair, Network::futurenet());

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