<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Integration;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\Contract\DeployFromExternalRefRequest;
use Soneso\StellarSDK\Soroban\Contract\DeployRequest;
use Soneso\StellarSDK\Soroban\Contract\InstallRequest;
use Soneso\StellarSDK\Soroban\Contract\SorobanClient;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Xdr\XdrContractDataDurability;
use Soneso\StellarSDK\Xdr\XdrContractExecutableExternalRef;
use Soneso\StellarSDK\Xdr\XdrContractExecutableType;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDKTests\PrintLogger;
use Soneso\StellarSDKTests\TestUtils;
use Throwable;

/**
 * Protocol 28 (CAP-85) external reference executable end-to-end integration test.
 *
 * Self-contained lifecycle: installs the owner fixture contract, deploys it, installs
 * the hello world contract's wasm, writes an executable tag entry naming it, resolves
 * the reference, and deploys a new instance from it with an explicit salt. It verifies
 * the created contract id against Address::deriveContractId, and checks the instance's
 * executable arm, the loading paths behind the reference, and a live invocation.
 *
 * The owner fixture wasm exposes set_executable(tag, wasm_hash) and
 * get_executable(tag) over the CAP-85 host functions; its source lives in this
 * repo under tools/p28-owner-fixture.
 */
class P28ExternalRefExecutableTest extends TestCase
{
    const OWNER_FIXTURE_PATH = __DIR__ . '/../wasm/p28_owner_fixture.wasm';
    const TARGET_CONTRACT_PATH = __DIR__ . '/../wasm/soroban_hello_world_contract.wasm';
    const EXECUTABLE_TAG = 'sdk-e2e-v1';

    const TESTNET_SERVER_URL = "https://soroban-testnet.stellar.org";
    const FUTURENET_SERVER_URL = "https://rpc-futurenet.stellar.org";

    private string $testOn = 'testnet'; // 'futurenet'
    private Network $network;
    private KeyPair $keyPair;
    private string $accountId;
    private SorobanServer $server;
    private StellarSDK $sdk;
    private string $rpcUrl;

    public function setUp(): void
    {
        error_reporting(E_ALL);
        $this->keyPair = KeyPair::random();
        $this->accountId = $this->keyPair->getAccountId();
        if ($this->testOn === 'testnet') {
            $this->network = Network::testnet();
            $this->rpcUrl = self::TESTNET_SERVER_URL;
            $this->sdk = StellarSDK::getTestNetInstance();
        } elseif ($this->testOn === 'futurenet') {
            $this->network = Network::futurenet();
            $this->rpcUrl = self::FUTURENET_SERVER_URL;
            $this->sdk = StellarSDK::getFutureNetInstance();
        } else {
            $this->markTestSkipped("Unsupported testOn value '{$this->testOn}'.");
        }
        $this->server = new SorobanServer($this->rpcUrl);
        $this->server->setLogger(new PrintLogger());

        try {
            $protocolVersion = $this->server->getNetwork()->getProtocolVersion();
        } catch (Throwable $t) {
            $protocolVersion = null;
        }
        if ($protocolVersion === null || $protocolVersion < 28) {
            $this->markTestSkipped('The network reports protocol '
                . ($protocolVersion === null ? 'unavailable' : (string)$protocolVersion)
                . '; external reference executables need protocol 28 '
                . '(testnet upgrade scheduled for 2026-08-27).');
        }

        TestUtils::fundTestAccountAndAwaitVisibility(
            $this->accountId,
            rpc: $this->server,
            horizon: $this->sdk,
            useFuturenet: $this->testOn !== 'testnet',
        );
    }

