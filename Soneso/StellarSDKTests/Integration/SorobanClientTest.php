<?php  declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Integration;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\Contract\ClientOptions;
use Soneso\StellarSDK\Soroban\Contract\ContractSpec;
use Soneso\StellarSDK\Soroban\Contract\DeployRequest;
use Soneso\StellarSDK\Soroban\Contract\InstallRequest;
use Soneso\StellarSDK\Soroban\Contract\NativeUnionVal;
use Soneso\StellarSDK\Soroban\Contract\SorobanClient;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;
use Soneso\StellarSDK\Util\FriendBot;
use Soneso\StellarSDK\Util\FuturenetFriendBot;
use Soneso\StellarSDKTests\PrintLogger;
use Soneso\StellarSDK\Xdr\XdrInt128Parts;
use Soneso\StellarSDK\Xdr\XdrInt256Parts;
use Soneso\StellarSDK\Xdr\XdrSCSpecEntry;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeBytesN;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeDef;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeMap;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeOption;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeTuple;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeUDT;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeVec;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTEnumCaseV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTEnumV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTStructFieldV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTStructV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionCaseTupleV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionCaseV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionCaseVoidV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionV0;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValType;
use Soneso\StellarSDK\Xdr\XdrUInt128Parts;
use Soneso\StellarSDKTests\bindings\AtomicSwapContract;
use Soneso\StellarSDKTests\bindings\AuthContract;
use Soneso\StellarSDKTests\bindings\HelloContract;
use Soneso\StellarSDKTests\bindings\TokenContract;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotNull;

class SorobanClientTest  extends TestCase
{
    const HELLO_CONTRACT_PATH = './../wasm/soroban_hello_world_contract.wasm';
    const AUTH_CONTRACT_PATH = './../wasm/soroban_auth_contract.wasm';
    const SWAP_CONTRACT_PATH = './../wasm/soroban_atomic_swap_contract.wasm';
    const TOKEN_CONTRACT_PATH = './../wasm/soroban_token_contract.wasm';
    const TESTNET_RPC_URL = "https://soroban-testnet.stellar.org";
    const FUTURENET_RPC_URL = "https://rpc-futurenet.stellar.org";

    private string $testOn = 'testnet'; // 'futurenet'
    private Network $network;
    private KeyPair $sourceAccountKeyPair;

    public function setUp(): void
    {
        // Turn on error reporting
        error_reporting(E_ALL);
        $this->sourceAccountKeyPair = KeyPair::random();
        print("Signer seed: " . $this->sourceAccountKeyPair->getSecretSeed() . PHP_EOL);
        if ($this->testOn === 'testnet') {
            $this->network = Network::testnet();
            FriendBot::fundTestAccount($this->sourceAccountKeyPair->getAccountId());
        } elseif ($this->testOn === 'futurenet') {
            $this->network = Network::futurenet();
            FuturenetFriendBot::fundTestAccount($this->sourceAccountKeyPair->getAccountId());
        }
    }

    public function testHelloContract() {

        $helloContractWasmHash = $this->installContract(self::HELLO_CONTRACT_PATH);
        print("Installed hello contract wasm hash: {$helloContractWasmHash}" . PHP_EOL);

        $client = $this->deployContract($helloContractWasmHash);
        print("Deployed hello contract contract id: {$client->getContractId()}" . PHP_EOL);

        $methodNames = $client->getMethodNames();
        self::assertCount(1, $methodNames);
        assertEquals("hello", $methodNames[0]);

        $result = $client->invokeMethod(name: "hello", args: [XdrSCVal::forSymbol("John")]);
        assertNotNull($result->vec);
        self::assertCount(2, $result->vec);
        $resultVal = $result->vec[0]->sym . ", " . $result->vec[1]->sym;
        self::assertEquals("Hello, John", $resultVal);

        // contract spec test
        $spec = new ContractSpec($client->getSpecEntries());
        $args = $spec->funcArgsToXdrSCValues(name: "hello",args: ["to" => "Maria"]);
        self::assertCount(1, $args);
        $result = $client->invokeMethod(name: "hello", args: $args);
        assertNotNull($result->vec);
        self::assertCount(2, $result->vec);
        $resultVal = $result->vec[0]->sym . ", " . $result->vec[1]->sym;
        self::assertEquals("Hello, Maria", $resultVal);
    }

    public function testAuthContract()
    {
        $authContractWasmHash = $this->installContract(self::AUTH_CONTRACT_PATH);
        print("Installed auth contract wasm hash: {$authContractWasmHash}" . PHP_EOL);

        $deployedClient = $this->deployContract($authContractWasmHash);
        print("Deployed auth contract contract id: {$deployedClient->getContractId()}" . PHP_EOL);

        // just a small test to check if it can load by contract id
        $rpcUrl = $this->testOn == "testnet" ? self::TESTNET_RPC_URL: self::FUTURENET_RPC_URL;
        $client = SorobanClient::forClientOptions(new ClientOptions(
            sourceAccountKeyPair: $this->sourceAccountKeyPair,
            contractId: $deployedClient->getContractId(),
            network: $this->network,
            rpcUrl: $rpcUrl,
            logger: new PrintLogger())
        );
        assertEquals($deployedClient->getContractId(), $client->getContractId());

        $methodName = "increment";

        $methodNames = $client->getMethodNames();
        self::assertCount(1, $methodNames);
        assertEquals($methodName, $methodNames[0]);

        // submitter and invoker use are the same
        // no need to sign auth

        $invokerAccountId = $this->sourceAccountKeyPair->getAccountId();
        $spec = new ContractSpec($client->getSpecEntries());
        $args = $spec->funcArgsToXdrSCValues(name: $methodName, args: ["user" => $invokerAccountId, "value" => 3]);
        $result = $client->invokeMethod(name: $methodName, args: $args);
        assertEquals(3, $result->u32);

        // submitter and invoker use are NOT the same
        // we need to sign the auth entry

        $invokerKeyPair = KeyPair::random();
        if ($this->testOn === 'testnet') {
            FriendBot::fundTestAccount($invokerKeyPair->getAccountId());
        } elseif ($this->testOn === 'futurenet') {
            FuturenetFriendBot::fundTestAccount($invokerKeyPair->getAccountId());
        }

        $invokerAccountId = $invokerKeyPair->getAccountId();
        $args = $spec->funcArgsToXdrSCValues(name: $methodName,
            args: ["user" => $invokerAccountId, "value" => 4]);

        try {
            $client->invokeMethod(name: $methodName, args: $args);
            self::fail("should not reach here!");
        } catch (Exception $e) {
            print($e->getMessage() . PHP_EOL);
        }

        $tx = $client->buildInvokeMethodTx(name: $methodName, args: $args);
        $tx->signAuthEntries(signerKeyPair: $invokerKeyPair);
        $response = $tx->signAndSend();

        $result = $response->getResultValue();
        assertNotNull($result);
        assertEquals(4, $result->u32);

    }

