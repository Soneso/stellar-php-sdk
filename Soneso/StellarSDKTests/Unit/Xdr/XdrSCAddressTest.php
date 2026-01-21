<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrSCAddress;
use Soneso\StellarSDK\Xdr\XdrSCAddressType;

/**
 * Unit tests for XdrSCAddress
 *
 * Tests all address type variants: account, contract, muxed account,
 * claimable balance, and liquidity pool.
 */
class XdrSCAddressTest extends TestCase
{
    private const TEST_ACCOUNT_ID = 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H';
    private const TEST_CONTRACT_ID_HEX = 'e5c244f77f8e6b82f1a8d3e9b0c5a6d7f8e9a0b1c2d3e4f5a6b7c8d9e0f1a2b3';
    private const TEST_CLAIMABLE_BALANCE_ID = 'da0d57da7d4850e7fc10d2a9d0ebc731f7afb40574c03395b17d49149b91f5be';
    private const TEST_LIQUIDITY_POOL_ID = 'dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7';

    // Account Address Tests

    public function testForAccountIdWithGAddress(): void
    {
        $address = XdrSCAddress::forAccountId(self::TEST_ACCOUNT_ID);

        $this->assertEquals(XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT, $address->getType()->getValue());
        $this->assertNotNull($address->getAccountId());
        $this->assertEquals(self::TEST_ACCOUNT_ID, $address->getAccountId()->getAccountId());
    }

    public function testForAccountIdWithMuxedAddress(): void
    {
        // Generate a valid muxed account ID
        $keyPair = KeyPair::random();
        $accountId = $keyPair->getAccountId();
        $muxedAccountId = StrKey::encodeMuxedAccountId($accountId, 12345);

        $address = XdrSCAddress::forAccountId($muxedAccountId);

        $this->assertEquals(XdrSCAddressType::SC_ADDRESS_TYPE_MUXED_ACCOUNT, $address->getType()->getValue());
        $this->assertNotNull($address->getMuxedAccount());
    }

    public function testForAccountIdWithInvalidPrefix(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid account id');

        XdrSCAddress::forAccountId('INVALID_ACCOUNT_ID');
    }

    public function testAccountAddressEncodeDecodeRoundTrip(): void
    {
        $original = XdrSCAddress::forAccountId(self::TEST_ACCOUNT_ID);

        $encoded = $original->encode();
        $decoded = XdrSCAddress::decode(new XdrBuffer($encoded));

        $this->assertEquals($original->getType()->getValue(), $decoded->getType()->getValue());
        $this->assertEquals(
            $original->getAccountId()->getAccountId(),
            $decoded->getAccountId()->getAccountId()
        );
    }

    public function testAccountAddressToStrKey(): void
    {
        $address = XdrSCAddress::forAccountId(self::TEST_ACCOUNT_ID);

        $strKey = $address->toStrKey();

        $this->assertEquals(self::TEST_ACCOUNT_ID, $strKey);
    }

    // Contract Address Tests

    public function testForContractIdWithHex(): void
    {
        $address = XdrSCAddress::forContractId(self::TEST_CONTRACT_ID_HEX);

        $this->assertEquals(XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT, $address->getType()->getValue());
        $this->assertEquals(self::TEST_CONTRACT_ID_HEX, $address->getContractId());
    }

    public function testForContractIdWithStrKey(): void
    {
        $contractStrKey = StrKey::encodeContractIdHex(self::TEST_CONTRACT_ID_HEX);

        $address = XdrSCAddress::forContractId($contractStrKey);

        $this->assertEquals(XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT, $address->getType()->getValue());
        $this->assertEquals($contractStrKey, $address->getContractId());
    }

    public function testContractAddressEncodeDecodeRoundTrip(): void
    {
        $original = XdrSCAddress::forContractId(self::TEST_CONTRACT_ID_HEX);

        $encoded = $original->encode();
        $decoded = XdrSCAddress::decode(new XdrBuffer($encoded));

        $this->assertEquals($original->getType()->getValue(), $decoded->getType()->getValue());
        // Decoded contractId is always hex
        $this->assertEquals(self::TEST_CONTRACT_ID_HEX, $decoded->getContractId());
    }

