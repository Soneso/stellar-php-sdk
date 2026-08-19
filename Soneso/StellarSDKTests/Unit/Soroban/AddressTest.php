<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Soroban;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrSCAddress;
use Soneso\StellarSDK\Xdr\XdrSCAddressType;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValType;

class AddressTest extends TestCase
{
    private string $testAccountId;
    private string $testContractIdHex;
    private string $testContractIdStrKey;
    private string $testMuxedAccountId;
    private string $testClaimableBalanceIdHex;
    private string $testLiquidityPoolIdHex;

    public function setUp(): void
    {
        error_reporting(E_ALL);

        // Valid test data
        $this->testAccountId = 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H';
        $this->testContractIdHex = '3f0918bf77f7e30fe942e4bc2ce903ffa2d80e7f3e1f82ba58877f0eb73df0b7';
        $this->testContractIdStrKey = StrKey::encodeContractIdHex($this->testContractIdHex);
        $this->testMuxedAccountId = 'MA5SRA3BGOEN6ASL33AVTC2QV7G2PV3DU4A3VDMPEIEZVF2H4Z5YUAAAAAAACL7RNP5CM';

        // 32-byte hex IDs for claimable balance and liquidity pool
        $this->testClaimableBalanceIdHex = '00000000da0d57da7d4850e7fc10d2a9d0ebc731f7afb40574c03395b17d49149b91f5be';
        $this->testLiquidityPoolIdHex = 'dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7';
    }

    /**
     * Test creating Address from account ID
     */
    public function testFromAccountId(): void
    {
        $address = Address::fromAccountId($this->testAccountId);

        $this->assertEquals(Address::TYPE_ACCOUNT, $address->getType());
        $this->assertEquals($this->testAccountId, $address->getAccountId());
        $this->assertNull($address->getContractId());
        $this->assertNull($address->getMuxedAccountId());
        $this->assertNull($address->getClaimableBalanceId());
        $this->assertNull($address->getLiquidityPoolId());
    }

    /**
     * Test creating Address from contract ID
     */
    public function testFromContractId(): void
    {
        $address = Address::fromContractId($this->testContractIdHex);

        $this->assertEquals(Address::TYPE_CONTRACT, $address->getType());
        $this->assertEquals($this->testContractIdHex, $address->getContractId());
        $this->assertNull($address->getAccountId());
        $this->assertNull($address->getMuxedAccountId());
        $this->assertNull($address->getClaimableBalanceId());
        $this->assertNull($address->getLiquidityPoolId());
    }

    /**
     * Test creating Address from muxed account ID
     */
    public function testFromMuxedAccountId(): void
    {
        $address = Address::fromMuxedAccountId($this->testMuxedAccountId);

        $this->assertEquals(Address::TYPE_MUXED_ACCOUNT, $address->getType());
        $this->assertEquals($this->testMuxedAccountId, $address->getMuxedAccountId());
        $this->assertNull($address->getAccountId());
        $this->assertNull($address->getContractId());
        $this->assertNull($address->getClaimableBalanceId());
        $this->assertNull($address->getLiquidityPoolId());
    }

    /**
     * Test creating Address from claimable balance ID
     */
    public function testFromClaimableBalanceId(): void
    {
        $address = Address::fromClaimableBalanceId($this->testClaimableBalanceIdHex);

        $this->assertEquals(Address::TYPE_CLAIMABLE_BALANCE, $address->getType());
        $this->assertEquals($this->testClaimableBalanceIdHex, $address->getClaimableBalanceId());
        $this->assertNull($address->getAccountId());
        $this->assertNull($address->getContractId());
        $this->assertNull($address->getMuxedAccountId());
        $this->assertNull($address->getLiquidityPoolId());
    }