    public function testAtomicSwap()
    {
        $swapContractWasmHash = $this->installContract(self::SWAP_CONTRACT_PATH);
        print("Installed swap contract wasm hash: {$swapContractWasmHash}" . PHP_EOL);

        $tokenContractWasmHash = $this->installContract(self::TOKEN_CONTRACT_PATH);
        print("Installed token contract wasm hash: {$tokenContractWasmHash}" . PHP_EOL);

        $adminKeyPair = KeyPair::random();
        $aliceKeyPair = KeyPair::random();
        $aliceId = $aliceKeyPair->getAccountId();
        $bobKeyPair = KeyPair::random();
        $bobId = $bobKeyPair->getAccountId();

        if ($this->testOn === 'testnet') {
            FriendBot::fundTestAccount($adminKeyPair->getAccountId());
            FriendBot::fundTestAccount($aliceId);
            FriendBot::fundTestAccount($bobId);
        } elseif ($this->testOn === 'futurenet') {
            FuturenetFriendBot::fundTestAccount($adminKeyPair->getAccountId());
            FuturenetFriendBot::fundTestAccount($aliceId);
            FuturenetFriendBot::fundTestAccount($bobId);
        }

        print("admin: " . $adminKeyPair->getSecretSeed() .  " : " . $adminKeyPair->getAccountId(). PHP_EOL);
        print("alice: " . $aliceKeyPair->getSecretSeed() .  " : " . $aliceKeyPair->getAccountId(). PHP_EOL);
        print("bob: " . $bobKeyPair->getSecretSeed() .  " : " . $bobKeyPair->getAccountId(). PHP_EOL);

        $atomicSwapClient = $this->deployContract($swapContractWasmHash);
        print("Deployed atomic swap contract contract id: {$atomicSwapClient->getContractId()}" . PHP_EOL);

        $adminAddress = Address::fromAccountId($adminKeyPair->getAccountId())->toXdrSCVal();
        $tokenName = XdrSCVal::forString("TokenA");
        $tokenSymbol = XdrSCVal::forString("TokenA");
        $decimal = XdrSCVal::forU32(8);

        $tokenAClient = $this->deployContract($tokenContractWasmHash, constructorArgs: [$adminAddress, $decimal, $tokenName, $tokenSymbol]);
        $tokenAContractId = $tokenAClient->getContractId();
        print("Deployed token A contract contract id: {$tokenAContractId}" . PHP_EOL);

        $tokenName = XdrSCVal::forString("TokenB");
        $tokenSymbol = XdrSCVal::forString("TokenB");
        $tokenBClient = $this->deployContract($tokenContractWasmHash, constructorArgs: [$adminAddress, $decimal, $tokenName, $tokenSymbol]);
        $tokenBContractId = $tokenBClient->getContractId();
        print("Deployed token B contract contract id: {$tokenBContractId}" . PHP_EOL);

        $this->mint($tokenAClient, $adminKeyPair, $aliceId, 10000000000000);
        $this->mint($tokenBClient, $adminKeyPair, $bobId, 10000000000000);
        print("Alice and Bob funded");

        $aliceTokenABalance = $this->readBalance($aliceId, $tokenAClient);
        $this->assertEquals(10000000000000, $aliceTokenABalance);

        $bobTokenBBalance = $this->readBalance($bobId, $tokenBClient);
        $this->assertEquals(10000000000000, $bobTokenBBalance);

        $swapMethodName = "swap";

        $spec = new ContractSpec($atomicSwapClient->getSpecEntries());
        $args = $spec->funcArgsToXdrSCValues(name: $swapMethodName, args: [
            "a" => $aliceId,
            "b" => $bobId,
            "token_a" => $tokenAContractId,
            "token_b" => $tokenBContractId,
            "amount_a" => 1000,
            "min_b_for_a" => 4500,
            "amount_b" => 5000,
            "min_a_for_b" => 950
        ]);

        $tx = $atomicSwapClient->buildInvokeMethodTx(name: $swapMethodName, args: $args);

        $whoElseNeedsToSign = $tx->needsNonInvokerSigningBy();
        self::assertCount(2, $whoElseNeedsToSign);
        self::assertContains($aliceId, $whoElseNeedsToSign);
        self::assertContains($bobId, $whoElseNeedsToSign);

        $tx->signAuthEntries(signerKeyPair: $aliceKeyPair);
        print("Signed by Alice");

        // test signing via callback
        $bobPublicKeyKeyPair = KeyPair::fromAccountId($bobId);
        $tx->signAuthEntries(signerKeyPair: $bobPublicKeyKeyPair, authorizeEntryCallback: function (SorobanAuthorizationEntry $entry, Network $network) use ($bobKeyPair): SorobanAuthorizationEntry  {
                print("Bob is signing");
                // You can send it to some other server for signing by encoding it as a base64xdr string
                $base64Entry = $entry->toBase64Xdr();
                // send for signing ...
                // and on the other server you can decode it:
                $entryToSign = SorobanAuthorizationEntry::fromBase64Xdr($base64Entry);
                // sign it
                $entryToSign->sign($bobKeyPair, $network);
                // encode as a base64xdr string and send it back
                $signedBase64Entry = $entryToSign->toBase64Xdr();
                print("Bob signed");
                // here you can now decode it and return it
                return SorobanAuthorizationEntry::fromBase64Xdr($signedBase64Entry);
            },
        );
        print("Signed by Bob");
        $response = $tx->signAndSend();
        $result = $response->getResultValue();
        $this->assertEquals(XdrSCValType::SCV_VOID, $result->type->value);
        print("Swap done");

        // small spec functions test
        $spec = new ContractSpec($tokenAClient->getSpecEntries());
        $functions = $spec->funcs();
        $this->assertCount(13, $functions);
        $func = $spec->getFunc("mint");
        $this->assertEquals("mint", $func->name);
    }

