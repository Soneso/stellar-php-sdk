<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrClaimableBalanceID;
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
        $muxedAccountId = (new MuxedAccount($keyPair->getAccountId(), 12345))->getAccountId();
        $this->assertTrue(StrKey::isValidMuxedAccountId($muxedAccountId));

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

    public function testContractAddressEncodeRejectsANonHexId(): void
    {
        $address = XdrSCAddress::forContractId('not a contract id');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Contract id must be a "C..." strkey or a hexadecimal string,'
            . ' "not a contract id" given'
        );
        $address->encode();
    }

    public function testContractAddressRejectsAHexIdOfStrkeyLength(): void
    {
        // 56 hexadecimal characters is also the length of a "C..." strkey, a plausible
        // truncation of the contract hash. The rejection has to name the hexadecimal
        // rule rather than report a strkey that failed to decode.
        $address = XdrSCAddress::forContractId(str_repeat('ab', 28));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Contract id must be 56 characters as a "C..." strkey, or the contract hash'
            . ' as 64 hexadecimal characters; 56 characters given'
        );
        $address->toStrKey();
    }

    public function testContractAddressRejectsAnUppercaseHexIdOfStrkeyLength(): void
    {
        // "C" is a hexadecimal digit, so 56 hexadecimal characters can also carry the
        // leading letter of a strkey. The rejection has to name the hexadecimal rule
        // rather than report a strkey that failed to decode.
        $address = XdrSCAddress::forContractId('C' . str_repeat('a', 55));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Contract id must be 56 characters as a "C..." strkey, or the contract hash'
            . ' as 64 hexadecimal characters; 56 characters given'
        );
        $address->toStrKey();
    }

    public function testContractAddressReadersAgreeOnEitherCaseOfTheId(): void
    {
        // Hexadecimal is case insensitive, so the two spellings denote one contract
        // and both readers of the field have to report it the same way.
        $upper = XdrSCAddress::forContractId(strtoupper(self::TEST_CONTRACT_ID_HEX));
        $lower = XdrSCAddress::forContractId(self::TEST_CONTRACT_ID_HEX);

        $this->assertEquals($lower->encode(), $upper->encode());
        $this->assertEquals($lower->toStrKey(), $upper->toStrKey());
        $this->assertEquals(
            self::TEST_CONTRACT_ID_HEX,
            StrKey::decodeContractIdHex($upper->toStrKey())
        );
    }

    // Muxed Account Address Tests

    public function testMuxedAccountAddressToStrKey(): void
    {
        $keyPair = KeyPair::random();
        $muxedAccountId = (new MuxedAccount($keyPair->getAccountId(), 12345))->getAccountId();

        $address = XdrSCAddress::forAccountId($muxedAccountId);
        $strKey = $address->toStrKey();

        $this->assertEquals($muxedAccountId, $strKey);
        $this->assertTrue(StrKey::isValidMuxedAccountId($strKey));
    }

    // Claimable Balance Address Tests

    public function testForClaimableBalanceId(): void
    {
        $address = XdrSCAddress::forClaimableBalanceId(self::TEST_CLAIMABLE_BALANCE_ID);

        $this->assertEquals(XdrSCAddressType::SC_ADDRESS_TYPE_CLAIMABLE_BALANCE, $address->getType()->getValue());
        $this->assertEquals(self::TEST_CLAIMABLE_BALANCE_ID, $address->getClaimableBalanceId()->getHash());
    }

    public function testClaimableBalanceAddressToStrKey(): void
    {
        $address = XdrSCAddress::forClaimableBalanceId(self::TEST_CLAIMABLE_BALANCE_ID);

        $strKey = $address->toStrKey();

        $this->assertStringStartsWith('B', $strKey);
        $this->assertTrue(StrKey::isValidClaimableBalanceId($strKey));
        // A B-strkey carries the 1-byte claimable balance type (0 for V0)
        // followed by the 32-byte balance hash.
        $decodedHex = StrKey::decodeClaimableBalanceIdHex($strKey);
        $this->assertEquals('00' . self::TEST_CLAIMABLE_BALANCE_ID, $decodedHex);
    }

    public function testClaimableBalanceAddressToStrKeyAcceptsThePaddedHexForm(): void
    {
        // A balance id arrives from Horizon, and leaves getPaddedBalanceIdHex(), with
        // the 4-byte XDR type discriminant ahead of the hash. encode() takes that
        // form, so toStrKey() answers for it as well.
        $bare = XdrSCAddress::forClaimableBalanceId(self::TEST_CLAIMABLE_BALANCE_ID);
        $padded = XdrSCAddress::forClaimableBalanceId('00000000' . self::TEST_CLAIMABLE_BALANCE_ID);

        $strKey = $padded->toStrKey();

        $this->assertEquals($bare->toStrKey(), $strKey);
        $this->assertTrue(StrKey::isValidClaimableBalanceId($strKey));
        $this->assertEquals('00' . self::TEST_CLAIMABLE_BALANCE_ID, StrKey::decodeClaimableBalanceIdHex($strKey));
        // Both spellings encode to the same XDR, so both must report the same strkey.
        $this->assertEquals($bare->encode(), $padded->encode());
    }

    public function testClaimableBalanceAddressRejectsAnUnknownHexForm(): void
    {
        $address = XdrSCAddress::forClaimableBalanceId('00' . self::TEST_CLAIMABLE_BALANCE_ID . 'ff');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Claimable balance id must be 58 characters as a "B..." strkey, or the balance'
            . ' hash as 64 hexadecimal characters, which a type discriminant may prefix to'
            . ' 66 or 72 characters; 68 characters given'
        );
        $address->toStrKey();
    }

    public function testClaimableBalanceAddressRejectsAHexIdOfStrkeyLength(): void
    {
        // 58 hexadecimal characters is also the length of a "B..." strkey, a plausible
        // truncation of the padded form. The rejection has to name the hexadecimal rule
        // rather than report a strkey that failed to decode.
        $address = XdrSCAddress::forClaimableBalanceId(str_repeat('ab', 29));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Claimable balance id must be 58 characters as a "B..." strkey, or the balance'
            . ' hash as 64 hexadecimal characters, which a type discriminant may prefix to'
            . ' 66 or 72 characters; 58 characters given'
        );
        $address->toStrKey();
    }

    public function testClaimableBalanceAddressRejectsAnUppercaseHexIdOfStrkeyLength(): void
    {
        // "B" is a hexadecimal digit, so 58 hexadecimal characters can also carry the
        // leading letter of a strkey. The rejection has to name the hexadecimal rule
        // rather than report a strkey that failed to decode.
        $address = XdrSCAddress::forClaimableBalanceId('B' . str_repeat('a', 57));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Claimable balance id must be 58 characters as a "B..." strkey, or the balance'
            . ' hash as 64 hexadecimal characters, which a type discriminant may prefix to'
            . ' 66 or 72 characters; 58 characters given'
        );
        $address->toStrKey();
    }

    public function testClaimableBalanceAddressReadsAStrkeyOfHexDigitsAsAStrkey(): void
    {
        // The base32 and hexadecimal alphabets share A-F and 2-7, so a "B..." strkey
        // can consist entirely of hexadecimal digits. It is still a strkey: 58
        // characters is not a length any of the hexadecimal spellings has.
        $strKey = 'BAAB5E6FEA37BFA5EFDD75E4D7ED75A74FBBF5FA5EBBE733DE5ECE34D4';
        $hashHex = '1e93c52037f0941d21463ff49c1fc83ff41fe14212f4a0e902127f7b193a4113';
        $this->assertTrue(ctype_xdigit($strKey));
        $this->assertTrue(StrKey::isValidClaimableBalanceId($strKey));

        $address = XdrSCAddress::forClaimableBalanceId($strKey);

        $this->assertEquals($strKey, $address->toStrKey());
        $this->assertEquals(
            '00000000' . $hashHex,
            $address->getClaimableBalanceId()->getPaddedBalanceIdHex()
        );
        $this->assertEquals(
            XdrSCAddress::forClaimableBalanceId($hashHex)->encode(),
            $address->encode()
        );
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

    public function testLiquidityPoolAddressRejectsAHexIdOfStrkeyLength(): void
    {
        // 56 hexadecimal characters is also the length of an "L..." strkey, a plausible
        // truncation of the pool hash. The rejection has to name the hexadecimal rule
        // rather than report a strkey that failed to decode.
        $address = XdrSCAddress::forLiquidityPoolId(str_repeat('ab', 28));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Liquidity pool id must be 56 characters as an "L..." strkey, or the pool hash'
            . ' as 64 hexadecimal characters; 56 characters given'
        );
        $address->toStrKey();
    }

    public function testLiquidityPoolAddressReadersAgreeOnEitherCaseOfTheId(): void
    {
        // Hexadecimal is case insensitive, so the two spellings denote one pool and
        // both readers of the field have to report it the same way.
        $upper = XdrSCAddress::forLiquidityPoolId(strtoupper(self::TEST_LIQUIDITY_POOL_ID));
        $lower = XdrSCAddress::forLiquidityPoolId(self::TEST_LIQUIDITY_POOL_ID);

        $this->assertEquals($lower->encode(), $upper->encode());
        $this->assertEquals($lower->toStrKey(), $upper->toStrKey());
        $this->assertEquals(
            self::TEST_LIQUIDITY_POOL_ID,
            StrKey::decodeLiquidityPoolIdHex($upper->toStrKey())
        );
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

        $this->assertEquals(self::TEST_CLAIMABLE_BALANCE_ID, $address->getClaimableBalanceId()->getHash());

        $newId = 'ff00ff00ff00ff00ff00ff00ff00ff00ff00ff00ff00ff00ff00ff00ff00ff00';
        $address->setClaimableBalanceId(XdrClaimableBalanceID::forClaimableBalanceId($newId));
        $this->assertEquals($newId, $address->getClaimableBalanceId()->getHash());
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
        $muxedAccountId = (new MuxedAccount($keyPair->getAccountId(), 12345))->getAccountId();
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

    public function testGetCanonicalContractIdHexRejectsAnUnsetId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Contract id is not set');
        XdrSCAddress::forContractId('')->getCanonicalContractIdHex();
    }

    /**
     * A 56-character value is read as a strkey, so a non-hex one is refused with the
     * strkey rejection rather than the spelling list.
     */
    public function testGetCanonicalContractIdHexReportsTheStrkeyRejectionForAStrkeyShapedNonHexId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('version byte in encoded data does not match');
        XdrSCAddress::forContractId('C' . str_repeat('Z', 55))->getCanonicalContractIdHex();
    }

    public function testGetCanonicalLiquidityPoolIdHexRejectsAnUnsetId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Liquidity pool id is not set');
        XdrSCAddress::forLiquidityPoolId('')->getCanonicalLiquidityPoolIdHex();
    }

    public function testGetCanonicalLiquidityPoolIdHexRejectsANonHexId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be an "L..." strkey or a hexadecimal string');
        XdrSCAddress::forLiquidityPoolId('not a pool id')->getCanonicalLiquidityPoolIdHex();
    }
}
