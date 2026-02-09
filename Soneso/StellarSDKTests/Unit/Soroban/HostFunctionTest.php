<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Soroban;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeNative;
use Soneso\StellarSDK\CreateContractHostFunction;
use Soneso\StellarSDK\DeploySACWithAssetHostFunction;
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValType;
use Exception;

/**
 * Unit tests for Soroban Host Function classes
 *
 * Tests CreateContractHostFunction, DeploySACWithAssetHostFunction,
 * and InvokeContractHostFunction.
 */
class HostFunctionTest extends TestCase
{
    private const TEST_ACCOUNT_ID = 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H';
    private const TEST_ISSUER_ID = 'GDQP2KPQGKIHYJGXNUIYOMHARUARCA7DJT5FO2FFOOUJ3DANUBER3WPR';
    private const TEST_CONTRACT_ID = 'CDLZFC3SYJYDZT7K67VZ75HPJVIEUVNIXF47ZG2FB2RMQQVU2HHGCYSC';

    // CreateContractHostFunction Tests

    public function testCreateContractConstructorWithoutSalt(): void
    {
        $address = Address::fromAccountId(self::TEST_ACCOUNT_ID);
        $wasmId = str_repeat("ab", 32);

        $hostFunction = new CreateContractHostFunction($address, $wasmId);

        $this->assertEquals($address->accountId, $hostFunction->getAddress()->accountId);
        $this->assertEquals($wasmId, $hostFunction->getWasmId());
        $this->assertNotEmpty($hostFunction->getSalt());
        $this->assertEquals(32, strlen($hostFunction->getSalt()));
    }

    public function testCreateContractConstructorWithSalt(): void
    {
        $address = Address::fromAccountId(self::TEST_ACCOUNT_ID);
        $wasmId = str_repeat("ab", 32);
        $salt = str_repeat("\x00", 32);

        $hostFunction = new CreateContractHostFunction($address, $wasmId, $salt);

        $this->assertEquals($salt, $hostFunction->getSalt());
    }

    public function testCreateContractToXdrRoundTrip(): void
    {
        $address = Address::fromAccountId(self::TEST_ACCOUNT_ID);
        $wasmId = str_repeat("ab", 32);
        $salt = str_repeat("\x11", 32);

        $original = new CreateContractHostFunction($address, $wasmId, $salt);
        $xdr = $original->toXdr();
        $decoded = CreateContractHostFunction::fromXdr($xdr);

        $this->assertEquals($original->getWasmId(), $decoded->getWasmId());
        $this->assertEquals($original->getSalt(), $decoded->getSalt());
        $this->assertEquals($original->getAddress()->accountId, $decoded->getAddress()->accountId);
    }

    public function testCreateContractSetAddress(): void
    {
        $address1 = Address::fromAccountId(self::TEST_ACCOUNT_ID);
        $address2 = Address::fromAccountId(self::TEST_ISSUER_ID);
        $wasmId = str_repeat("ab", 32);

        $hostFunction = new CreateContractHostFunction($address1, $wasmId);
        $this->assertEquals($address1->accountId, $hostFunction->getAddress()->accountId);

        $hostFunction->setAddress($address2);
        $this->assertEquals($address2->accountId, $hostFunction->getAddress()->accountId);
    }

    public function testCreateContractSetWasmId(): void
    {
        $address = Address::fromAccountId(self::TEST_ACCOUNT_ID);
        $wasmId1 = str_repeat("ab", 32);
        $wasmId2 = str_repeat("cd", 32);

        $hostFunction = new CreateContractHostFunction($address, $wasmId1);
        $this->assertEquals($wasmId1, $hostFunction->getWasmId());

        $hostFunction->setWasmId($wasmId2);
        $this->assertEquals($wasmId2, $hostFunction->getWasmId());
    }

    public function testCreateContractSetSalt(): void
    {
        $address = Address::fromAccountId(self::TEST_ACCOUNT_ID);
        $wasmId = str_repeat("ab", 32);
        $salt1 = str_repeat("\x11", 32);
        $salt2 = str_repeat("\x22", 32);

        $hostFunction = new CreateContractHostFunction($address, $wasmId, $salt1);
        $this->assertEquals($salt1, $hostFunction->getSalt());

        $hostFunction->setSalt($salt2);
        $this->assertEquals($salt2, $hostFunction->getSalt());
    }

    // DeploySACWithAssetHostFunction Tests

    public function testDeploySACWithAssetConstructorNativeAsset(): void
    {
        $asset = new AssetTypeNative();
        $hostFunction = new DeploySACWithAssetHostFunction($asset);

        $this->assertInstanceOf(AssetTypeNative::class, $hostFunction->getAsset());
    }