    public function testNativeToXdrSCVal() {
        // void
        $def = XdrSCSpecTypeDef::VOID();
        $spec = new ContractSpec([]);
        $val = $spec->nativeToXdrSCVal(null, $def);
        $this->assertEquals(XdrSCValType::SCV_VOID, $val->type->value);

        // address
        $keyPair = KeyPair::random();
        $accountId = $keyPair->getAccountId();
        $contractId = "CCCZVCWISWKWZ3NNH737WGOVCDUI3P776QE3ZM7AUWMJKQBHCPW7NW3D";
        $def = XdrSCSpecTypeDef::ADDRESS();
        $val = $spec->nativeToXdrSCVal(Address::fromAccountId($accountId), $def);
        $this->assertEquals(XdrSCValType::SCV_ADDRESS, $val->type->value);
        $val = $spec->nativeToXdrSCVal($accountId, $def);
        $this->assertEquals(XdrSCValType::SCV_ADDRESS, $val->type->value);
        $val = $spec->nativeToXdrSCVal(Address::fromContractId($contractId), $def);
        $this->assertEquals(XdrSCValType::SCV_ADDRESS, $val->type->value);
        $val = $spec->nativeToXdrSCVal($contractId, $def);
        $this->assertEquals(XdrSCValType::SCV_ADDRESS, $val->type->value);

        // vec
        $def = XdrSCSpecTypeDef::forVec(new XdrSCSpecTypeVec(elementType: XdrSCSpecTypeDef::SYMBOL()));
        $val = $spec->nativeToXdrSCVal(["a", "b"], $def);
        $this->assertEquals(XdrSCValType::SCV_VEC, $val->type->value);
        $this->assertCount(2, $val->vec);

        // map
        $map = new XdrSCSpecTypeMap(keyType: XdrSCSpecTypeDef::STRING(), valueType: XdrSCSpecTypeDef::ADDRESS());
        $def = XdrSCSpecTypeDef::forMap($map);
        $val = $spec->nativeToXdrSCVal(["a" => $accountId, "b" => $contractId], $def);
        $this->assertEquals(XdrSCValType::SCV_MAP, $val->type->value);
        $this->assertCount(2, $val->map);

        // tuple
        $tuple = new XdrSCSpecTypeTuple(valueTypes: [XdrSCSpecTypeDef::STRING(), XdrSCSpecTypeDef::BOOL()]);
        $def = XdrSCSpecTypeDef::forTuple($tuple);
        $val = $spec->nativeToXdrSCVal(["a", true], $def);
        $this->assertEquals(XdrSCValType::SCV_VEC, $val->type->value);
        $this->assertCount(2, $val->vec);

        // numbers
        $def = XdrSCSpecTypeDef::U32();
        $val = $spec->nativeToXdrSCVal(12, $def);
        $this->assertEquals(XdrSCValType::SCV_U32, $val->type->value);
        $this->assertEquals(12, $val->u32);

        $def = XdrSCSpecTypeDef::I32();
        $val = $spec->nativeToXdrSCVal(-12, $def);
        $this->assertEquals(XdrSCValType::SCV_I32, $val->type->value);
        $this->assertEquals(-12, $val->i32);

        $def = XdrSCSpecTypeDef::U64();
        $val = $spec->nativeToXdrSCVal(112, $def);
        $this->assertEquals(XdrSCValType::SCV_U64, $val->type->value);
        $this->assertEquals(112, $val->u64);

        $def = XdrSCSpecTypeDef::I64();
        $val = $spec->nativeToXdrSCVal(-112, $def);
        $this->assertEquals(XdrSCValType::SCV_I64, $val->type->value);
        $this->assertEquals(-112, $val->i64);

        // for > 128 only 64 positive numbers are supported
        $def = XdrSCSpecTypeDef::U128();
        $val = $spec->nativeToXdrSCVal(1112, $def);
        $this->assertEquals(XdrSCValType::SCV_U128, $val->type->value);
        $this->assertEquals(1112, $val->u128->lo);

        // bigger numbers must be passed as xdrscval
        $val = $spec->nativeToXdrSCVal(XdrSCVal::forU128(new XdrUInt128Parts(hi: 1230, lo:81881)), $def);
        $this->assertEquals(XdrSCValType::SCV_U128, $val->type->value);
        $this->assertEquals(1230, $val->u128->hi);
        $this->assertEquals(81881, $val->u128->lo);

        $def = XdrSCSpecTypeDef::I128();
        $val = $spec->nativeToXdrSCVal(2112, $def);
        $this->assertEquals(XdrSCValType::SCV_I128, $val->type->value);
        $this->assertEquals(2112, $val->i128->lo);

        $val = $spec->nativeToXdrSCVal(XdrSCVal::forI128(new XdrInt128Parts(hi: -1230, lo:81881)), $def);
        $this->assertEquals(XdrSCValType::SCV_I128, $val->type->value);
        $this->assertEquals(-1230, $val->i128->hi);
        $this->assertEquals(81881, $val->i128->lo);

        $def = XdrSCSpecTypeDef::U256();
        $val = $spec->nativeToXdrSCVal(3112, $def);
        $this->assertEquals(XdrSCValType::SCV_U256, $val->type->value);
        $this->assertEquals(3112, $val->u256->loLo);

        $def = XdrSCSpecTypeDef::I256();
        $val = $spec->nativeToXdrSCVal(3112, $def);
        $this->assertEquals(XdrSCValType::SCV_I256, $val->type->value);
        $this->assertEquals(3112, $val->i256->loLo);

        // strings (bytes, bytesN, string, symbol, address)
        $def = XdrSCSpecTypeDef::BYTES();
        $val = $spec->nativeToXdrSCVal($keyPair->getPublicKey(), $def);
        $this->assertEquals(XdrSCValType::SCV_BYTES, $val->type->value);

        $def = XdrSCSpecTypeDef::forBytesN(new XdrSCSpecTypeBytesN(n:32));
        $val = $spec->nativeToXdrSCVal($keyPair->getPublicKey(), $def);
        $this->assertEquals(XdrSCValType::SCV_BYTES, $val->type->value);

        $def = XdrSCSpecTypeDef::STRING();
        $val = $spec->nativeToXdrSCVal("hello this is a text", $def);
        $this->assertEquals(XdrSCValType::SCV_STRING, $val->type->value);

        $def = XdrSCSpecTypeDef::SYMBOL();
        $val = $spec->nativeToXdrSCVal("XLM", $def);
        $this->assertEquals(XdrSCValType::SCV_SYMBOL, $val->type->value);

        $def = XdrSCSpecTypeDef::ADDRESS();
        $val = $spec->nativeToXdrSCVal($accountId, $def);
        $this->assertEquals(XdrSCValType::SCV_ADDRESS, $val->type->value);
        $val = $spec->nativeToXdrSCVal($contractId, $def);
        $this->assertEquals(XdrSCValType::SCV_ADDRESS, $val->type->value);

        // bool
        $def = XdrSCSpecTypeDef::BOOL();
        $val = $spec->nativeToXdrSCVal(false, $def);
        $this->assertEquals(XdrSCValType::SCV_BOOL, $val->type->value);

        // void
        $def = XdrSCSpecTypeDef::VOID();
        $val = $spec->nativeToXdrSCVal(null, $def);
        $this->assertEquals(XdrSCValType::SCV_VOID, $val->type->value);

        // option
        $def = XdrSCSpecTypeDef::forOption(new XdrSCSpecTypeOption(valueType: XdrSCSpecTypeDef::STRING()));
        $val = $spec->nativeToXdrSCVal("a string", $def);
        $this->assertEquals(XdrSCValType::SCV_STRING, $val->type->value);
        $val = $spec->nativeToXdrSCVal(null, $def);
        $this->assertEquals(XdrSCValType::SCV_VOID, $val->type->value);

        // UDT (enum, struct, union)

        // enum
        $cases = [
            new XdrSCSpecUDTEnumCaseV0(doc:"", name:"a", value:1),
            new XdrSCSpecUDTEnumCaseV0(doc:"", name:"b", value:2),
            new XdrSCSpecUDTEnumCaseV0(doc:"", name:"c", value:3),
        ];
        $enum = new XdrSCSpecUDTEnumV0(doc: "", lib: "", name: "myEnum", cases: $cases);
        $entry = XdrSCSpecEntry::forUDTEnumV0($enum);
        $spec = new ContractSpec(entries:[$entry]);
        $def = XdrSCSpecTypeDef::forUDT(new XdrSCSpecTypeUDT(name: "myEnum"));
        $val = $spec->nativeToXdrSCVal(2, $def);
        $this->assertEquals(XdrSCValType::SCV_U32, $val->type->value);
        $this->assertEquals(2, $val->u32);

        // struct
        $fields = [
            new XdrSCSpecUDTStructFieldV0(doc:"", name:"field1", type: XdrSCSpecTypeDef::U32()),
            new XdrSCSpecUDTStructFieldV0(doc:"", name:"field2", type: XdrSCSpecTypeDef::U32()),
            new XdrSCSpecUDTStructFieldV0(doc:"", name:"field3", type: XdrSCSpecTypeDef::U32()),
        ];
        $struct = new XdrSCSpecUDTStructV0(doc:"", lib:"", name:"myStruct", fields: $fields);
        $entry = XdrSCSpecEntry::forUDTStructV0($struct);
        $spec = new ContractSpec(entries:[$entry]);
        $def = XdrSCSpecTypeDef::forUDT(new XdrSCSpecTypeUDT(name: "myStruct"));
        $val = $spec->nativeToXdrSCVal(["field1" => 1,"field2" => 2,"field3" => 3], $def);
        $this->assertEquals(XdrSCValType::SCV_MAP, $val->type->value);
        $this->assertCount(3, $val->map);

        // field names are all numeric
        $fields = [
            new XdrSCSpecUDTStructFieldV0(doc:"", name:"1", type: XdrSCSpecTypeDef::STRING()),
            new XdrSCSpecUDTStructFieldV0(doc:"", name:"2", type: XdrSCSpecTypeDef::STRING()),
            new XdrSCSpecUDTStructFieldV0(doc:"", name:"3", type: XdrSCSpecTypeDef::STRING()),
        ];
        $numericStruct = new XdrSCSpecUDTStructV0(doc:"", lib:"", name:"myNumericStruct", fields: $fields);
        $entry = XdrSCSpecEntry::forUDTStructV0($numericStruct);
        $spec = new ContractSpec(entries:[$entry]);
        $def = XdrSCSpecTypeDef::forUDT(new XdrSCSpecTypeUDT(name: "myNumericStruct"));
        $val = $spec->nativeToXdrSCVal(["one","two","three"], $def);
        $this->assertEquals(XdrSCValType::SCV_VEC, $val->type->value);
        $this->assertCount(3, $val->vec);

        // union
        $unionCases = [
            XdrSCSpecUDTUnionCaseV0::forVoidCase(new XdrSCSpecUDTUnionCaseVoidV0(doc:"", name:"voidCase")),
            XdrSCSpecUDTUnionCaseV0::forTupleCase(new XdrSCSpecUDTUnionCaseTupleV0(doc:"", name:"tupleCase",
                type:[XdrSCSpecTypeDef::STRING(), XdrSCSpecTypeDef::U32()]))
        ];
        $union = new XdrSCSpecUDTUnionV0(doc:"", lib:"", name:"myUnion", cases:$unionCases);
        $entry = XdrSCSpecEntry::forUDTUnionV0($union);
        $spec = new ContractSpec(entries:[$entry]);
        $def = XdrSCSpecTypeDef::forUDT(new XdrSCSpecTypeUDT(name: "myUnion"));
        $val = $spec->nativeToXdrSCVal(new NativeUnionVal("voidCase"), $def);
        $this->assertEquals(XdrSCValType::SCV_VEC, $val->type->value);
        $this->assertCount(1, $val->vec); // only key
        $val = $spec->nativeToXdrSCVal(new NativeUnionVal("tupleCase", values: ["a", 4]), $def);
        $this->assertEquals(XdrSCValType::SCV_VEC, $val->type->value);
        $this->assertCount(3, $val->vec); // key + 2 values (a,4)

        // Test BigInt functionality for U128
        print(PHP_EOL . "=== Testing BigInt functionality ===" . PHP_EOL);
        
        // U128 - test with small integer
        $def = XdrSCSpecTypeDef::U128();
        $val = $spec->nativeToXdrSCVal(42, $def);
        $this->assertEquals(XdrSCValType::SCV_U128, $val->type->value);
        $this->assertEquals(0, $val->u128->hi);
        $this->assertEquals(42, $val->u128->lo);
        print("✓ U128 with small integer" . PHP_EOL);
        
        // U128 - test with string BigInt
        $bigValue = "340282366920938463463374607431768211455"; // Max U128
        $val = $spec->nativeToXdrSCVal($bigValue, $def);
        $this->assertEquals(XdrSCValType::SCV_U128, $val->type->value);
        $bigInt = $val->toBigInt();
        $this->assertEquals($bigValue, gmp_strval($bigInt));
        print("✓ U128 with max value string" . PHP_EOL);
        
        // U128 - test with GMP object
        $gmpValue = gmp_init("123456789012345678901234567890");
        $val = $spec->nativeToXdrSCVal($gmpValue, $def);
        $this->assertEquals(XdrSCValType::SCV_U128, $val->type->value);
        $bigInt = $val->toBigInt();
        $this->assertEquals("123456789012345678901234567890", gmp_strval($bigInt));
        print("✓ U128 with GMP object" . PHP_EOL);
        
        // I128 - test with negative value
        $def = XdrSCSpecTypeDef::I128();
        $val = $spec->nativeToXdrSCVal(-12345, $def);
        $this->assertEquals(XdrSCValType::SCV_I128, $val->type->value);
        $bigInt = $val->toBigInt();
        $this->assertEquals("-12345", gmp_strval($bigInt));
        print("✓ I128 with negative integer" . PHP_EOL);
        
        // I128 - test with large negative string
        $negativeValue = "-85070591730234615865843651857942052863";
        $val = $spec->nativeToXdrSCVal($negativeValue, $def);
        $this->assertEquals(XdrSCValType::SCV_I128, $val->type->value);
        $bigInt = $val->toBigInt();
        $this->assertEquals($negativeValue, gmp_strval($bigInt));
        print("✓ I128 with large negative string" . PHP_EOL);
        
        // U256 - test with small integer
        $def = XdrSCSpecTypeDef::U256();
        $val = $spec->nativeToXdrSCVal(999, $def);
        $this->assertEquals(XdrSCValType::SCV_U256, $val->type->value);
        $this->assertEquals(0, $val->u256->hiHi);
        $this->assertEquals(0, $val->u256->hiLo);
        $this->assertEquals(0, $val->u256->loHi);
        $this->assertEquals(999, $val->u256->loLo);
        print("✓ U256 with small integer" . PHP_EOL);
        
        // U256 - test with huge string value
        $hugeValue = "99999999999999999999999999999999999999999999999999999999999999999999999999";
        $val = $spec->nativeToXdrSCVal($hugeValue, $def);
        $this->assertEquals(XdrSCValType::SCV_U256, $val->type->value);
        $bigInt = $val->toBigInt();
        $this->assertEquals($hugeValue, gmp_strval($bigInt));
        print("✓ U256 with huge string value" . PHP_EOL);
        
        // U256 - test with GMP power of 2
        $powerOf2 = gmp_pow(2, 200);
        $val = $spec->nativeToXdrSCVal($powerOf2, $def);
        $this->assertEquals(XdrSCValType::SCV_U256, $val->type->value);
        $bigInt = $val->toBigInt();
        $this->assertEquals(gmp_strval($powerOf2), gmp_strval($bigInt));
        print("✓ U256 with GMP 2^200" . PHP_EOL);
        
        // I256 - test with negative value
        $def = XdrSCSpecTypeDef::I256();
        $val = $spec->nativeToXdrSCVal(-999999, $def);
        $this->assertEquals(XdrSCValType::SCV_I256, $val->type->value);
        $bigInt = $val->toBigInt();
        $this->assertEquals("-999999", gmp_strval($bigInt));
        print("✓ I256 with negative integer" . PHP_EOL);
        
        // I256 - test with large negative GMP
        $largeNegative = gmp_neg(gmp_pow(2, 200));
        $val = $spec->nativeToXdrSCVal($largeNegative, $def);
        $this->assertEquals(XdrSCValType::SCV_I256, $val->type->value);
        $bigInt = $val->toBigInt();
        $this->assertEquals(gmp_strval($largeNegative), gmp_strval($bigInt));
        print("✓ I256 with negative GMP 2^200" . PHP_EOL);
        
        // Test backward compatibility with XdrUInt128Parts
        $def = XdrSCSpecTypeDef::U128();
        $parts = new XdrUInt128Parts(12345, 67890);
        $val = $spec->nativeToXdrSCVal($parts, $def);
        $this->assertEquals(XdrSCValType::SCV_U128, $val->type->value);
        $this->assertEquals(12345, $val->u128->hi);
        $this->assertEquals(67890, $val->u128->lo);
        print("✓ Backward compatibility with XdrUInt128Parts" . PHP_EOL);
        
        // Test backward compatibility with XdrInt256Parts
        $def = XdrSCSpecTypeDef::I256();
        $parts = new XdrInt256Parts(1, 2, 3, 4);
        $val = $spec->nativeToXdrSCVal($parts, $def);
        $this->assertEquals(XdrSCValType::SCV_I256, $val->type->value);
        $this->assertEquals(1, $val->i256->hiHi);
        $this->assertEquals(2, $val->i256->hiLo);
        $this->assertEquals(3, $val->i256->loHi);
        $this->assertEquals(4, $val->i256->loLo);
        print("✓ Backward compatibility with XdrInt256Parts" . PHP_EOL);
        
        // Test roundtrip conversion
        $def = XdrSCSpecTypeDef::U128();
        $originalValue = "123456789012345678901234567890123456";
        $val = $spec->nativeToXdrSCVal($originalValue, $def);
        $roundtrip = $val->toBigInt();
        $this->assertEquals($originalValue, gmp_strval($roundtrip));
        print("✓ Roundtrip conversion for U128" . PHP_EOL);
        
        // Test error handling - this should work now with BigInt support
        $def = XdrSCSpecTypeDef::I128();
        $largeStringValue = "99999999999999999999999999999";
        $val = $spec->nativeToXdrSCVal($largeStringValue, $def);
        $this->assertEquals(XdrSCValType::SCV_I128, $val->type->value);
        $bigInt = $val->toBigInt();
        $this->assertEquals($largeStringValue, gmp_strval($bigInt));
        print("✓ Large string values now work with BigInt support" . PHP_EOL);
        
        print("=== All BigInt tests passed! ===" . PHP_EOL);
    }

