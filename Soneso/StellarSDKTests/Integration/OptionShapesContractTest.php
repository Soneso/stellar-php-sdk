<?php  declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Integration;

use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Contract\ClientOptions;
use Soneso\StellarSDK\Soroban\Contract\DeployRequest;
use Soneso\StellarSDK\Soroban\Contract\InstallRequest;
use Soneso\StellarSDK\Soroban\Contract\SorobanClient;
use Soneso\StellarSDK\Util\FriendBot;
use Soneso\StellarSDKTests\PrintLogger;
use Soneso\StellarSDKTests\bindings\OptionShapesContract;
use Soneso\StellarSDKTests\bindings\OptionShapesContractMaybeStruct;
use Soneso\StellarSDKTests\bindings\OptionShapesContractOptUnion;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertSame;

/**
 * Integration test for the generated OptionShapesContract binding.
 *
 * Installs and deploys the option shapes contract on testnet and exercises the
 * generated PHP binding across every position an Option can occupy: nested in a
 * tuple, a struct field, map values and a union payload, plus a keyword-named
 * method.
 */
class OptionShapesContractTest extends TestCase
{
    const OPTION_SHAPES_CONTRACT_PATH = './../wasm/soroban_bindings_option_shapes_contract.wasm';
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
    public function testOptionShapesContractBinding(): void
    {
        $wasmHash = $this->installContract(self::OPTION_SHAPES_CONTRACT_PATH);
        print("Installed option shapes contract wasm hash: {$wasmHash}" . PHP_EOL);

        $deployedClient = $this->deployContract($wasmHash);
        $contractId = $deployedClient->getContractId();
        print("Deployed option shapes contract id: {$contractId}" . PHP_EOL);

        $contract = OptionShapesContract::forClientOptions(new ClientOptions(
            sourceAccountKeyPair: $this->sourceAccountKeyPair,
            contractId: $contractId,
            network: $this->network,
            rpcUrl: self::TESTNET_RPC_URL,
            logger: new PrintLogger()
        ));
        assertEquals($contractId, $contract->getContractId());

        // `default` is a PHP keyword but a valid method name, so the binding keeps
        // the contract's function name unchanged
        $defaultValue = 77;
        assertSame($defaultValue, $contract->default($defaultValue));
        print("keyword method default round trip: {$defaultValue}" . PHP_EOL);

        // tuple (Option<u32>, u32): a null option slot encodes as SCV_VOID; a set
        // slot keeps its integer type
        assertSame([9, 4], $contract->optTuple([9, 4]));
        assertSame([null, 4], $contract->optTuple([null, 4]));
        print("tuple (Option<u32>,u32) some/none round trip" . PHP_EOL);

        // struct with an Option<u32> field: only SCV_VOID decodes to null, a set
        // value keeps its integer type
        $struktSome = $contract->optStrukt(new OptionShapesContractMaybeStruct(flag: 1, maybe: 8));
        assertSame(1, $struktSome->flag);
        assertSame(8, $struktSome->maybe);
        $struktNone = $contract->optStrukt(new OptionShapesContractMaybeStruct(flag: 2, maybe: null));
        assertSame(2, $struktNone->flag);
        assertNull($struktNone->maybe);
        print("struct (MaybeStruct) option-field some/none round trip" . PHP_EOL);

        // map<u32, Option<u32>>: null values encode as SCV_VOID and decode back to
        // null; entries come back sorted ascending by key
        $optMapResult = $contract->optMap([5 => null, 1 => 10, 3 => null, 2 => 20]);
        assertSame([1 => 10, 2 => 20, 3 => null, 5 => null], $optMapResult);
        print("map<u32,Option<u32>> mixed some/none round trip: " . json_encode($optMapResult) . PHP_EOL);

        // union with an Option payload: Maybe(null) encodes as [Maybe, SCV_VOID]
        // and stays distinct from the payload-less Nothing arm
        $nothingResult = $contract->optUnion(
            new OptionShapesContractOptUnion(OptionShapesContractOptUnion::NOTHING)
        );
        assertSame(OptionShapesContractOptUnion::NOTHING, $nothingResult->kind);
        assertNull($nothingResult->maybe);
        $maybeSomeResult = $contract->optUnion(
            new OptionShapesContractOptUnion(OptionShapesContractOptUnion::MAYBE, maybe: 21)
        );
        assertSame(OptionShapesContractOptUnion::MAYBE, $maybeSomeResult->kind);
        assertSame(21, $maybeSomeResult->maybe);
        $maybeNoneResult = $contract->optUnion(
            new OptionShapesContractOptUnion(OptionShapesContractOptUnion::MAYBE, maybe: null)
        );
        assertSame(OptionShapesContractOptUnion::MAYBE, $maybeNoneResult->kind);
        assertNull($maybeNoneResult->maybe);
        print("union (OptUnion) Nothing/Maybe(21)/Maybe(null) round trips" . PHP_EOL);

        print("All OptionShapesContract binding round trips passed." . PHP_EOL);
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
    private function deployContract(string $wasmHash): SorobanClient
    {
        $deployRequest = new DeployRequest(
            rpcUrl: self::TESTNET_RPC_URL,
            network: $this->network,
            sourceAccountKeyPair: $this->sourceAccountKeyPair,
            wasmHash: $wasmHash,
            constructorArgs: null,
            logger: new PrintLogger()
        );
        return SorobanClient::deploy($deployRequest);
    }
}
