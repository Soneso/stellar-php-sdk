<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Integration;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Xdr\XdrContractDataDurability;
use Soneso\StellarSDK\Xdr\XdrContractExecutableType;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDKTests\PrintLogger;
use Throwable;

/**
 * Protocol 28 (CAP-85) external reference executable read-path integration test.
 *
 * Reads a contract whose instance carries an external reference executable and verifies
 * that the reference resolves to the owning contract's tag entry, the wasm id it holds,
 * and the contract code and parsed spec behind that wasm id.
 *
 * The test covers the read path only: writing an executable tag entry needs an owner
 * contract that exposes a tag-writing function, and no such contract ships with this
 * repo's test fixtures. The fixture constants below therefore name a contract that
 * already exists on the network, and the test skips while they are unfilled.
 */
class P28ExternalRefExecutableTest extends TestCase
{
    const TESTNET_SERVER_URL = "https://soroban-testnet.stellar.org";

    /** Contract id ("C...") of a contract created from an external reference. */
    const EXTERNAL_REF_CONTRACT_ID = '';
    /** Contract id ("C...") of the owner contract the reference names. */
    const OWNER_CONTRACT_ID = '';
    /** Tag under which the owner holds the executable tag entry. */
    const EXECUTABLE_TAG = '';
    /** Hex-encoded wasm id the tag entry is expected to resolve to. */
    const EXPECTED_WASM_ID = '';
    /** Name of a function the resolved contract spec is expected to export. */
    const EXPECTED_FUNCTION_NAME = '';

    /**
     * Set to 'testnet' to run; any other value skips the test.
     *
     * This gate follows the same convention used throughout the integration suite.
     */
    private string $testOn = 'testnet';

    private SorobanServer $server;

    public function setUp(): void
    {
        error_reporting(E_ALL);
        if ($this->testOn !== 'testnet') {
            $this->markTestSkipped('P28 external ref integration test requires testnet access. '
                . 'Set $testOn = "testnet" to enable.');
        }
        $this->server = new SorobanServer(self::TESTNET_SERVER_URL);
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

        foreach ([
            'EXTERNAL_REF_CONTRACT_ID' => self::EXTERNAL_REF_CONTRACT_ID,
            'OWNER_CONTRACT_ID' => self::OWNER_CONTRACT_ID,
            'EXECUTABLE_TAG' => self::EXECUTABLE_TAG,
            'EXPECTED_WASM_ID' => self::EXPECTED_WASM_ID,
            'EXPECTED_FUNCTION_NAME' => self::EXPECTED_FUNCTION_NAME,
        ] as $name => $value) {
            if ($value === '') {
                $this->markTestSkipped('Fixture constant ' . $name . ' is unfilled; it needs '
                    . 'an external reference contract deployed on testnet.');
            }
        }
    }

    public function testResolvesExternalRefExecutable(): void
    {
        // The instance entry must carry the external reference arm pointing at the
        // expected owner and tag.
        $instanceEntry = $this->server->getContractData(
            StrKey::decodeContractIdHex(self::EXTERNAL_REF_CONTRACT_ID),
            XdrSCVal::forLedgerKeyContractInstance(),
            XdrContractDataDurability::PERSISTENT(),
        );
        $this->assertNotNull($instanceEntry);
        $executable = $instanceEntry->getLedgerEntryDataXdr()->contractData?->val->instance?->executable;
        $this->assertNotNull($executable);
        $this->assertSame(XdrContractExecutableType::CONTRACT_EXECUTABLE_EXTERNAL_REF, $executable->type->value);
        $ref = $executable->externalRef;
        $this->assertNotNull($ref);
        $this->assertSame(
            StrKey::decodeContractIdHex(self::OWNER_CONTRACT_ID),
            $ref->executableOwner->getCanonicalContractIdHex(),
        );
        $this->assertSame(self::EXECUTABLE_TAG, $ref->tag);

        // The reference must resolve to the expected wasm id.
        $this->assertSame(self::EXPECTED_WASM_ID, $this->server->loadWasmIdForExternalRef($ref));

        // The loading paths must recover the code and its spec through the reference.
        $codeEntry = $this->server->loadContractCodeForContractId(self::EXTERNAL_REF_CONTRACT_ID);
        $this->assertNotNull($codeEntry);
        $this->assertNotSame('', $codeEntry->code->value);

        $info = $this->server->loadContractInfoForContractId(self::EXTERNAL_REF_CONTRACT_ID);
        $this->assertNotNull($info);
        $functionNames = array_map(static function ($func) {
            return $func->name;
        }, $info->funcs);
        $this->assertContains(self::EXPECTED_FUNCTION_NAME, $functionNames);
    }
}