    /**
     * @throws GuzzleException
     */
    private function installContract(string $path): string {
        $contractCode = file_get_contents($path, false);

        $rpcUrl = $this->testOn == "testnet" ? self::TESTNET_RPC_URL: self::FUTURENET_RPC_URL;
        $installRequest = new InstallRequest(
            wasmBytes: $contractCode,
            rpcUrl: $rpcUrl,
            network: $this->network,
            sourceAccountKeyPair: $this->sourceAccountKeyPair,
            logger: new PrintLogger()
        );

        return SorobanClient::install($installRequest);
    }

    /**
     * @param string $wasmHash wasm id
     * @param array<XdrSCVal>|null $constructorArgs Constructor/Initialization Args for the contract's `__constructor` method.
     * @return SorobanClient
     * @throws GuzzleException
     */
    private function deployContract(string $wasmHash, ?array $constructorArgs = null): SorobanClient {
        $rpcUrl = $this->testOn == "testnet" ? self::TESTNET_RPC_URL: self::FUTURENET_RPC_URL;
        $deployRequest = new DeployRequest(
            rpcUrl: $rpcUrl,
            network: $this->network,
            sourceAccountKeyPair: $this->sourceAccountKeyPair,
            wasmHash: $wasmHash,
            constructorArgs: $constructorArgs,
            logger: new PrintLogger()
        );
        return SorobanClient::deploy($deployRequest);
    }