    /**
     * Test creating Address from liquidity pool ID
     */
    public function testFromLiquidityPoolId(): void
    {
        $address = Address::fromLiquidityPoolId($this->testLiquidityPoolIdHex);

        $this->assertEquals(Address::TYPE_LIQUIDITY_POOL, $address->getType());
        $this->assertEquals($this->testLiquidityPoolIdHex, $address->getLiquidityPoolId());
        $this->assertNull($address->getAccountId());
        $this->assertNull($address->getContractId());
        $this->assertNull($address->getMuxedAccountId());
        $this->assertNull($address->getClaimableBalanceId());
    }

    /**
     * Test Address XDR encoding and decoding for account type
     */
    public function testAccountXdrRoundtrip(): void
    {
        $original = Address::fromAccountId($this->testAccountId);
        $xdr = $original->toXdr();

        $this->assertInstanceOf(XdrSCAddress::class, $xdr);
        $this->assertEquals(XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT, $xdr->type->value);

        $decoded = Address::fromXdr($xdr);

        $this->assertEquals(Address::TYPE_ACCOUNT, $decoded->getType());
        $this->assertEquals($this->testAccountId, $decoded->getAccountId());
    }

    /**
     * Test Address XDR encoding and decoding for contract type
     */
    public function testContractXdrRoundtrip(): void
    {
        $original = Address::fromContractId($this->testContractIdHex);
        $xdr = $original->toXdr();

        $this->assertInstanceOf(XdrSCAddress::class, $xdr);
        $this->assertEquals(XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT, $xdr->type->value);

        $decoded = Address::fromXdr($xdr);

        $this->assertEquals(Address::TYPE_CONTRACT, $decoded->getType());
        $this->assertEquals($this->testContractIdHex, $decoded->getContractId());
    }

    /**
     * Test Address XDR encoding and decoding for muxed account type
     */
    public function testMuxedAccountXdrRoundtrip(): void
    {
        $original = Address::fromMuxedAccountId($this->testMuxedAccountId);
        $xdr = $original->toXdr();

        $this->assertInstanceOf(XdrSCAddress::class, $xdr);
        $this->assertEquals(XdrSCAddressType::SC_ADDRESS_TYPE_MUXED_ACCOUNT, $xdr->type->value);

        $decoded = Address::fromXdr($xdr);

        $this->assertEquals(Address::TYPE_MUXED_ACCOUNT, $decoded->getType());
        $this->assertEquals($this->testMuxedAccountId, $decoded->getMuxedAccountId());
    }

    /**
     * Test Address XDR encoding and decoding for claimable balance type
     */
    public function testClaimableBalanceXdrRoundtrip(): void
    {
        $original = Address::fromClaimableBalanceId($this->testClaimableBalanceIdHex);
        $xdr = $original->toXdr();

        $this->assertInstanceOf(XdrSCAddress::class, $xdr);
        $this->assertEquals(XdrSCAddressType::SC_ADDRESS_TYPE_CLAIMABLE_BALANCE, $xdr->type->value);

        $decoded = Address::fromXdr($xdr);

        $this->assertEquals(Address::TYPE_CLAIMABLE_BALANCE, $decoded->getType());
        // fromXdr reports the 72-character Horizon form whatever spelling built the XDR.
        $this->assertEquals($this->testClaimableBalanceIdHex, $decoded->getClaimableBalanceId());
    }

    /**
     * Test Address XDR encoding and decoding for liquidity pool type
     */
    public function testLiquidityPoolXdrRoundtrip(): void
    {
        $original = Address::fromLiquidityPoolId($this->testLiquidityPoolIdHex);
        $xdr = $original->toXdr();

        $this->assertInstanceOf(XdrSCAddress::class, $xdr);
        $this->assertEquals(XdrSCAddressType::SC_ADDRESS_TYPE_LIQUIDITY_POOL, $xdr->type->value);

        $decoded = Address::fromXdr($xdr);

        $this->assertEquals(Address::TYPE_LIQUIDITY_POOL, $decoded->getType());
        $this->assertEquals($this->testLiquidityPoolIdHex, $decoded->getLiquidityPoolId());
    }

