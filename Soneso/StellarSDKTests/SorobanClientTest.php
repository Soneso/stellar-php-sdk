<?php  declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

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
use Soneso\StellarSDK\Xdr\XdrInt128Parts;
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
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotNull;

class SorobanClientTest  extends TestCase
{
    const TESTNET_SERVER_URL = "https://soroban-testnet.stellar.org";
    const HELLO_CONTRACT_PATH = './wasm/soroban_hello_world_contract.wasm';
    const AUTH_CONTRACT_PATH = './wasm/soroban_auth_contract.wasm';
    const SWAP_CONTRACT_PATH = './wasm/soroban_atomic_swap_contract.wasm';
    const TOKEN_CONTRACT_PATH = './wasm/soroban_token_contract.wasm';

    private Network $network;
    private KeyPair $sourceAccountKeyPair;

    public function setUp(): void
    {
        // Turn on error reporting
        error_reporting(E_ALL);
        $this->network = Network::testnet();
        $this->sourceAccountKeyPair = KeyPair::random();
        print("Signer seed: " . $this->sourceAccountKeyPair->getSecretSeed() . PHP_EOL);
        FriendBot::fundTestAccount($this->sourceAccountKeyPair->getAccountId());
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
        $client = SorobanClient::forClientOptions(new ClientOptions(
            sourceAccountKeyPair: $this->sourceAccountKeyPair,
            contractId: $deployedClient->getContractId(),
            network: $this->network,
            rpcUrl: self::TESTNET_SERVER_URL,
            enableServerLogging: true)
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
        FriendBot::fundTestAccount($invokerKeyPair->getAccountId());

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

        FriendBot::fundTestAccount($adminKeyPair->getAccountId());
        FriendBot::fundTestAccount($aliceId);
        FriendBot::fundTestAccount($bobId);

        print("admin: " . $adminKeyPair->getSecretSeed() .  " : " . $adminKeyPair->getAccountId(). PHP_EOL);
        print("alice: " . $aliceKeyPair->getSecretSeed() .  " : " . $aliceKeyPair->getAccountId(). PHP_EOL);
        print("bob: " . $bobKeyPair->getSecretSeed() .  " : " . $bobKeyPair->getAccountId(). PHP_EOL);

        $atomicSwapClient = $this->deployContract($swapContractWasmHash);
        print("Deployed atomic swap contract contract id: {$atomicSwapClient->getContractId()}" . PHP_EOL);

        $tokenAClient = $this->deployContract($tokenContractWasmHash);
        $tokenAContractId = $tokenAClient->getContractId();
        print("Deployed token A contract contract id: {$tokenAContractId}" . PHP_EOL);

        $tokenBClient = $this->deployContract($tokenContractWasmHash);
        $tokenBContractId = $tokenBClient->getContractId();
        print("Deployed token B contract contract id: {$tokenBContractId}" . PHP_EOL);

        $this->createToken($tokenAClient, $adminKeyPair,"TokenA", "TokenA");
        $this->createToken($tokenBClient, $adminKeyPair, "TokenB", "TokenB");
        print("Tokens created");

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
        $func = $spec->getFunc("initialize");
        $this->assertEquals("initialize", $func->name);
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
    }

    /**
     * @throws GuzzleException
     */
    private function installContract(string $path): string {
        $contractCode = file_get_contents($path, false);

        $installRequest = new InstallRequest(
            wasmBytes: $contractCode,
            rpcUrl: self::TESTNET_SERVER_URL,
            network: $this->network,
            sourceAccountKeyPair: $this->sourceAccountKeyPair,
            enableServerLogging: true
        );

        return SorobanClient::install($installRequest);
    }

    /**
     * @throws GuzzleException
     */
    private function deployContract(string $wasmHash): SorobanClient {
        $deployRequest = new DeployRequest(
            rpcUrl: self::TESTNET_SERVER_URL,
            network: $this->network,
            sourceAccountKeyPair: $this->sourceAccountKeyPair,
            wasmHash: $wasmHash,
            enableServerLogging: true
        );
        return SorobanClient::deploy($deployRequest);
    }

    /**
     * @throws GuzzleException
     */
    private function createToken(SorobanClient $tokenClient, Keypair $submitterKp, String $name, String $symbol) : void
    {
        // see https://soroban.stellar.org/docs/reference/interfaces/token-interface
        $submitterId = $submitterKp->getAccountId();

        $methodName = "initialize";

        $spec = new ContractSpec($tokenClient->getSpecEntries());
        $args = $spec->funcArgsToXdrSCValues(name: $methodName, args: [
            "admin" => Address::fromAccountId($submitterId),
            "decimal" => 8,
            "name" => $name,
            "symbol" => $symbol
        ]);

        $tokenClient->invokeMethod(name: $methodName, args: $args);
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