    public function testContractAddressToStrKeyFromHex(): void
    {
        $address = XdrSCAddress::forContractId(self::TEST_CONTRACT_ID_HEX);

        $strKey = $address->toStrKey();

        $this->assertStringStartsWith('C', $strKey);
        // Should be able to decode back to hex
        $decodedHex = StrKey::decodeContractIdHex($strKey);
        $this->assertEquals(self::TEST_CONTRACT_ID_HEX, $decodedHex);
    }

    public function testContractAddressToStrKeyFromStrKey(): void
    {
        $contractStrKey = StrKey::encodeContractIdHex(self::TEST_CONTRACT_ID_HEX);
        $address = XdrSCAddress::forContractId($contractStrKey);

        $strKey = $address->toStrKey();

        $this->assertEquals($contractStrKey, $strKey);
    }

    // Muxed Account Address Tests

    public function testMuxedAccountAddressEncodeDecodeRoundTrip(): void
    {
        $keyPair = KeyPair::random();
        $accountId = $keyPair->getAccountId();
        $muxedAccountId = StrKey::encodeMuxedAccountId($accountId, 99999);

        $original = XdrSCAddress::forAccountId($muxedAccountId);

        $encoded = $original->encode();
        $decoded = XdrSCAddress::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCAddressType::SC_ADDRESS_TYPE_MUXED_ACCOUNT, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getMuxedAccount());
    }

    public function testMuxedAccountAddressToStrKey(): void
    {
        $keyPair = KeyPair::random();
        $accountId = $keyPair->getAccountId();
        $muxedAccountId = StrKey::encodeMuxedAccountId($accountId, 12345);

        $address = XdrSCAddress::forAccountId($muxedAccountId);
        $strKey = $address->toStrKey();

        $this->assertStringStartsWith('M', $strKey);
    }

    // Claimable Balance Address Tests

    public function testForClaimableBalanceId(): void
    {
        $address = XdrSCAddress::forClaimableBalanceId(self::TEST_CLAIMABLE_BALANCE_ID);

        $this->assertEquals(XdrSCAddressType::SC_ADDRESS_TYPE_CLAIMABLE_BALANCE, $address->getType()->getValue());
        $this->assertEquals(self::TEST_CLAIMABLE_BALANCE_ID, $address->getClaimableBalanceId());
    }

    public function testClaimableBalanceAddressEncodeDecodeRoundTrip(): void
    {
        $original = XdrSCAddress::forClaimableBalanceId(self::TEST_CLAIMABLE_BALANCE_ID);

        $encoded = $original->encode();
        $decoded = XdrSCAddress::decode(new XdrBuffer($encoded));

        $this->assertEquals($original->getType()->getValue(), $decoded->getType()->getValue());
        $this->assertEquals(self::TEST_CLAIMABLE_BALANCE_ID, $decoded->getClaimableBalanceId());
    }

    public function testClaimableBalanceAddressToStrKey(): void
    {
        // Note: Claimable balance IDs include a 4-byte type prefix (00000000 for V0)
        $fullClaimableBalanceId = '00000000' . self::TEST_CLAIMABLE_BALANCE_ID;
        $address = XdrSCAddress::forClaimableBalanceId($fullClaimableBalanceId);

        $strKey = $address->toStrKey();

        $this->assertStringStartsWith('B', $strKey);
        // Should be able to decode back
        $decodedHex = StrKey::decodeClaimableBalanceIdHex($strKey);
        $this->assertEquals($fullClaimableBalanceId, $decodedHex);
    }

    public function testClaimableBalanceAddressToStrKeyAlreadyEncoded(): void
    {
        $encodedId = StrKey::encodeClaimableBalanceIdHex(self::TEST_CLAIMABLE_BALANCE_ID);
        $address = XdrSCAddress::forClaimableBalanceId($encodedId);

        $strKey = $address->toStrKey();

        $this->assertEquals($encodedId, $strKey);
    }

    // Liquidity Pool Address Tests

    public function testForLiquidityPoolId(): void
    {
        $address = XdrSCAddress::forLiquidityPoolId(self::TEST_LIQUIDITY_POOL_ID);

        $this->assertEquals(XdrSCAddressType::SC_ADDRESS_TYPE_LIQUIDITY_POOL, $address->getType()->getValue());
        $this->assertEquals(self::TEST_LIQUIDITY_POOL_ID, $address->getLiquidityPoolId());
    }

    public function testLiquidityPoolAddressEncodeDecodeRoundTrip(): void
    {
        $original = XdrSCAddress::forLiquidityPoolId(self::TEST_LIQUIDITY_POOL_ID);

        $encoded = $original->encode();
        $decoded = XdrSCAddress::decode(new XdrBuffer($encoded));

        $this->assertEquals($original->getType()->getValue(), $decoded->getType()->getValue());
        $this->assertEquals(self::TEST_LIQUIDITY_POOL_ID, $decoded->getLiquidityPoolId());
    }

    public function testLiquidityPoolAddressToStrKey(): void
    {
        $address = XdrSCAddress::forLiquidityPoolId(self::TEST_LIQUIDITY_POOL_ID);

        $strKey = $address->toStrKey();

        $this->assertStringStartsWith('L', $strKey);
        // Should be able to decode back
        $decodedHex = StrKey::decodeLiquidityPoolIdHex($strKey);
        $this->assertEquals(self::TEST_LIQUIDITY_POOL_ID, $decodedHex);
    }

    public function testLiquidityPoolAddressToStrKeyAlreadyEncoded(): void
    {
        $encodedId = StrKey::encodeLiquidityPoolIdHex(self::TEST_LIQUIDITY_POOL_ID);
        $address = XdrSCAddress::forLiquidityPoolId($encodedId);

        $strKey = $address->toStrKey();

        $this->assertEquals($encodedId, $strKey);
    }

    public function testLiquidityPoolAddressEncodeWithStrKeyFormat(): void
    {
        $encodedId = StrKey::encodeLiquidityPoolIdHex(self::TEST_LIQUIDITY_POOL_ID);
        $original = XdrSCAddress::forLiquidityPoolId($encodedId);

        $encoded = $original->encode();
        $decoded = XdrSCAddress::decode(new XdrBuffer($encoded));

        $this->assertEquals($original->getType()->getValue(), $decoded->getType()->getValue());
        // After decode, it's hex format
        $this->assertEquals(self::TEST_LIQUIDITY_POOL_ID, $decoded->getLiquidityPoolId());
    }

    // Getter/Setter Tests

    public function testGetSetType(): void
    {
        $address = new XdrSCAddress(XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT());

        $this->assertEquals(XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT, $address->getType()->getValue());

        $address->setType(XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT());
        $this->assertEquals(XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT, $address->getType()->getValue());
    }

    public function testGetSetContractId(): void
    {
        $address = XdrSCAddress::forContractId(self::TEST_CONTRACT_ID_HEX);

        $this->assertEquals(self::TEST_CONTRACT_ID_HEX, $address->getContractId());

        $newContractId = 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2';
        $address->setContractId($newContractId);
        $this->assertEquals($newContractId, $address->getContractId());
    }

    public function testGetSetClaimableBalanceId(): void
    {
        $address = XdrSCAddress::forClaimableBalanceId(self::TEST_CLAIMABLE_BALANCE_ID);

        $this->assertEquals(self::TEST_CLAIMABLE_BALANCE_ID, $address->getClaimableBalanceId());

        $newId = 'ff00ff00ff00ff00ff00ff00ff00ff00ff00ff00ff00ff00ff00ff00ff00ff00';
        $address->setClaimableBalanceId($newId);
        $this->assertEquals($newId, $address->getClaimableBalanceId());
    }

    public function testGetSetLiquidityPoolId(): void
    {
        $address = XdrSCAddress::forLiquidityPoolId(self::TEST_LIQUIDITY_POOL_ID);

        $this->assertEquals(self::TEST_LIQUIDITY_POOL_ID, $address->getLiquidityPoolId());

        $newId = 'aa11bb22cc33dd44ee55ff66aa11bb22cc33dd44ee55ff66aa11bb22cc33dd44';
        $address->setLiquidityPoolId($newId);
        $this->assertEquals($newId, $address->getLiquidityPoolId());
    }

    public function testGetSetMuxedAccount(): void
    {
        $keyPair = KeyPair::random();
        $muxedAccountId = StrKey::encodeMuxedAccountId($keyPair->getAccountId(), 12345);
        $address = XdrSCAddress::forAccountId($muxedAccountId);

        $this->assertNotNull($address->getMuxedAccount());

        $address->setMuxedAccount(null);
        $this->assertNull($address->getMuxedAccount());
    }

    public function testGetSetAccountId(): void
    {
        $address = XdrSCAddress::forAccountId(self::TEST_ACCOUNT_ID);

        $this->assertNotNull($address->getAccountId());

        $address->setAccountId(null);
        $this->assertNull($address->getAccountId());
    }
}