    public function testDeploySACWithAssetConstructorCreditAsset(): void
    {
        $asset = Asset::createNonNativeAsset("USD", self::TEST_ISSUER_ID);
        $hostFunction = new DeploySACWithAssetHostFunction($asset);

        $this->assertEquals("USD", $hostFunction->getAsset()->getCode());
    }

    public function testDeploySACWithAssetToXdrRoundTrip(): void
    {
        $asset = Asset::createNonNativeAsset("USD", self::TEST_ISSUER_ID);
        $original = new DeploySACWithAssetHostFunction($asset);

        $xdr = $original->toXdr();
        $decoded = DeploySACWithAssetHostFunction::fromXdr($xdr);

        $this->assertEquals($original->getAsset()->getCode(), $decoded->getAsset()->getCode());
    }

    public function testDeploySACWithAssetToXdrRoundTripNative(): void
    {
        $asset = new AssetTypeNative();
        $original = new DeploySACWithAssetHostFunction($asset);

        $xdr = $original->toXdr();
        $decoded = DeploySACWithAssetHostFunction::fromXdr($xdr);

        $this->assertInstanceOf(AssetTypeNative::class, $decoded->getAsset());
    }

    public function testDeploySACWithAssetSetAsset(): void
    {
        $asset1 = Asset::createNonNativeAsset("USD", self::TEST_ISSUER_ID);
        $asset2 = Asset::createNonNativeAsset("EUR", self::TEST_ISSUER_ID);

        $hostFunction = new DeploySACWithAssetHostFunction($asset1);
        $this->assertEquals("USD", $hostFunction->getAsset()->getCode());

        $hostFunction->setAsset($asset2);
        $this->assertEquals("EUR", $hostFunction->getAsset()->getCode());
    }

    // InvokeContractHostFunction Tests

    public function testInvokeContractConstructorWithoutArguments(): void
    {
        $hostFunction = new InvokeContractHostFunction(
            self::TEST_CONTRACT_ID,
            "transfer"
        );

        $this->assertEquals(self::TEST_CONTRACT_ID, $hostFunction->getContractId());
        $this->assertEquals("transfer", $hostFunction->getFunctionName());
        $this->assertNull($hostFunction->getArguments());
    }

    public function testInvokeContractConstructorWithArguments(): void
    {
        $arg1 = new XdrSCVal(new XdrSCValType(XdrSCValType::SCV_BOOL));
        $arg1->b = true;

        $arg2 = new XdrSCVal(new XdrSCValType(XdrSCValType::SCV_U64));
        $arg2->u64 = 1000;

        $hostFunction = new InvokeContractHostFunction(
            self::TEST_CONTRACT_ID,
            "transfer",
            [$arg1, $arg2]
        );

        $this->assertCount(2, $hostFunction->getArguments());
    }

    public function testInvokeContractConstructorWithEmptyArguments(): void
    {
        $hostFunction = new InvokeContractHostFunction(
            self::TEST_CONTRACT_ID,
            "get_balance",
            []
        );

        $this->assertEmpty($hostFunction->getArguments());
    }

    public function testInvokeContractToXdrRoundTrip(): void
    {
        $arg = new XdrSCVal(new XdrSCValType(XdrSCValType::SCV_BOOL));
        $arg->b = true;

        $original = new InvokeContractHostFunction(
            self::TEST_CONTRACT_ID,
            "my_function",
            [$arg]
        );

        $xdr = $original->toXdr();
        $decoded = InvokeContractHostFunction::fromXdr($xdr);

        $this->assertEquals($original->getContractId(), $decoded->getContractId());
        $this->assertEquals($original->getFunctionName(), $decoded->getFunctionName());
        $this->assertCount(1, $decoded->getArguments());
    }

    public function testInvokeContractToXdrRoundTripNoArgs(): void
    {
        $original = new InvokeContractHostFunction(
            self::TEST_CONTRACT_ID,
            "get_balance"
        );

        $xdr = $original->toXdr();
        $decoded = InvokeContractHostFunction::fromXdr($xdr);

        $this->assertEquals($original->getContractId(), $decoded->getContractId());
        $this->assertEquals($original->getFunctionName(), $decoded->getFunctionName());
        $this->assertEmpty($decoded->getArguments());
    }

    public function testInvokeContractSetContractId(): void
    {
        $hostFunction = new InvokeContractHostFunction(
            self::TEST_CONTRACT_ID,
            "transfer"
        );
        $this->assertEquals(self::TEST_CONTRACT_ID, $hostFunction->getContractId());

        $newContractId = "CAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAITA4";
        $hostFunction->setContractId($newContractId);
        $this->assertEquals($newContractId, $hostFunction->getContractId());
    }