    /**
     * Test Address to XdrSCVal conversion
     */
    public function testToXdrSCVal(): void
    {
        $address = Address::fromAccountId($this->testAccountId);
        $scVal = $address->toXdrSCVal();

        $this->assertInstanceOf(XdrSCVal::class, $scVal);
        $this->assertEquals(XdrSCValType::SCV_ADDRESS, $scVal->type->value);
        $this->assertNotNull($scVal->address);
    }

    /**
     * Test Address from XdrSCVal round-trip for an account address
     */
    public function testFromXdrSCVal(): void
    {
        $original = Address::fromAccountId($this->testAccountId);
        $scVal = $original->toXdrSCVal();

        $this->assertEquals(XdrSCValType::SCV_ADDRESS, $scVal->type->value);
        $this->assertNotNull($scVal->address);

        $decoded = Address::fromXdrSCVal($scVal);

        $this->assertEquals(Address::TYPE_ACCOUNT, $decoded->getType());
        $this->assertEquals($this->testAccountId, $decoded->getAccountId());
    }

    /**
     * Test Address from XdrSCVal round-trip for a contract address
     */
    public function testFromXdrSCValForContract(): void
    {
        $original = Address::fromContractId($this->testContractIdHex);
        $scVal = $original->toXdrSCVal();

        $this->assertEquals(XdrSCValType::SCV_ADDRESS, $scVal->type->value);
        $this->assertNotNull($scVal->address);

        $decoded = Address::fromXdrSCVal($scVal);

        $this->assertEquals(Address::TYPE_CONTRACT, $decoded->getType());
        $this->assertEquals($this->testContractIdHex, $decoded->getContractId());
    }

    /**
     * Test Address from XdrSCVal round-trip for a muxed account address
     */
    public function testFromXdrSCValForMuxedAccount(): void
    {
        $original = Address::fromMuxedAccountId($this->testMuxedAccountId);
        $scVal = $original->toXdrSCVal();

        $this->assertEquals(XdrSCValType::SCV_ADDRESS, $scVal->type->value);
        $this->assertNotNull($scVal->address);

        $decoded = Address::fromXdrSCVal($scVal);

        $this->assertEquals(Address::TYPE_MUXED_ACCOUNT, $decoded->getType());
        $this->assertEquals($this->testMuxedAccountId, $decoded->getMuxedAccountId());
    }

    /**
     * Test fromAnyId with account ID
     */
    public function testFromAnyIdWithAccountId(): void
    {
        $address = Address::fromAnyId($this->testAccountId);

        $this->assertNotNull($address);
        $this->assertEquals(Address::TYPE_ACCOUNT, $address->getType());
        $this->assertEquals($this->testAccountId, $address->getAccountId());
    }

    /**
     * Test fromAnyId with contract ID (StrKey format)
     */
    public function testFromAnyIdWithContractIdStrKey(): void
    {
        $address = Address::fromAnyId($this->testContractIdStrKey);

        $this->assertNotNull($address);
        $this->assertEquals(Address::TYPE_CONTRACT, $address->getType());
        $this->assertEquals($this->testContractIdHex, $address->getContractId());
    }

    /**
     * Test fromAnyId with contract ID (hex format)
     */
    public function testFromAnyIdWithContractIdHex(): void
    {
        $address = Address::fromAnyId($this->testContractIdHex);

        $this->assertNotNull($address);
        $this->assertEquals(Address::TYPE_CONTRACT, $address->getType());
        $this->assertEquals($this->testContractIdHex, $address->getContractId());
    }

    /**
     * Test fromAnyId with a muxed account ID (M-prefixed StrKey).
     */
    public function testFromAnyIdWithMuxedAccountId(): void
    {
        $address = Address::fromAnyId($this->testMuxedAccountId);

        $this->assertNotNull($address);
        $this->assertEquals(Address::TYPE_MUXED_ACCOUNT, $address->getType());
        $this->assertEquals($this->testMuxedAccountId, $address->getMuxedAccountId());
    }

