<?php  declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Integration;

use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\Contract\ClientOptions;
use Soneso\StellarSDK\Soroban\Contract\DeployRequest;
use Soneso\StellarSDK\Soroban\Contract\InstallRequest;
use Soneso\StellarSDK\Soroban\Contract\SorobanClient;
use Soneso\StellarSDK\Util\FriendBot;
use Soneso\StellarSDKTests\PrintLogger;
use Soneso\StellarSDKTests\bindings\BindingsSpecTestContract;
use Soneso\StellarSDKTests\bindings\BindingsSpecTestContractComplexEnum;
use Soneso\StellarSDKTests\bindings\BindingsSpecTestContractRoyalCard;
use Soneso\StellarSDKTests\bindings\BindingsSpecTestContractSimpleEnum;
use Soneso\StellarSDKTests\bindings\BindingsSpecTestContractSimpleStruct;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertSame;

/**
 * Integration test for the full-surface generated binding (BindingsSpecTestContract).
 *
 * Installs and deploys the reference spec test contract on testnet and exercises
 * the generated PHP binding across the type surface that the bindings generator
 * must encode and decode: wide integers, timepoint/duration, bytes, map, tuple,
 * option, struct, union and address round trips.
 */
class BindingsSpecTestContractTest extends TestCase
{
    const SPEC_CONTRACT_PATH = './../wasm/soroban_bindings_spec_test_contract.wasm';
    const TESTNET_RPC_URL = "https://soroban-testnet.stellar.org";

    private Network $network;
    private KeyPair $sourceAccountKeyPair;

    public function setUp(): void
    {
        error_reporting(E_ALL);
        $this->network = Network::testnet();
        $this->sourceAccountKeyPair = KeyPair::random();
        print("Signer seed: " . $this->sourceAccountKeyPair->getSecretSeed() . PHP_EOL);
        FriendBot::fundTestAccount($this->sourceAccountKeyPair->getAccountId());
    }