    /**
     * Test hello contract with ContractSpec
     * Demonstrates the simplified approach using ContractSpec for automatic type conversion
     */
    public function testHelloContractWithContractSpec() {
        $helloContractWasmHash = $this->installContract(self::HELLO_CONTRACT_PATH);
        print("Installed hello contract wasm hash: {$helloContractWasmHash}" . PHP_EOL);

        $client = $this->deployContract($helloContractWasmHash);
        print("Deployed hello contract contract id: {$client->getContractId()}" . PHP_EOL);

        $methodNames = $client->getMethodNames();
        self::assertCount(1, $methodNames);
        assertEquals("hello", $methodNames[0]);

        // Using ContractSpec for automatic type conversion (new approach)
        $contractSpec = $client->getContractSpec();
        
        // Demonstrate ContractSpec capabilities
        $functions = $contractSpec->funcs();
        print("Contract functions: " . implode(", ", array_map(fn($f) => $f->name, $functions)) . PHP_EOL);
        
        $helloFunc = $contractSpec->getFunc("hello");
        assertNotNull($helloFunc);
        assertEquals("hello", $helloFunc->name);
        print("Found hello function with " . count($helloFunc->inputs) . " inputs" . PHP_EOL);
        
        // Convert arguments using ContractSpec - this is the key improvement!
        // Instead of: [XdrSCVal::forSymbol("Maria")]
        // We can use: ["to" => "Maria"]
        $args = $contractSpec->funcArgsToXdrSCValues("hello", ["to" => "Maria"]);
        
        $result = $client->invokeMethod(name: "hello", args: $args);
        assertNotNull($result->vec);
        self::assertCount(2, $result->vec);
        assertNotNull($result->vec[0]->sym);
        assertNotNull($result->vec[1]->sym);
        $resultValue = $result->vec[0]->sym . ", " . $result->vec[1]->sym;
        assertEquals("Hello, Maria", $resultValue);
        
        print("✓ Hello contract test with ContractSpec passed!" . PHP_EOL);
    }

    /**
     * Test hello contract with contract binding
     * Uses generated PHP binding for the hello contract
     */
    public function testHelloContractWithBinding() {
        $helloContractWasmHash = $this->installContract(self::HELLO_CONTRACT_PATH);
        print("Installed hello contract wasm hash: {$helloContractWasmHash}" . PHP_EOL);
        
        $deployedClient = $this->deployContract($helloContractWasmHash);
        print("Deployed hello contract contract id: {$deployedClient->getContractId()}" . PHP_EOL);
        
        // Create HelloContract instance using the contract binding
        $rpcUrl = $this->testOn == "testnet" ? self::TESTNET_RPC_URL : self::FUTURENET_RPC_URL;
        $helloContract = HelloContract::forClientOptions(new ClientOptions(
            sourceAccountKeyPair: $this->sourceAccountKeyPair,
            contractId: $deployedClient->getContractId(),
            network: $this->network,
            rpcUrl: $rpcUrl,
            logger: new PrintLogger()
        ));
        
        // Verify contract ID matches
        assertEquals($deployedClient->getContractId(), $helloContract->getContractId());
        
        // Call hello method using the contract binding
        $result = $helloContract->hello(to: "ContractBinding");
        
        // Verify the result
        self::assertCount(2, $result);
        $resultValue = $result[0] . ", " . $result[1];
        assertEquals("Hello, ContractBinding", $resultValue);
        
        print("✓ HelloContract binding successfully invoked hello method" . PHP_EOL);
        print("✓ Result: {$resultValue}" . PHP_EOL);
    }