    /**
     * A bare 32-byte hash in hexadecimal is not self-describing, so fromAnyId resolves
     * 64 hex characters as a contract id; a pool named by hex alone cannot be told apart
     * from a contract. The strkey spelling resolves as the pool (see the strkey test below).
     */
    public function testFromAnyIdWithLiquidityPoolIdHex(): void
    {
        $address = Address::fromAnyId($this->testLiquidityPoolIdHex);

        $this->assertNotNull($address);
        $this->assertEquals(Address::TYPE_CONTRACT, $address->getType());
        $this->assertEquals($this->testLiquidityPoolIdHex, $address->getContractId());
    }

    public function testFromAnyIdReadsALiquidityPoolStrkey(): void
    {
        $strKey = StrKey::encodeLiquidityPoolIdHex($this->testLiquidityPoolIdHex);
        $address = Address::fromAnyId($strKey);

        $this->assertNotNull($address);
        $this->assertEquals(Address::TYPE_LIQUIDITY_POOL, $address->getType());
        $this->assertEquals($this->testLiquidityPoolIdHex, $address->getLiquidityPoolId());
    }

    public function testFromAnyIdReadsAStrkeyPrefixedClaimableBalanceHex(): void
    {
        $bareHash = substr($this->testClaimableBalanceIdHex, 8);
        $address = Address::fromAnyId('00' . $bareHash);

        $this->assertNotNull($address);
        $this->assertEquals(Address::TYPE_CLAIMABLE_BALANCE, $address->getType());
        $this->assertEquals($this->testClaimableBalanceIdHex, $address->getClaimableBalanceId());
    }

    public function testFromAnyIdReadsAnXdrPrefixedClaimableBalanceHex(): void
    {
        $address = Address::fromAnyId($this->testClaimableBalanceIdHex);

        $this->assertNotNull($address);
        $this->assertEquals(Address::TYPE_CLAIMABLE_BALANCE, $address->getType());
        $this->assertEquals($this->testClaimableBalanceIdHex, $address->getClaimableBalanceId());
    }

    /**
     * Test fromAnyId with invalid ID
     */
    public function testFromAnyIdWithInvalidId(): void
    {
        $address = Address::fromAnyId('invalid_id_format');
        $this->assertNull($address);
    }

    /**
     * Test toStrKey for account address
     */
    public function testToStrKeyForAccount(): void
    {
        $address = Address::fromAccountId($this->testAccountId);
        $strKey = $address->toStrKey();

        $this->assertEquals($this->testAccountId, $strKey);
    }

    /**
     * Test toStrKey for contract address
     */
    public function testToStrKeyForContract(): void
    {
        $address = Address::fromContractId($this->testContractIdHex);
        $strKey = $address->toStrKey();

        $this->assertEquals($this->testContractIdStrKey, $strKey);
    }

    /**
     * Test setters and getters
     */
    public function testSettersAndGetters(): void
    {
        $address = new Address(Address::TYPE_ACCOUNT);

        // Test account ID
        $address->setAccountId($this->testAccountId);
        $this->assertEquals($this->testAccountId, $address->getAccountId());

        // Test contract ID
        $address->setType(Address::TYPE_CONTRACT);
        $address->setContractId($this->testContractIdHex);
        $this->assertEquals($this->testContractIdHex, $address->getContractId());

        // Test muxed account ID
        $address->setType(Address::TYPE_MUXED_ACCOUNT);
        $address->setMuxedAccountId($this->testMuxedAccountId);
        $this->assertEquals($this->testMuxedAccountId, $address->getMuxedAccountId());

        // Test claimable balance ID
        $address->setType(Address::TYPE_CLAIMABLE_BALANCE);
        $address->setClaimableBalanceId($this->testClaimableBalanceIdHex);
        $this->assertEquals($this->testClaimableBalanceIdHex, $address->getClaimableBalanceId());

        // Test liquidity pool ID
        $address->setType(Address::TYPE_LIQUIDITY_POOL);
        $address->setLiquidityPoolId($this->testLiquidityPoolIdHex);
        $this->assertEquals($this->testLiquidityPoolIdHex, $address->getLiquidityPoolId());
    }