    /**
     * @throws GuzzleException
     */
    public function testSpecContractBinding(): void
    {
        $wasmHash = $this->installContract(self::SPEC_CONTRACT_PATH);
        print("Installed spec test contract wasm hash: {$wasmHash}" . PHP_EOL);

        $deployedClient = $this->deployContract($wasmHash);
        $contractId = $deployedClient->getContractId();
        print("Deployed spec test contract id: {$contractId}" . PHP_EOL);

        $contract = BindingsSpecTestContract::forClientOptions(new ClientOptions(
            sourceAccountKeyPair: $this->sourceAccountKeyPair,
            contractId: $contractId,
            network: $this->network,
            rpcUrl: self::TESTNET_RPC_URL,
            logger: new PrintLogger()
        ));
        assertEquals($contractId, $contract->getContractId());

        // u64 above 2^53
        $u64 = 4611686018427387904;
        assertSame($u64, $contract->u64($u64));
        print("u64 round trip: {$u64}" . PHP_EOL);

        // i64 negative
        $i64 = -4611686018427387904;
        assertSame($i64, $contract->i64($i64));
        print("i64 round trip: {$i64}" . PHP_EOL);

        // i32 at its most negative value (-2^31)
        $i32 = -2147483648;
        assertSame($i32, $contract->i32($i32));
        print("i32 negative round trip: {$i32}" . PHP_EOL);

        // timepoint
        $timepoint = 1700000000;
        assertSame($timepoint, $contract->timepoint($timepoint));
        print("timepoint round trip: {$timepoint}" . PHP_EOL);

        // duration
        $duration = 3600;
        assertSame($duration, $contract->duration($duration));
        print("duration round trip: {$duration}" . PHP_EOL);

        // bytes
        $bytes = hex2bin("deadbeef");
        assertSame($bytes, $contract->bytes($bytes));
        print("bytes round trip: deadbeef" . PHP_EOL);

        // map<u32, bool>: map entries are encoded as XdrSCMapEntry objects sorted
        // ascending by key (Soroban rejects an unsorted ScMap). Keys are supplied out
        // of order to confirm the encoder sorts them.
        $map = [7 => true, 1 => true, 2 => false];
        $mapResult = $contract->map($map);
        assertEquals([1 => true, 2 => false, 7 => true], $mapResult);
        print("map<u32,bool> unsorted-input round trip: " . json_encode($mapResult) . PHP_EOL);

        // tuple (Symbol, u32): tuples encode as an SCV_VEC
        $tuple = ["hello", 42];
        assertEquals($tuple, $contract->tuple($tuple));
        print("tuple (Symbol,u32) round trip: " . json_encode($tuple) . PHP_EOL);

        // vec<u32>: element order is preserved
        $vec = [3, 1, 2];
        assertSame($vec, $contract->vec($vec));
        print("vec<u32> round trip: " . json_encode($vec) . PHP_EOL);

        // card: RoyalCard is an integer-discriminant enum encoded as SCV_U32
        $card = BindingsSpecTestContractRoyalCard::Queen;
        assertSame($card, $contract->card($card));
        print("card (RoyalCard) round trip: {$card->name}={$card->value}" . PHP_EOL);

        // option some + none
        assertSame(5, $contract->option(5));
        assertNull($contract->option(null));
        print("option some/none round trip" . PHP_EOL);

        // struct round trip
        $struct = new BindingsSpecTestContractSimpleStruct(a: 11, b: true, c: "world");
        $structResult = $contract->strukt($struct);
        assertSame(11, $structResult->a);
        assertSame(true, $structResult->b);
        assertSame("world", $structResult->c);
        print("struct round trip: a={$structResult->a} b=true c={$structResult->c}" . PHP_EOL);

        // union round trip (unit variant union)
        $simple = new BindingsSpecTestContractSimpleEnum(BindingsSpecTestContractSimpleEnum::SECOND);
        $simpleResult = $contract->simple($simple);
        assertSame(BindingsSpecTestContractSimpleEnum::SECOND, $simpleResult->kind);
        print("union (SimpleEnum) round trip: {$simpleResult->kind}" . PHP_EOL);

        // union round trip (void case)
        $complexVoid = new BindingsSpecTestContractComplexEnum(BindingsSpecTestContractComplexEnum::VOID);
        $complexVoidResult = $contract->complex($complexVoid);
        assertSame(BindingsSpecTestContractComplexEnum::VOID, $complexVoidResult->kind);
        print("union (ComplexEnum::Void) round trip" . PHP_EOL);

        // union round trip (tuple case with nested struct)
        $complexStruct = new BindingsSpecTestContractComplexEnum(
            BindingsSpecTestContractComplexEnum::STRUCT,
            struct: new BindingsSpecTestContractSimpleStruct(a: 3, b: false, c: "nested")
        );
        $complexStructResult = $contract->complex($complexStruct);
        assertSame(BindingsSpecTestContractComplexEnum::STRUCT, $complexStructResult->kind);
        assertSame(3, $complexStructResult->struct->a);
        assertSame(false, $complexStructResult->struct->b);
        assertSame("nested", $complexStructResult->struct->c);
        print("union (ComplexEnum::Struct) round trip" . PHP_EOL);

        // union round trip (tuple case with Address + i128)
        $assetAddress = Address::fromAccountId($this->sourceAccountKeyPair->getAccountId());
        $complexAsset = new BindingsSpecTestContractComplexEnum(
            BindingsSpecTestContractComplexEnum::ASSET,
            asset: [$assetAddress, "1000000"]
        );
        $complexAssetResult = $contract->complex($complexAsset);
        assertSame(BindingsSpecTestContractComplexEnum::ASSET, $complexAssetResult->kind);
        assertSame(
            $this->sourceAccountKeyPair->getAccountId(),
            $complexAssetResult->asset[0]->getAccountId()
        );
        assertSame("1000000", $complexAssetResult->asset[1]);
        print("union (ComplexEnum::Asset) round trip" . PHP_EOL);

        // i128 (big int as string)
        $i128 = "170141183460469231731687303715884105727";
        assertSame($i128, $contract->i128($i128));
        print("i128 round trip: {$i128}" . PHP_EOL);

        // u128 at its maximum value (2^128 - 1), passed and returned as a decimal string
        $u128Max = "340282366920938463463374607431768211455";
        assertSame($u128Max, $contract->u128($u128Max));
        print("u128 max round trip: {$u128Max}" . PHP_EOL);

        // u256 at its maximum value (2^256 - 1), passed and returned as a decimal string
        $u256Max = "115792089237316195423570985008687907853269984665640564039457584007913129639935";
        assertSame($u256Max, $contract->u256($u256Max));
        print("u256 max round trip: {$u256Max}" . PHP_EOL);

        // i256 at both signed extremes (-2^255 and 2^255 - 1), as decimal strings
        $i256Min = "-57896044618658097711785492504343953926634992332820282019728792003956564819968";
        assertSame($i256Min, $contract->i256($i256Min));
        print("i256 min round trip: {$i256Min}" . PHP_EOL);

        $i256Max = "57896044618658097711785492504343953926634992332820282019728792003956564819967";
        assertSame($i256Max, $contract->i256($i256Max));
        print("i256 max round trip: {$i256Max}" . PHP_EOL);

        // address round trip: decodes an SCV_ADDRESS value returned by the contract
        $address = Address::fromAccountId($this->sourceAccountKeyPair->getAccountId());
        $addressResult = $contract->address($address);
        assertSame($this->sourceAccountKeyPair->getAccountId(), $addressResult->getAccountId());
        print("address round trip: {$addressResult->getAccountId()}" . PHP_EOL);

        // reserved-word method and parameter names: the contract's `from` method
        // takes a Symbol argument named `finally`; the binding escapes both the
        // method and the argument. Confirms a Symbol round trips through them.
        $symbol = "from_kw";
        assertSame($symbol, $contract->from($symbol));
        print("keyword method from(finally) round trip: {$symbol}" . PHP_EOL);

        print("All BindingsSpecTestContract binding round trips passed." . PHP_EOL);
    }

    /**
     * @throws GuzzleException
     */
    private function installContract(string $path): string
    {
        $contractCode = file_get_contents($path, false);
        $installRequest = new InstallRequest(
            wasmBytes: $contractCode,
            rpcUrl: self::TESTNET_RPC_URL,
            network: $this->network,
            sourceAccountKeyPair: $this->sourceAccountKeyPair,
            logger: new PrintLogger()
        );
        return SorobanClient::install($installRequest);
    }

    /**
     * @throws GuzzleException
     */
    private function deployContract(string $wasmHash, ?array $constructorArgs = null): SorobanClient
    {
        $deployRequest = new DeployRequest(
            rpcUrl: self::TESTNET_RPC_URL,
            network: $this->network,
            sourceAccountKeyPair: $this->sourceAccountKeyPair,
            wasmHash: $wasmHash,
            constructorArgs: $constructorArgs,
            logger: new PrintLogger()
        );
        return SorobanClient::deploy($deployRequest);
    }
}