    /**
     * Test auth contract with ContractSpec
     * Shows the difference between manual and ContractSpec approach
     */
    public function testAuthContractWithContractSpec() {
        $authContractWasmHash = $this->installContract(self::AUTH_CONTRACT_PATH);
        print("Installed auth contract wasm hash: {$authContractWasmHash}" . PHP_EOL);

        $client = $this->deployContract($authContractWasmHash);
        print("Deployed auth contract contract id: {$client->getContractId()}" . PHP_EOL);

        $methodNames = $client->getMethodNames();
        self::assertCount(1, $methodNames);
        assertEquals("increment", $methodNames[0]);

        // Demonstrate ContractSpec usage with auth contract
        $contractSpec = $client->getContractSpec();
        
        // Show the difference between manual and ContractSpec approach
        print("=== Manual XdrSCVal Creation (Original) ===" . PHP_EOL);
        $invokerAddress = Address::fromAccountId($this->sourceAccountKeyPair->getAccountId());
        $manualArgs = [$invokerAddress->toXdrSCVal(), XdrSCVal::forU32(5)];
        $manualResult = $client->invokeMethod(name: "increment", args: $manualArgs);
        assertNotNull($manualResult->u32);
        assertEquals(5, $manualResult->u32);
        print("Manual result: {$manualResult->u32}" . PHP_EOL);

        print("=== ContractSpec Approach (New) ===" . PHP_EOL);
        // Much simpler and more readable!
        $specArgs = $contractSpec->funcArgsToXdrSCValues("increment", [
            "user" => $this->sourceAccountKeyPair->getAccountId(),  // String account ID -> automatically converts to Address
            "value" => 7                                            // int -> automatically converts to u32
        ]);
        $specResult = $client->invokeMethod(name: "increment", args: $specArgs);
        assertNotNull($specResult->u32);
        assertEquals(12, $specResult->u32); // 5 + 7
        print("ContractSpec result: {$specResult->u32}" . PHP_EOL);
        
        print("✓ Auth contract test with ContractSpec passed!" . PHP_EOL);
        print("✓ ContractSpec made the code cleaner and more intuitive!" . PHP_EOL);
    }

    /**
     * Test auth contract with contract binding
     * Uses generated PHP binding for the auth contract
     */
    public function testAuthContractWithBinding() {
        $authContractWasmHash = $this->installContract(self::AUTH_CONTRACT_PATH);
        print("Installed auth contract wasm hash: {$authContractWasmHash}" . PHP_EOL);
        
        $deployedClient = $this->deployContract($authContractWasmHash);
        print("Deployed auth contract contract id: {$deployedClient->getContractId()}" . PHP_EOL);
        
        // Create AuthContract instance using the contract binding
        $rpcUrl = $this->testOn == "testnet" ? self::TESTNET_RPC_URL : self::FUTURENET_RPC_URL;
        $authContract = AuthContract::forClientOptions(new ClientOptions(
            sourceAccountKeyPair: $this->sourceAccountKeyPair,
            contractId: $deployedClient->getContractId(),
            network: $this->network,
            rpcUrl: $rpcUrl,
            logger: new PrintLogger()
        ));
        
        // Verify contract ID matches
        assertEquals($deployedClient->getContractId(), $authContract->getContractId());
        
        // Test 1: submitter and invoker are the same (no need to sign auth)
        $user = Address::fromAccountId($this->sourceAccountKeyPair->getAccountId());
        $value = 5;
        $result = $authContract->increment(user: $user, value: $value);
        
        assertEquals(5, $result);
        print("✓ First increment: {$result}" . PHP_EOL);
        
        // Test 2: increment again
        $result2 = $authContract->increment(user: $user, value: 7);
        
        assertEquals(12, $result2); // 5 + 7
        print("✓ Second increment: {$result2}" . PHP_EOL);
        
        print("✓ AuthContract binding successfully tested!" . PHP_EOL);
    }

    /**
     * Test atomic swap with ContractSpec
     * Demonstrates how ContractSpec simplifies complex contract interactions
     */
    public function testAtomicSwapWithContractSpec() {
        $swapContractWasmHash = $this->installContract(self::SWAP_CONTRACT_PATH);
        print("Installed swap contract wasm hash: {$swapContractWasmHash}" . PHP_EOL);

        $tokenContractWasmHash = $this->installContract(self::TOKEN_CONTRACT_PATH);
        print("Installed token contract wasm hash: {$tokenContractWasmHash}" . PHP_EOL);

        $adminKeyPair = KeyPair::random();
        $aliceKeyPair = KeyPair::random();
        $aliceId = $aliceKeyPair->getAccountId();
        $bobKeyPair = KeyPair::random();
        $bobId = $bobKeyPair->getAccountId();

        if ($this->testOn === 'testnet') {
            FriendBot::fundTestAccount($adminKeyPair->getAccountId());
            FriendBot::fundTestAccount($aliceId);
            FriendBot::fundTestAccount($bobId);
        } else {
            FuturenetFriendBot::fundTestAccount($adminKeyPair->getAccountId());
            FuturenetFriendBot::fundTestAccount($aliceId);
            FuturenetFriendBot::fundTestAccount($bobId);
        }

        $atomicSwapClient = $this->deployContract($swapContractWasmHash);
        print("Deployed atomic swap contract contract id: {$atomicSwapClient->getContractId()}" . PHP_EOL);

        $adminAddress = Address::fromAccountId($adminKeyPair->getAccountId())->toXdrSCVal();
        $tokenName = XdrSCVal::forString("TokenA");
        $tokenSymbol = XdrSCVal::forString("TokenA");
        $decimal = XdrSCVal::forU32(8);

        $tokenAClient = $this->deployContract($tokenContractWasmHash, constructorArgs: [$adminAddress, $decimal, $tokenName, $tokenSymbol]);
        $tokenAContractId = $tokenAClient->getContractId();
        print("Deployed token A contract contract id: {$tokenAContractId}" . PHP_EOL);

        $tokenName = XdrSCVal::forString("TokenB");
        $tokenSymbol = XdrSCVal::forString("TokenB");
        $tokenBClient = $this->deployContract($tokenContractWasmHash, constructorArgs: [$adminAddress, $decimal, $tokenName, $tokenSymbol]);
        $tokenBContractId = $tokenBClient->getContractId();
        print("Deployed token B contract contract id: {$tokenBContractId}" . PHP_EOL);

        // Use ContractSpec for token operations

        print("=== Minting tokens with ContractSpec ===" . PHP_EOL);
        $this->mintWithSpec($tokenAClient, $adminKeyPair, $aliceId, "10000000000000");
        $this->mintWithSpec($tokenBClient, $adminKeyPair, $bobId, "10000000000000");
        print("✓ Alice and Bob funded using ContractSpec" . PHP_EOL);

        $aliceTokenABalance = $this->readBalanceWithSpec($aliceId, $tokenAClient);
        assertEquals("10000000000000", $aliceTokenABalance);

        $bobTokenBBalance = $this->readBalanceWithSpec($bobId, $tokenBClient);
        assertEquals("10000000000000", $bobTokenBBalance);
        print("✓ Balances verified using ContractSpec" . PHP_EOL);

        print("=== Demonstrating ContractSpec for complex atomic swap ===" . PHP_EOL);
        print("--- Manual XdrSCVal creation (original approach) ---" . PHP_EOL);
        $manualAmountA = XdrSCVal::forI128BigInt(1000);
        $manualMinBForA = XdrSCVal::forI128BigInt(4500);
        $manualAmountB = XdrSCVal::forI128BigInt(5000);
        $manualMinAForB = XdrSCVal::forI128BigInt(950);

        $manualArgs = [
            Address::fromAccountId($aliceId)->toXdrSCVal(),
            Address::fromAccountId($bobId)->toXdrSCVal(),
            Address::fromContractId($tokenAContractId)->toXdrSCVal(),
            Address::fromContractId($tokenBContractId)->toXdrSCVal(),
            $manualAmountA,
            $manualMinBForA,
            $manualAmountB,
            $manualMinAForB
        ];
        print("Manual args count: " . count($manualArgs) . PHP_EOL);

        print("--- ContractSpec approach (new approach) ---" . PHP_EOL);
        // This is MUCH cleaner and more readable!
        $contractSpec = $atomicSwapClient->getContractSpec();
        $specArgs = $contractSpec->funcArgsToXdrSCValues("swap", [
            "a" => $aliceId,                    // String -> Address (automatic)
            "b" => $bobId,                      // String -> Address (automatic)
            "token_a" => $tokenAContractId,     // String -> Address (automatic)
            "token_b" => $tokenBContractId,     // String -> Address (automatic)
            "amount_a" => 1000,                 // int -> i128 (automatic)
            "min_b_for_a" => 4500,             // int -> i128 (automatic)
            "amount_b" => 5000,                 // int -> i128 (automatic)
            "min_a_for_b" => 950                // int -> i128 (automatic)
        ]);
        print("ContractSpec args count: " . count($specArgs) . PHP_EOL);
        print("✓ ContractSpec automatically converted 8 parameters with correct types" . PHP_EOL);

        // Build and execute the transaction using ContractSpec args
        $tx = $atomicSwapClient->buildInvokeMethodTx(name: "swap", args: $specArgs);

        $whoElseNeedsToSign = $tx->needsNonInvokerSigningBy();
        self::assertCount(2, $whoElseNeedsToSign);
        self::assertContains($aliceId, $whoElseNeedsToSign);
        self::assertContains($bobId, $whoElseNeedsToSign);

        $tx->signAuthEntries(signerKeyPair: $aliceKeyPair);
        print("✓ Signed by Alice" . PHP_EOL);

        $tx->signAuthEntries(signerKeyPair: $bobKeyPair);
        print("✓ Signed by Bob" . PHP_EOL);

        $response = $tx->signAndSend();
        $result = $response->getResultValue();
        assertNotNull($result);
        assertEquals(XdrSCValType::SCV_VOID, $result->type->value);
        
        print("✓ Atomic swap completed successfully using ContractSpec!" . PHP_EOL);
        print("✓ ContractSpec made complex contract invocation much simpler and more readable" . PHP_EOL);
    }