    public function testInvokeContractSetFunctionName(): void
    {
        $hostFunction = new InvokeContractHostFunction(
            self::TEST_CONTRACT_ID,
            "transfer"
        );
        $this->assertEquals("transfer", $hostFunction->getFunctionName());

        $hostFunction->setFunctionName("approve");
        $this->assertEquals("approve", $hostFunction->getFunctionName());
    }

    public function testInvokeContractSetArguments(): void
    {
        $hostFunction = new InvokeContractHostFunction(
            self::TEST_CONTRACT_ID,
            "transfer"
        );
        $this->assertNull($hostFunction->getArguments());

        $arg = new XdrSCVal(new XdrSCValType(XdrSCValType::SCV_U32));
        $arg->u32 = 42;
        $hostFunction->setArguments([$arg]);

        $this->assertCount(1, $hostFunction->getArguments());
    }

    public function testInvokeContractSetArgumentsToNull(): void
    {
        $arg = new XdrSCVal(new XdrSCValType(XdrSCValType::SCV_BOOL));
        $arg->b = true;

        $hostFunction = new InvokeContractHostFunction(
            self::TEST_CONTRACT_ID,
            "transfer",
            [$arg]
        );
        $this->assertCount(1, $hostFunction->getArguments());

        $hostFunction->setArguments(null);
        $this->assertNull($hostFunction->getArguments());
    }

    public function testInvokeContractMultipleArguments(): void
    {
        $args = [];
        for ($i = 0; $i < 5; $i++) {
            $arg = new XdrSCVal(new XdrSCValType(XdrSCValType::SCV_U32));
            $arg->u32 = $i;
            $args[] = $arg;
        }

        $original = new InvokeContractHostFunction(
            self::TEST_CONTRACT_ID,
            "multi_arg_function",
            $args
        );

        $xdr = $original->toXdr();
        $decoded = InvokeContractHostFunction::fromXdr($xdr);

        $this->assertCount(5, $decoded->getArguments());
    }

    // Exception Tests

    public function testCreateContractFromXdrInvalidTypeThrows(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Invalid argument");

        // Create XDR with wrong type (invoke contract instead of create contract)
        $hostFunction = new InvokeContractHostFunction(
            self::TEST_CONTRACT_ID,
            "transfer"
        );
        $xdr = $hostFunction->toXdr();

        // Try to decode as CreateContractHostFunction
        CreateContractHostFunction::fromXdr($xdr);
    }

    public function testDeploySACWithAssetFromXdrInvalidTypeThrows(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Invalid argument");

        // Create XDR with wrong type (invoke contract instead of create contract)
        $hostFunction = new InvokeContractHostFunction(
            self::TEST_CONTRACT_ID,
            "transfer"
        );
        $xdr = $hostFunction->toXdr();

        // Try to decode as DeploySACWithAssetHostFunction
        DeploySACWithAssetHostFunction::fromXdr($xdr);
    }

    public function testInvokeContractFromXdrNullInvokeContractThrows(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Invalid argument");

        // Create an XDR with create contract type (no invokeContract field)
        $address = Address::fromAccountId(self::TEST_ACCOUNT_ID);
        $wasmId = str_repeat("ab", 32);
        $salt = str_repeat("\x11", 32);
        $createContractHostFunction = new CreateContractHostFunction($address, $wasmId, $salt);
        $xdr = $createContractHostFunction->toXdr();

        // Try to decode as InvokeContractHostFunction
        InvokeContractHostFunction::fromXdr($xdr);
    }

    public function testDeploySACWithAssetFromXdrWrongPreimageTypeThrows(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Invalid argument");

        // Create XDR with address preimage type instead of asset preimage type
        $address = Address::fromAccountId(self::TEST_ACCOUNT_ID);
        $wasmId = str_repeat("ab", 32);
        $salt = str_repeat("\x11", 32);
        $createContractHostFunction = new CreateContractHostFunction($address, $wasmId, $salt);
        $xdr = $createContractHostFunction->toXdr();

        // Try to decode as DeploySACWithAssetHostFunction
        DeploySACWithAssetHostFunction::fromXdr($xdr);
    }

    public function testCreateContractFromXdrWrongExecutableTypeThrows(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Invalid argument");

        // Create XDR with asset preimage type instead of address preimage type
        $asset = Asset::createNonNativeAsset("USD", self::TEST_ISSUER_ID);
        $deploySACHostFunction = new DeploySACWithAssetHostFunction($asset);
        $xdr = $deploySACHostFunction->toXdr();

        // Try to decode as CreateContractHostFunction
        CreateContractHostFunction::fromXdr($xdr);
    }
}