    public function testExternalRefLifecycle(): void
    {
        // Install and deploy the owner fixture holding the executable tag entries.
        $ownerWasmId = SorobanClient::install(new InstallRequest(
            wasmBytes: file_get_contents(self::OWNER_FIXTURE_PATH, false),
            rpcUrl: $this->rpcUrl,
            network: $this->network,
            sourceAccountKeyPair: $this->keyPair,
        ));
        $owner = SorobanClient::deploy(new DeployRequest(
            rpcUrl: $this->rpcUrl,
            network: $this->network,
            sourceAccountKeyPair: $this->keyPair,
            wasmHash: $ownerWasmId,
        ));
        $this->assertContains('set_executable', $owner->getMethodNames());

        // Install the target code and write the tag entry naming it.
        $targetWasmId = SorobanClient::install(new InstallRequest(
            wasmBytes: file_get_contents(self::TARGET_CONTRACT_PATH, false),
            rpcUrl: $this->rpcUrl,
            network: $this->network,
            sourceAccountKeyPair: $this->keyPair,
        ));
        $owner->invokeMethod('set_executable', [
            XdrSCVal::forString(self::EXECUTABLE_TAG),
            XdrSCVal::forBytes(hex2bin($targetWasmId)),
        ]);

        // The reference resolves to the stored wasm id; an unknown tag stays null.
        $ownerAddress = Address::fromContractId(StrKey::decodeContractIdHex($owner->getContractId()));
        $ref = new XdrContractExecutableExternalRef($ownerAddress->toXdr(), self::EXECUTABLE_TAG);
        $this->assertSame($targetWasmId, $this->server->loadWasmIdForExternalRef($ref));
        $this->assertNull($this->server->loadWasmIdForExternalRef(
            new XdrContractExecutableExternalRef($ownerAddress->toXdr(), 'no-such-tag')));

        // Deploy from the reference with an explicit salt; the created contract id must
        // match the derivation, and the instance must run the target's code.
        $salt = random_bytes(32);
        $predictedContractId = Address::deriveContractId(
            Address::fromAccountId($this->accountId), $salt, $this->network);
        $client = SorobanClient::deployFromExternalRef(new DeployFromExternalRefRequest(
            rpcUrl: $this->rpcUrl,
            network: $this->network,
            sourceAccountKeyPair: $this->keyPair,
            executableOwner: $ownerAddress,
            tag: self::EXECUTABLE_TAG,
            salt: $salt,
        ));
        $this->assertSame($predictedContractId, $client->getContractId());
        $this->assertContains('hello', $client->getMethodNames());

        // The instance entry carries the external reference arm naming owner and tag.
        $instanceEntry = $this->server->getContractData(
            StrKey::decodeContractIdHex($client->getContractId()),
            XdrSCVal::forLedgerKeyContractInstance(),
            XdrContractDataDurability::PERSISTENT(),
        );
        $this->assertNotNull($instanceEntry);
        $executable = $instanceEntry->getLedgerEntryDataXdr()->contractData?->val->instance?->executable;
        $this->assertNotNull($executable);
        $this->assertSame(XdrContractExecutableType::CONTRACT_EXECUTABLE_EXTERNAL_REF, $executable->type->value);
        $this->assertSame(
            StrKey::decodeContractIdHex($owner->getContractId()),
            $executable->externalRef?->executableOwner->getCanonicalContractIdHex(),
        );
        $this->assertSame(self::EXECUTABLE_TAG, $executable->externalRef?->tag);

        // The loading paths recover the target's code and spec through the reference.
        $codeEntry = $this->server->loadContractCodeForContractId($client->getContractId());
        $this->assertNotNull($codeEntry);
        $this->assertSame(
            hash('sha256', file_get_contents(self::TARGET_CONTRACT_PATH, false)),
            hash('sha256', $codeEntry->code->value),
        );
        $info = $this->server->loadContractInfoForContractId($client->getContractId());
        $this->assertNotNull($info);
        $functionNames = array_map(static function ($func) {
            return $func->name;
        }, $info->funcs);
        $this->assertContains('hello', $functionNames);

        // The deployed instance is live: it answers as the target contract.
        $result = $client->invokeMethod('hello', [XdrSCVal::forSymbol('friend')]);
        $this->assertSame('Hello', $result->vec[0]->sym);
        $this->assertSame('friend', $result->vec[1]->sym);
    }
}