    /**
     * Test atomic swap with contract bindings
     * Uses generated PHP bindings for token and atomic swap contracts
     * Demonstrates the use of build methods for transaction manipulation
     */
    public function testAtomicSwapContractWithBinding() {
        $swapContractWasmHash = $this->installContract(self::SWAP_CONTRACT_PATH);
        print("Installed swap contract wasm hash: {$swapContractWasmHash}" . PHP_EOL);
        
        $tokenContractWasmHash = $this->installContract(self::TOKEN_CONTRACT_PATH);
        print("Installed token contract wasm hash: {$tokenContractWasmHash}" . PHP_EOL);
        
        $adminKeyPair = KeyPair::random();
        $aliceKeyPair = KeyPair::random();
        $aliceId = $aliceKeyPair->getAccountId();
        $bobKeyPair = KeyPair::random();
        $bobId = $bobKeyPair->getAccountId();
        
        if ($this->testOn === 'testnet') {
            FriendBot::fundTestAccount($adminKeyPair->getAccountId());
            FriendBot::fundTestAccount($aliceId);
            FriendBot::fundTestAccount($bobId);
        } else {
            FuturenetFriendBot::fundTestAccount($adminKeyPair->getAccountId());
            FuturenetFriendBot::fundTestAccount($aliceId);
            FuturenetFriendBot::fundTestAccount($bobId);
        }
        
        // Deploy contracts
        $atomicSwapDeployed = $this->deployContract($swapContractWasmHash);
        print("Deployed atomic swap contract contract id: {$atomicSwapDeployed->getContractId()}" . PHP_EOL);

        $adminAddress = Address::fromAccountId($adminKeyPair->getAccountId())->toXdrSCVal();
        $tokenName = XdrSCVal::forString("TokenA");
        $tokenSymbol = XdrSCVal::forString("TokenA");
        $decimal = XdrSCVal::forU32(8);

        $tokenADeployed = $this->deployContract($tokenContractWasmHash, constructorArgs: [$adminAddress, $decimal, $tokenName, $tokenSymbol]);
        $tokenAContractId = $tokenADeployed->getContractId();
        print("Deployed token A contract contract id: {$tokenAContractId}" . PHP_EOL);

        $tokenName = XdrSCVal::forString("TokenB");
        $tokenSymbol = XdrSCVal::forString("TokenB");
        $tokenBDeployed = $this->deployContract($tokenContractWasmHash, constructorArgs: [$adminAddress, $decimal, $tokenName, $tokenSymbol]);
        $tokenBContractId = $tokenBDeployed->getContractId();
        print("Deployed token B contract contract id: {$tokenBContractId}" . PHP_EOL);
        
        // Create contract binding instances
        $rpcUrl = $this->testOn == "testnet" ? self::TESTNET_RPC_URL : self::FUTURENET_RPC_URL;
        
        $atomicSwapContract = AtomicSwapContract::forClientOptions(new ClientOptions(
            sourceAccountKeyPair: $this->sourceAccountKeyPair,
            contractId: $atomicSwapDeployed->getContractId(),
            network: $this->network,
            rpcUrl: $rpcUrl,
            logger: new PrintLogger()
        ));
        
        $tokenAContract = TokenContract::forClientOptions(new ClientOptions(
            sourceAccountKeyPair: $adminKeyPair,
            contractId: $tokenAContractId,
            network: $this->network,
            rpcUrl: $rpcUrl,
            logger: new PrintLogger()
        ));
        
        $tokenBContract = TokenContract::forClientOptions(new ClientOptions(
            sourceAccountKeyPair: $adminKeyPair,
            contractId: $tokenBContractId,
            network: $this->network,
            rpcUrl: $rpcUrl,
            logger: new PrintLogger()
        ));
        
        // Mint tokens using bindings with build methods
        print("=== Minting tokens using build methods ===" . PHP_EOL);
        $aliceAddress = Address::fromAccountId($aliceId);
        $bobAddress = Address::fromAccountId($bobId);
        
        // Build mint transaction for Alice
        $mintAliceTx = $tokenAContract->buildMintTx(
            to: $aliceAddress,
            amount: "10000000000000"
        );
        // The build method allows inspection and manipulation before sending
        print("✓ Built mint transaction for Alice using buildMintTx()" . PHP_EOL);
        $mintAliceTx->signAndSend();
        print("✓ Minted Token A to Alice" . PHP_EOL);
        
        // Build mint transaction for Bob
        $mintBobTx = $tokenBContract->buildMintTx(
            to: $bobAddress,
            amount: "10000000000000"
        );
        $mintBobTx->signAndSend();
        print("✓ Minted Token B to Bob" . PHP_EOL);
        
        sleep(5); // Wait for state to settle
        
        // Check balances using bindings
        print("=== Checking balances ===" . PHP_EOL);
        
        // Check with direct method
        $aliceTokenABalance = $tokenAContract->balance(id: $aliceAddress);
        assertEquals("10000000000000", $aliceTokenABalance);
        print("✓ Alice Token A balance: 10000000000000" . PHP_EOL);
        
        $bobTokenBBalance = $tokenBContract->balance(id: $bobAddress);
        assertEquals("10000000000000", $bobTokenBBalance);
        print("✓ Bob Token B balance: 10000000000000" . PHP_EOL);
        
        // Perform atomic swap using NEW build method
        print("=== Performing atomic swap with buildSwapTx method ===" . PHP_EOL);
        
        // Prepare addresses for the swap
        $tokenAAddress = Address::fromContractId($tokenAContractId);
        $tokenBAddress = Address::fromContractId($tokenBContractId);
        
        // Use the NEW buildSwapTx method from the generated binding!
        $swapTx = $atomicSwapContract->buildSwapTx(
            a: $aliceAddress,
            b: $bobAddress,
            token_a: $tokenAAddress,
            token_b: $tokenBAddress,
            amount_a: "1000",
            min_b_for_a: "4500",
            amount_b: "5000",
            min_a_for_b: "950"
        );
        
        print("✓ Built swap transaction using buildSwapTx()" . PHP_EOL);
        
        // Check who needs to sign
        $whoElseNeedsToSign = $swapTx->needsNonInvokerSigningBy();
        self::assertCount(2, $whoElseNeedsToSign);
        self::assertContains($aliceId, $whoElseNeedsToSign);
        self::assertContains($bobId, $whoElseNeedsToSign);
        print("✓ Transaction requires signatures from Alice and Bob" . PHP_EOL);
        
        // Sign auth entries
        $swapTx->signAuthEntries(signerKeyPair: $aliceKeyPair);
        print("✓ Signed by Alice" . PHP_EOL);
        
        $swapTx->signAuthEntries(signerKeyPair: $bobKeyPair);
        print("✓ Signed by Bob" . PHP_EOL);
        
        // Send the transaction
        $response = $swapTx->signAndSend();
        $result = $response->getResultValue();
        assertNotNull($result);
        assertEquals(XdrSCValType::SCV_VOID, $result->type->value);
        
        print("✓ Atomic swap completed successfully using buildSwapTx()!" . PHP_EOL);
        
        sleep(5); // Wait for state to settle
        
        // Verify final balances after swap
        print("=== Verifying final balances after swap ===" . PHP_EOL);
        
        // Alice should have received Token B
        $aliceTokenBBalance = $tokenBContract->balance(id: $aliceAddress);
        $aliceTokenBExpected = gmp_strval(gmp_sub(gmp_init("4500"), gmp_init("0"))); // 5000 Token B
        assertEquals($aliceTokenBExpected, $aliceTokenBBalance);
        print("✓ Alice Token B balance after swap: {$aliceTokenBBalance}" . PHP_EOL);
        
        // Bob should have received Token A
        $bobTokenABalance = $tokenAContract->balance(id: $bobAddress);
        $bobTokenAExpected = gmp_strval(gmp_sub(gmp_init("950"), gmp_init("0"))); // 1000 Token A
        assertEquals($bobTokenAExpected, $bobTokenABalance);
        print("✓ Bob Token A balance after swap: {$bobTokenABalance}" . PHP_EOL);
        
        // Alice's Token A should be reduced
        $aliceTokenAFinal = $tokenAContract->balance(id: $aliceAddress);
        $aliceTokenAExpected = gmp_strval(gmp_sub(gmp_init("10000000000000"), gmp_init("950")));
        assertEquals($aliceTokenAExpected, $aliceTokenAFinal);
        print("✓ Alice Token A balance reduced to: {$aliceTokenAFinal}" . PHP_EOL);
        
        // Bob's Token B should be reduced
        $bobTokenBFinal = $tokenBContract->balance(id: $bobAddress);
        $bobTokenBExpected = gmp_strval(gmp_sub(gmp_init("10000000000000"), gmp_init("4500")));
        assertEquals($bobTokenBExpected, $bobTokenBFinal);
        print("✓ Bob Token B balance reduced to: {$bobTokenBFinal}" . PHP_EOL);
        
        print("=== Summary ===" . PHP_EOL);
        print("✓ Successfully demonstrated buildSwapTx() method" . PHP_EOL);
        print("✓ Transaction inspection before sending" . PHP_EOL);
        print("✓ Multi-party authorization handling" . PHP_EOL);
        print("✓ Contract bindings provide type-safe, clean interface" . PHP_EOL);
        print("✓ Build methods enable advanced transaction workflows" . PHP_EOL);
    }