    /**
     * Test toXdr throws exception when account ID is null
     */
    public function testToXdrThrowsExceptionForNullAccountId(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('accountId is null');

        $address = new Address(Address::TYPE_ACCOUNT);
        $address->toXdr();
    }

    /**
     * Test toXdr throws exception when contract ID is null
     */
    public function testToXdrThrowsExceptionForNullContractId(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('contractId is null');

        $address = new Address(Address::TYPE_CONTRACT);
        $address->toXdr();
    }

    /**
     * Test toXdr throws exception when muxed account ID is null
     */
    public function testToXdrThrowsExceptionForNullMuxedAccountId(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('muxedAccountId is null');

        $address = new Address(Address::TYPE_MUXED_ACCOUNT);
        $address->toXdr();
    }

    /**
     * Test toXdr throws exception when claimable balance ID is null
     */
    public function testToXdrThrowsExceptionForNullClaimableBalanceId(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('claimableBalanceId is null');

        $address = new Address(Address::TYPE_CLAIMABLE_BALANCE);
        $address->toXdr();
    }

    /**
     * Test toXdr throws exception when liquidity pool ID is null
     */
    public function testToXdrThrowsExceptionForNullLiquidityPoolId(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('liquidityPoolId is null');

        $address = new Address(Address::TYPE_LIQUIDITY_POOL);
        $address->toXdr();
    }

    /**
     * Test toXdr throws exception for unknown address type
     */
    public function testToXdrThrowsExceptionForUnknownType(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unknown address type');

        $address = new Address(999); // Invalid type
        $address->toXdr();
    }

    /**
     * Test fromXdrSCVal throws exception for non-address SCVal
     */
    public function testFromXdrSCValThrowsExceptionForNonAddress(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Given XdrSCVal is not of type address');

        $scVal = XdrSCVal::forU32(42);
        Address::fromXdrSCVal($scVal);
    }

    /**
     * Test Address type constants
     */
    public function testTypeConstants(): void
    {
        $this->assertEquals(0, Address::TYPE_ACCOUNT);
        $this->assertEquals(1, Address::TYPE_CONTRACT);
        $this->assertEquals(2, Address::TYPE_MUXED_ACCOUNT);
        $this->assertEquals(3, Address::TYPE_CLAIMABLE_BALANCE);
        $this->assertEquals(4, Address::TYPE_LIQUIDITY_POOL);
    }

    /**
     * Test multiple address instances are independent
     */
    public function testMultipleInstancesAreIndependent(): void
    {
        $address1 = Address::fromAccountId($this->testAccountId);
        $address2 = Address::fromContractId($this->testContractIdHex);

        $this->assertEquals(Address::TYPE_ACCOUNT, $address1->getType());
        $this->assertEquals(Address::TYPE_CONTRACT, $address2->getType());
        $this->assertEquals($this->testAccountId, $address1->getAccountId());
        $this->assertEquals($this->testContractIdHex, $address2->getContractId());
    }

    public function testFromXdrReportsOneIdForEitherConstructionOfTheXdr(): void
    {
        // An XdrSCAddress built from a strkey holds that spelling, while one decoded
        // from bytes holds the raw id. Both name one entity, so fromXdr must report
        // one spelling for both constructions of every id-carrying arm: the Horizon
        // 72-character form for a claimable balance, canonical hex for the rest.
        $hashHex = str_repeat('cd', 32);

        $inMemory = XdrSCAddress::forClaimableBalanceId(StrKey::encodeClaimableBalanceIdHex($hashHex));
        $decoded = XdrSCAddress::decode(new XdrBuffer($inMemory->encode()));
        $this->assertEquals('00000000' . $hashHex, Address::fromXdr($inMemory)->getClaimableBalanceId());
        $this->assertEquals('00000000' . $hashHex, Address::fromXdr($decoded)->getClaimableBalanceId());

        $inMemory = XdrSCAddress::forContractId(StrKey::encodeContractIdHex($hashHex));
        $decoded = XdrSCAddress::decode(new XdrBuffer($inMemory->encode()));
        $this->assertEquals($hashHex, Address::fromXdr($inMemory)->getContractId());
        $this->assertEquals($hashHex, Address::fromXdr($decoded)->getContractId());

        $inMemory = XdrSCAddress::forLiquidityPoolId(StrKey::encodeLiquidityPoolIdHex($hashHex));
        $decoded = XdrSCAddress::decode(new XdrBuffer($inMemory->encode()));
        $this->assertEquals($hashHex, Address::fromXdr($inMemory)->getLiquidityPoolId());
        $this->assertEquals($hashHex, Address::fromXdr($decoded)->getLiquidityPoolId());
    }

