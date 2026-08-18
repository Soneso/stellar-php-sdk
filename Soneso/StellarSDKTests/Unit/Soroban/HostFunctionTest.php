<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Soroban;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeNative;
use Soneso\StellarSDK\CreateContractFromExternalRefHostFunction;
use Soneso\StellarSDK\CreateContractFromExternalRefWithConstructorHostFunction;
use Soneso\StellarSDK\CreateContractHostFunction;
use Soneso\StellarSDK\CreateContractWithConstructorHostFunction;
use Soneso\StellarSDK\DeploySACWithAssetHostFunction;
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperation;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrContractExecutable;
use Soneso\StellarSDK\Xdr\XdrContractIDPreimage;
use Soneso\StellarSDK\Xdr\XdrContractIDPreimageType;
use Soneso\StellarSDK\Xdr\XdrCreateContractArgs;
use Soneso\StellarSDK\Xdr\XdrCreateContractArgsV2;
use Soneso\StellarSDK\Xdr\XdrHostFunction;
use Soneso\StellarSDK\Xdr\XdrInvokeHostFunctionOp;
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
    private const TEST_ISSUER_ID = 'GABAEAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEJXA';
    private const TEST_CONTRACT_ID = 'CDLZFC3SYJYDZT7K67VZ75HPJVIEUVNIXF47ZG2FB2RMQQVU2HHGCYSC';
    private const TEST_OWNER_ID_HEX = 'cdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcdcd';

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

    // CreateContractWithConstructorHostFunction Tests

    public function testCreateContractWithConstructorToXdrRoundTrip(): void
    {
        $address = Address::fromAccountId(self::TEST_ACCOUNT_ID);
        $wasmId = str_repeat("ab", 32);
        $salt = str_repeat("\x11", 32);
        $args = [XdrSCVal::forU32(7)];

        $original = new CreateContractWithConstructorHostFunction($address, $wasmId, $args, $salt);
        $xdr = $original->toXdr();
        $decoded = CreateContractWithConstructorHostFunction::fromXdr($xdr);

        $this->assertEquals($original->getWasmId(), $decoded->getWasmId());
        $this->assertEquals($original->getSalt(), $decoded->getSalt());
        $this->assertEquals($original->getAddress()->accountId, $decoded->getAddress()->accountId);
        $this->assertCount(1, $decoded->getConstructorArgs());
        $this->assertEquals(7, $decoded->getConstructorArgs()[0]->u32);
        $this->assertEquals(base64_encode($xdr->encode()), base64_encode($decoded->toXdr()->encode()));
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

    // InvokeHostFunctionOperation::fromXdrOperation v2 deserialization tests

    public function testDeploySACWithAssetFromXdrV2RoundTrip(): void
    {
        $asset = Asset::createNonNativeAsset("USD", self::TEST_ISSUER_ID);

        // Build a CREATE_CONTRACT_V2 XdrHostFunction with FROM_ASSET preimage
        $preimage = XdrContractIDPreimage::forAsset($asset->toXdr());
        $executable = XdrContractExecutable::forToken();
        $constructorArgs = [];
        $createContractV2Args = new XdrCreateContractArgsV2($preimage, $executable, $constructorArgs);
        $xdrHostFunction = XdrHostFunction::forCreatingContractV2WithArgs($createContractV2Args);

        $xdrOp = new XdrInvokeHostFunctionOp($xdrHostFunction, []);

        $operation = InvokeHostFunctionOperation::fromXdrOperation($xdrOp);

        $this->assertInstanceOf(DeploySACWithAssetHostFunction::class, $operation->getFunction());
        /** @var DeploySACWithAssetHostFunction $hostFunction */
        $hostFunction = $operation->getFunction();
        $this->assertEquals("USD", $hostFunction->getAsset()->getCode());
    }

    public function testDeploySACWithNativeAssetFromXdrV2RoundTrip(): void
    {
        $asset = new AssetTypeNative();

        // Build a CREATE_CONTRACT_V2 XdrHostFunction with FROM_ASSET preimage (native asset)
        $preimage = XdrContractIDPreimage::forAsset($asset->toXdr());
        $executable = XdrContractExecutable::forToken();
        $constructorArgs = [];
        $createContractV2Args = new XdrCreateContractArgsV2($preimage, $executable, $constructorArgs);
        $xdrHostFunction = XdrHostFunction::forCreatingContractV2WithArgs($createContractV2Args);

        $xdrOp = new XdrInvokeHostFunctionOp($xdrHostFunction, []);

        $operation = InvokeHostFunctionOperation::fromXdrOperation($xdrOp);

        $this->assertInstanceOf(DeploySACWithAssetHostFunction::class, $operation->getFunction());
        /** @var DeploySACWithAssetHostFunction $hostFunction */
        $hostFunction = $operation->getFunction();
        $this->assertInstanceOf(AssetTypeNative::class, $hostFunction->getAsset());
    }

    // CreateContractFromExternalRefHostFunction Tests

    public function testCreateContractFromExternalRefToXdrRoundTrip(): void
    {
        $address = Address::fromAccountId(self::TEST_ACCOUNT_ID);
        $owner = Address::fromContractId(self::TEST_OWNER_ID_HEX);
        $salt = str_repeat("\x11", 32);

        $original = new CreateContractFromExternalRefHostFunction($address, $owner, "token-v1", $salt);
        $xdr = $original->toXdr();
        $decodedXdr = XdrHostFunction::decode(new XdrBuffer($xdr->encode()));
        $decoded = CreateContractFromExternalRefHostFunction::fromXdr($decodedXdr);

        $this->assertEquals(self::TEST_ACCOUNT_ID, $decoded->getAddress()->accountId);
        $this->assertEquals(self::TEST_OWNER_ID_HEX, $decoded->getExecutableOwner()->contractId);
        $this->assertEquals("token-v1", $decoded->getTag());
        $this->assertSame($salt, $decoded->getSalt());
        $this->assertEquals(base64_encode($xdr->encode()), base64_encode($decoded->toXdr()->encode()));
    }

    public function testCreateContractFromExternalRefWithConstructorToXdrRoundTrip(): void
    {
        $address = Address::fromAccountId(self::TEST_ACCOUNT_ID);
        $owner = Address::fromContractId(self::TEST_OWNER_ID_HEX);
        $salt = str_repeat("\x11", 32);
        $args = [XdrSCVal::forU32(7)];

        $original = new CreateContractFromExternalRefWithConstructorHostFunction($address, $owner, "token-v1", $args, $salt);
        $xdr = $original->toXdr();
        $decodedXdr = XdrHostFunction::decode(new XdrBuffer($xdr->encode()));
        $decoded = CreateContractFromExternalRefWithConstructorHostFunction::fromXdr($decodedXdr);

        $this->assertEquals(self::TEST_ACCOUNT_ID, $decoded->getAddress()->accountId);
        $this->assertEquals(self::TEST_OWNER_ID_HEX, $decoded->getExecutableOwner()->contractId);
        $this->assertEquals("token-v1", $decoded->getTag());
        $this->assertSame($salt, $decoded->getSalt());
        $this->assertCount(1, $decoded->getConstructorArgs());
        $this->assertEquals(7, $decoded->getConstructorArgs()[0]->u32);
        $this->assertEquals(base64_encode($xdr->encode()), base64_encode($decoded->toXdr()->encode()));
    }

    public function testCreateContractFromExternalRefWithoutSaltGeneratesSalt(): void
    {
        $address = Address::fromAccountId(self::TEST_ACCOUNT_ID);
        $owner = Address::fromContractId(self::TEST_OWNER_ID_HEX);

        $plain = new CreateContractFromExternalRefHostFunction($address, $owner, "token-v1");
        $this->assertEquals(32, strlen($plain->getSalt()));
        $this->assertSame($plain->getSalt(), $plain->toXdr()->createContract->contractIDPreimage->salt);

        $withConstructor = new CreateContractFromExternalRefWithConstructorHostFunction($address, $owner, "token-v1", []);
        $this->assertEquals(32, strlen($withConstructor->getSalt()));
        $this->assertSame($withConstructor->getSalt(), $withConstructor->toXdr()->createContractV2->contractIDPreimage->salt);
    }

    public function testCreateContractFromExternalRefSettersReplaceEveryField(): void
    {
        $salt1 = str_repeat("\x11", 32);
        $salt2 = str_repeat("\x22", 32);
        $ownerIdHex2 = str_repeat("ef", 32);

        $hostFunction = new CreateContractFromExternalRefHostFunction(
            Address::fromAccountId(self::TEST_ACCOUNT_ID),
            Address::fromContractId(self::TEST_OWNER_ID_HEX),
            "token-v1",
            $salt1
        );
        $hostFunction->setAddress(Address::fromAccountId(self::TEST_ISSUER_ID));
        $hostFunction->setExecutableOwner(Address::fromContractId($ownerIdHex2));
        $hostFunction->setTag("token-v2");
        $hostFunction->setSalt($salt2);

        $expected = new CreateContractFromExternalRefHostFunction(
            Address::fromAccountId(self::TEST_ISSUER_ID),
            Address::fromContractId($ownerIdHex2),
            "token-v2",
            $salt2
        );
        $this->assertEquals(self::TEST_ISSUER_ID, $hostFunction->getAddress()->accountId);
        $this->assertEquals(base64_encode($expected->toXdr()->encode()), base64_encode($hostFunction->toXdr()->encode()));
    }

    public function testCreateContractFromExternalRefWithConstructorSettersReplaceEveryField(): void
    {
        $salt1 = str_repeat("\x11", 32);
        $salt2 = str_repeat("\x22", 32);
        $ownerIdHex2 = str_repeat("ef", 32);

        $hostFunction = new CreateContractFromExternalRefWithConstructorHostFunction(
            Address::fromAccountId(self::TEST_ACCOUNT_ID),
            Address::fromContractId(self::TEST_OWNER_ID_HEX),
            "token-v1",
            [XdrSCVal::forU32(7)],
            $salt1
        );
        $hostFunction->setAddress(Address::fromAccountId(self::TEST_ISSUER_ID));
        $hostFunction->setExecutableOwner(Address::fromContractId($ownerIdHex2));
        $hostFunction->setTag("token-v2");
        $hostFunction->setConstructorArgs([XdrSCVal::forU32(9)]);
        $hostFunction->setSalt($salt2);

        $expected = new CreateContractFromExternalRefWithConstructorHostFunction(
            Address::fromAccountId(self::TEST_ISSUER_ID),
            Address::fromContractId($ownerIdHex2),
            "token-v2",
            [XdrSCVal::forU32(9)],
            $salt2
        );
        $this->assertEquals(self::TEST_ISSUER_ID, $hostFunction->getAddress()->accountId);
        $this->assertEquals(base64_encode($expected->toXdr()->encode()), base64_encode($hostFunction->toXdr()->encode()));
    }

    public function testExternalRefBinaryTagSurvivesRoundTrip(): void
    {
        $tag = "\x80\xff\x00tag\x01";
        $address = Address::fromAccountId(self::TEST_ACCOUNT_ID);
        $owner = Address::fromContractId(self::TEST_OWNER_ID_HEX);
        $salt = str_repeat("\x11", 32);

        $original = new CreateContractFromExternalRefHostFunction($address, $owner, $tag, $salt);
        $decodedXdr = XdrHostFunction::decode(new XdrBuffer($original->toXdr()->encode()));
        $decoded = CreateContractFromExternalRefHostFunction::fromXdr($decodedXdr);

        $this->assertSame($tag, $decoded->getTag());
        $this->assertEquals(base64_encode($original->toXdr()->encode()), base64_encode($decoded->toXdr()->encode()));
    }

    public function testFromXdrOperationParsesExternalRefCreate(): void
    {
        $hostFunction = new CreateContractFromExternalRefHostFunction(
            Address::fromAccountId(self::TEST_ACCOUNT_ID),
            Address::fromContractId(self::TEST_OWNER_ID_HEX),
            "token-v1",
            str_repeat("\x11", 32)
        );
        $xdrOp = new XdrInvokeHostFunctionOp($hostFunction->toXdr(), []);

        $operation = InvokeHostFunctionOperation::fromXdrOperation($xdrOp);

        $this->assertInstanceOf(CreateContractFromExternalRefHostFunction::class, $operation->getFunction());
        /** @var CreateContractFromExternalRefHostFunction $parsed */
        $parsed = $operation->getFunction();
        $this->assertEquals(self::TEST_OWNER_ID_HEX, $parsed->getExecutableOwner()->contractId);
        $this->assertEquals("token-v1", $parsed->getTag());
        $this->assertEquals(base64_encode($hostFunction->toXdr()->encode()), base64_encode($parsed->toXdr()->encode()));
    }

    public function testFromXdrOperationParsesExternalRefCreateWithConstructor(): void
    {
        $hostFunction = new CreateContractFromExternalRefWithConstructorHostFunction(
            Address::fromAccountId(self::TEST_ACCOUNT_ID),
            Address::fromContractId(self::TEST_OWNER_ID_HEX),
            "token-v1",
            [XdrSCVal::forU32(7)],
            str_repeat("\x11", 32)
        );
        $xdrOp = new XdrInvokeHostFunctionOp($hostFunction->toXdr(), []);

        $operation = InvokeHostFunctionOperation::fromXdrOperation($xdrOp);

        $this->assertInstanceOf(CreateContractFromExternalRefWithConstructorHostFunction::class, $operation->getFunction());
        /** @var CreateContractFromExternalRefWithConstructorHostFunction $parsed */
        $parsed = $operation->getFunction();
        $this->assertEquals(self::TEST_OWNER_ID_HEX, $parsed->getExecutableOwner()->contractId);
        $this->assertEquals("token-v1", $parsed->getTag());
        $this->assertCount(1, $parsed->getConstructorArgs());
        $this->assertEquals(base64_encode($hostFunction->toXdr()->encode()), base64_encode($parsed->toXdr()->encode()));
    }

    public function testFromXdrOperationRejectsInvalidExecutableCombinations(): void
    {
        $ownerXdr = Address::fromContractId(self::TEST_OWNER_ID_HEX)->toXdr();
        $assetPreimage = XdrContractIDPreimage::forAsset((new AssetTypeNative())->toXdr());
        $addressPreimage = new XdrContractIDPreimage(XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ADDRESS());
        $addressPreimage->address = Address::fromAccountId(self::TEST_ACCOUNT_ID)->toXdr();
        $addressPreimage->salt = str_repeat("\x11", 32);
        $externalRefExecutable = XdrContractExecutable::forExternalRef($ownerXdr, "token-v1");
        $tokenExecutable = XdrContractExecutable::forToken();

        $cases = [
            'CREATE_CONTRACT asset preimage with external ref executable' =>
                XdrHostFunction::forCreatingContractWithArgs(new XdrCreateContractArgs($assetPreimage, $externalRefExecutable)),
            'CREATE_CONTRACT_V2 asset preimage with external ref executable' =>
                XdrHostFunction::forCreatingContractV2WithArgs(new XdrCreateContractArgsV2($assetPreimage, $externalRefExecutable, [])),
            'CREATE_CONTRACT address preimage with stellar asset executable' =>
                XdrHostFunction::forCreatingContractWithArgs(new XdrCreateContractArgs($addressPreimage, $tokenExecutable)),
            'CREATE_CONTRACT_V2 address preimage with stellar asset executable' =>
                XdrHostFunction::forCreatingContractV2WithArgs(new XdrCreateContractArgsV2($addressPreimage, $tokenExecutable, [])),
        ];

        foreach ($cases as $label => $xdrHostFunction) {
            $threw = false;
            try {
                InvokeHostFunctionOperation::fromXdrOperation(new XdrInvokeHostFunctionOp($xdrHostFunction, []));
            } catch (Exception $e) {
                $threw = true;
                $this->assertStringContainsStringIgnoringCase("invalid argument", $e->getMessage(), $label);
            }
            $this->assertTrue($threw, "expected an exception for: " . $label);
        }
    }

    public function testWasmCreateContractClassesRejectExternalRefXdr(): void
    {
        $address = Address::fromAccountId(self::TEST_ACCOUNT_ID);
        $owner = Address::fromContractId(self::TEST_OWNER_ID_HEX);
        $salt = str_repeat("\x11", 32);

        $externalRefXdr = (new CreateContractFromExternalRefHostFunction($address, $owner, "token-v1", $salt))->toXdr();
        $threw = false;
        try {
            CreateContractHostFunction::fromXdr($externalRefXdr);
        } catch (Exception $e) {
            $threw = true;
            $this->assertStringContainsStringIgnoringCase("invalid argument", $e->getMessage());
        }
        $this->assertTrue($threw, "CreateContractHostFunction accepted an external ref executable");

        $externalRefV2Xdr = (new CreateContractFromExternalRefWithConstructorHostFunction($address, $owner, "token-v1", [], $salt))->toXdr();
        $threw = false;
        try {
            CreateContractWithConstructorHostFunction::fromXdr($externalRefV2Xdr);
        } catch (Exception $e) {
            $threw = true;
            $this->assertStringContainsStringIgnoringCase("invalid argument", $e->getMessage());
        }
        $this->assertTrue($threw, "CreateContractWithConstructorHostFunction accepted an external ref executable");
    }

    public function testCreateContractFromExternalRefFromXdrWrongExecutableThrows(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Invalid argument");

        // Create contract XDR with a wasm executable instead of an external reference
        $wasmHostFunction = new CreateContractHostFunction(
            Address::fromAccountId(self::TEST_ACCOUNT_ID),
            str_repeat("ab", 32)
        );

        CreateContractFromExternalRefHostFunction::fromXdr($wasmHostFunction->toXdr());
    }

    public function testCreateContractFromExternalRefWithConstructorFromXdrWrongExecutableThrows(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Invalid argument");

        // Create contract v2 XDR with a wasm executable instead of an external reference
        $wasmHostFunction = new CreateContractWithConstructorHostFunction(
            Address::fromAccountId(self::TEST_ACCOUNT_ID),
            str_repeat("ab", 32),
            []
        );

        CreateContractFromExternalRefWithConstructorHostFunction::fromXdr($wasmHostFunction->toXdr());
    }
}