    /**
     * ContractSpec version of mint function for comparison
     */
    private function mintWithSpec(SorobanClient $tokenClient, KeyPair $adminKp, string $toAccountId, string $amount): void {
        // Using ContractSpec - automatic type conversion!
        $args = $tokenClient->getContractSpec()->funcArgsToXdrSCValues("mint", [
            "to" => $toAccountId,  // String -> Address automatic conversion
            "amount" => $amount    // string -> i128 automatic conversion using BigInt
        ]);

        $tx = $tokenClient->buildInvokeMethodTx(name: "mint", args: $args);
        $tx->signAuthEntries(signerKeyPair: $adminKp);
        $tx->signAndSend();
    }

    /**
     * ContractSpec version of readBalance function for comparison
     */
    private function readBalanceWithSpec(string $forAccountId, SorobanClient $tokenClient): string {
        // Using ContractSpec - cleaner argument passing!
        $args = $tokenClient->getContractSpec()->funcArgsToXdrSCValues("balance", [
            "id" => $forAccountId  // String -> Address automatic conversion
        ]);
        
        $resultValue = $tokenClient->invokeMethod(name: "balance", args: $args);
        assertNotNull($resultValue->i128);
        // Return as string to handle large numbers
        return gmp_strval($resultValue->toBigInt());
    }

    /**
     * @throws GuzzleException
     */
    private function mint(SorobanClient $tokenClient, Keypair $adminKp, String $toAccountId, int $amount) : void
    {
        sleep(5);

        $methodName = "mint";

        $spec = new ContractSpec($tokenClient->getSpecEntries());
        $args = $spec->funcArgsToXdrSCValues(name: $methodName, args: [
            "to" => $toAccountId,
            "amount" => $amount
        ]);

        $tx = $tokenClient->buildInvokeMethodTx(name: $methodName, args: $args);
        $tx->signAuthEntries(signerKeyPair: $adminKp);
        $tx->signAndSend();

    }

    private function readBalance(String $forAccountId, SorobanClient $tokenClient) : int
    {
        sleep(5);

        // see https://soroban.stellar.org/docs/reference/interfaces/token-interface

        $methodName = "balance";

        $spec = new ContractSpec($tokenClient->getSpecEntries());
        $args = $spec->funcArgsToXdrSCValues(name: $methodName, args: [
            "id" => $forAccountId
        ]);

        $resultVal = $tokenClient->invokeMethod(name: $methodName, args: $args);
        return $resultVal->i128->lo;
    }
}