    public function testFromAnyIdReadsAStrkeyOfHexDigitsAsAStrkey(): void
    {
        // Every character of this valid B-strkey is a hexadecimal digit. A hex-first
        // reading would see a hex string of a width no id has and answer null; a
        // strkey names its own type, so it must resolve as one, reporting the
        // 72-character Horizon form.
        $strKey = 'BAAB5E6FEA37BFA5EFDD75E4D7ED75A74FBBF5FA5EBBE733DE5ECE34D4';
        $this->assertTrue(ctype_xdigit($strKey));

        $address = Address::fromAnyId($strKey);

        $this->assertNotNull($address);
        $this->assertEquals(Address::TYPE_CLAIMABLE_BALANCE, $address->getType());
        $this->assertEquals(
            '000000001e93c52037f0941d21463ff49c1fc83ff41fe14212f4a0e902127f7b193a4113',
            $address->getClaimableBalanceId()
        );
    }

    // deriveContractId

    public function testDeriveContractIdMatchesTheSharedCrossSdkVector(): void
    {
        // Shared vector pinned across all four Soneso SDKs; independently computed
        // (js-stellar-base and a hand-encoded preimage), never from this implementation.
        $deployer = Address::fromAccountId('GABAEAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEJXA');
        $salt = str_repeat("\x11", 32);

        $this->assertSame(
            'CADHEGA2PTPG6IJXYA62AY6JUQLQWOHRYX7A6FAZ4GL747OLBDQU7QXR',
            Address::deriveContractId($deployer, $salt, Network::testnet())
        );
        $this->assertSame(
            'CC22JLXJCLGHDQDBHJXTOEAFRM7GIS3D7CBQXR7M22HMWYNPUUPH6GKV',
            Address::deriveContractId($deployer, $salt, Network::public())
        );
    }

    public function testDeriveContractIdAcceptsAContractDeployer(): void
    {
        // Contracts deploy contracts too; the preimage takes any SC address. The
        // expected id was computed independently with js-stellar-base, covering the
        // contract arm of the address encoding that the account vector does not reach.
        $deployer = Address::fromContractId($this->testContractIdHex);
        $salt = str_repeat("\x11", 32);

        $this->assertSame(
            'CBU52TQFSCXOGX5OOE6QQKBRD3XNXAS2MRLI2XFKLGEODECDADE32QYU',
            Address::deriveContractId($deployer, $salt, Network::testnet())
        );
        $this->assertSame(
            'CAXJ2CGFFIGYDFF55V64NWWUASL7IMAZTVVECYOKOVQWF4KQGXP2HH3U',
            Address::deriveContractId($deployer, $salt, Network::public())
        );
    }

    public function testDeriveContractIdRejectsWrongSaltLength(): void
    {
        $deployer = Address::fromAccountId('GABAEAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEAQCAIBAEJXA');

        // 31 for the off-by-one, 64 for the likeliest real mistake: a hex string
        // where raw bytes are expected.
        foreach ([31, 64] as $length) {
            $threw = false;
            try {
                Address::deriveContractId($deployer, str_repeat("\x11", $length), Network::testnet());
            } catch (InvalidArgumentException $e) {
                $threw = true;
                $this->assertStringContainsString("must be exactly 32 bytes, got $length", $e->getMessage());
            }
            $this->assertTrue($threw, "expected an exception for salt length $length");
        }
    }
}
