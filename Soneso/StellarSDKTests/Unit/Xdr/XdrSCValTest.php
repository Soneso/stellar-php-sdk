<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValType;
use Soneso\StellarSDK\Xdr\XdrSCAddress;
use Soneso\StellarSDK\Xdr\XdrSCAddressType;
use Soneso\StellarSDK\Xdr\XdrSCMapEntry;
use Soneso\StellarSDK\Xdr\XdrSCError;
use Soneso\StellarSDK\Xdr\XdrSCErrorType;
use Soneso\StellarSDK\Xdr\XdrSCErrorCode;
use Soneso\StellarSDK\Xdr\XdrInt128Parts;
use Soneso\StellarSDK\Xdr\XdrUInt128Parts;
use Soneso\StellarSDK\Xdr\XdrInt256Parts;
use Soneso\StellarSDK\Xdr\XdrUInt256Parts;
use Soneso\StellarSDK\Xdr\XdrSCNonceKey;
use Soneso\StellarSDK\Xdr\XdrSCContractInstance;
use Soneso\StellarSDK\Xdr\XdrContractExecutable;
use Soneso\StellarSDK\Xdr\XdrContractExecutableType;
use Soneso\StellarSDK\Xdr\XdrDataValueMandatory;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrAccountID;

class XdrSCValTest extends TestCase
{
    private const TEST_ACCOUNT_ID = "GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H";
    private const TEST_CONTRACT_ID = "CA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUWDA";

    /**
     * Test XdrSCValType encode/decode round-trip
     */
    public function testXdrSCValTypeRoundTrip(): void
    {
        $types = [
            XdrSCValType::SCV_BOOL,
            XdrSCValType::SCV_VOID,
            XdrSCValType::SCV_ERROR,
            XdrSCValType::SCV_U32,
            XdrSCValType::SCV_I32,
            XdrSCValType::SCV_U64,
            XdrSCValType::SCV_I64,
            XdrSCValType::SCV_TIMEPOINT,
            XdrSCValType::SCV_DURATION,
            XdrSCValType::SCV_U128,
            XdrSCValType::SCV_I128,
            XdrSCValType::SCV_U256,
            XdrSCValType::SCV_I256,
            XdrSCValType::SCV_BYTES,
            XdrSCValType::SCV_STRING,
            XdrSCValType::SCV_SYMBOL,
            XdrSCValType::SCV_VEC,
            XdrSCValType::SCV_MAP,
            XdrSCValType::SCV_ADDRESS,
            XdrSCValType::SCV_CONTRACT_INSTANCE,
            XdrSCValType::SCV_LEDGER_KEY_CONTRACT_INSTANCE,
            XdrSCValType::SCV_LEDGER_KEY_NONCE,
        ];

        foreach ($types as $typeValue) {
            $original = new XdrSCValType($typeValue);
            $encoded = $original->encode();
            $decoded = XdrSCValType::decode(new XdrBuffer($encoded));

            $this->assertEquals($original->getValue(), $decoded->getValue());
            $this->assertEquals($encoded, $decoded->encode());
        }
    }

    /**
     * Test XdrSCVal for boolean true
     */
    public function testXdrSCValBoolTrue(): void
    {
        $original = XdrSCVal::forTrue();

        $this->assertEquals(XdrSCValType::SCV_BOOL, $original->getType()->getValue());
        $this->assertTrue($original->getB());

        $encoded = $original->encode();
        $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCValType::SCV_BOOL, $decoded->getType()->getValue());
        $this->assertTrue($decoded->getB());
        $this->assertEquals($encoded, $decoded->encode());
    }

    /**
     * Test XdrSCVal for boolean false
     */
    public function testXdrSCValBoolFalse(): void
    {
        $original = XdrSCVal::forFalse();

        $this->assertEquals(XdrSCValType::SCV_BOOL, $original->getType()->getValue());
        $this->assertFalse($original->getB());

        $encoded = $original->encode();
        $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCValType::SCV_BOOL, $decoded->getType()->getValue());
        $this->assertFalse($decoded->getB());
        $this->assertEquals($encoded, $decoded->encode());
    }

    /**
     * Test XdrSCVal for boolean with forBool
     */
    public function testXdrSCValForBool(): void
    {
        $trueVal = XdrSCVal::forBool(true);
        $falseVal = XdrSCVal::forBool(false);

        $this->assertTrue($trueVal->getB());
        $this->assertFalse($falseVal->getB());

        $encodedTrue = $trueVal->encode();
        $decodedTrue = XdrSCVal::decode(new XdrBuffer($encodedTrue));
        $this->assertTrue($decodedTrue->getB());

        $encodedFalse = $falseVal->encode();
        $decodedFalse = XdrSCVal::decode(new XdrBuffer($encodedFalse));
        $this->assertFalse($decodedFalse->getB());
    }

    /**
     * Test XdrSCVal for void
     */
    public function testXdrSCValVoid(): void
    {
        $original = XdrSCVal::forVoid();

        $this->assertEquals(XdrSCValType::SCV_VOID, $original->getType()->getValue());

        $encoded = $original->encode();
        $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCValType::SCV_VOID, $decoded->getType()->getValue());
        $this->assertEquals($encoded, $decoded->encode());
    }

    /**
     * Test XdrSCVal for error with contract code
     */
    public function testXdrSCValErrorContract(): void
    {
        $error = new XdrSCError(XdrSCErrorType::SCE_CONTRACT());
        $error->setContractCode(42);

        $original = XdrSCVal::forError($error);

        $this->assertEquals(XdrSCValType::SCV_ERROR, $original->getType()->getValue());
        $this->assertEquals(42, $original->getError()->getContractCode());

        $encoded = $original->encode();
        $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCValType::SCV_ERROR, $decoded->getType()->getValue());
        $this->assertEquals(XdrSCErrorType::SCE_CONTRACT, $decoded->getError()->getType()->getValue());
        $this->assertEquals(42, $decoded->getError()->getContractCode());
        $this->assertEquals($encoded, $decoded->encode());
    }

    /**
     * Test XdrSCVal for error with auth code
     */
    public function testXdrSCValErrorAuth(): void
    {
        $error = new XdrSCError(XdrSCErrorType::SCE_AUTH());
        $error->setCode(XdrSCErrorCode::SCEC_INVALID_INPUT());

        $original = XdrSCVal::forError($error);

        $this->assertEquals(XdrSCValType::SCV_ERROR, $original->getType()->getValue());

        $encoded = $original->encode();
        $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCValType::SCV_ERROR, $decoded->getType()->getValue());
        $this->assertEquals(XdrSCErrorType::SCE_AUTH, $decoded->getError()->getType()->getValue());
        $this->assertEquals(XdrSCErrorCode::SCEC_INVALID_INPUT, $decoded->getError()->getCode()->getValue());
        $this->assertEquals($encoded, $decoded->encode());
    }

    /**
     * Test XdrSCVal for error types without additional data
     */
    public function testXdrSCValErrorSimpleTypes(): void
    {
        $errorTypes = [
            XdrSCErrorType::SCE_WASM_VM(),
            XdrSCErrorType::SCE_CONTEXT(),
            XdrSCErrorType::SCE_STORAGE(),
            XdrSCErrorType::SCE_OBJECT(),
            XdrSCErrorType::SCE_CRYPTO(),
            XdrSCErrorType::SCE_EVENTS(),
            XdrSCErrorType::SCE_BUDGET(),
            XdrSCErrorType::SCE_VALUE(),
        ];

        foreach ($errorTypes as $errorType) {
            $error = new XdrSCError($errorType);
            $original = XdrSCVal::forError($error);

            $encoded = $original->encode();
            $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

            $this->assertEquals($errorType->getValue(), $decoded->getError()->getType()->getValue());
            $this->assertEquals($encoded, $decoded->encode());
        }
    }

    /**
     * Test XdrSCVal for u32
     */
    public function testXdrSCValU32(): void
    {
        $testValues = [0, 1, 100, 4294967295];

        foreach ($testValues as $value) {
            $original = XdrSCVal::forU32($value);

            $this->assertEquals(XdrSCValType::SCV_U32, $original->getType()->getValue());
            $this->assertEquals($value, $original->getU32());

            $encoded = $original->encode();
            $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

            $this->assertEquals(XdrSCValType::SCV_U32, $decoded->getType()->getValue());
            $this->assertEquals($value, $decoded->getU32());
            $this->assertEquals($encoded, $decoded->encode());
        }
    }

    /**
     * Test XdrSCVal for i32
     */
    public function testXdrSCValI32(): void
    {
        $testValues = [-2147483648, -1, 0, 1, 2147483647];

        foreach ($testValues as $value) {
            $original = XdrSCVal::forI32($value);

            $this->assertEquals(XdrSCValType::SCV_I32, $original->getType()->getValue());
            $this->assertEquals($value, $original->getI32());

            $encoded = $original->encode();
            $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

            $this->assertEquals(XdrSCValType::SCV_I32, $decoded->getType()->getValue());
            $this->assertEquals($value, $decoded->getI32());
            $this->assertEquals($encoded, $decoded->encode());
        }
    }

    /**
     * Test XdrSCVal for u64
     */
    public function testXdrSCValU64(): void
    {
        $testValues = [0, 1, 1000, 9223372036854775807];

        foreach ($testValues as $value) {
            $original = XdrSCVal::forU64($value);

            $this->assertEquals(XdrSCValType::SCV_U64, $original->getType()->getValue());
            $this->assertEquals($value, $original->getU64());

            $encoded = $original->encode();
            $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

            $this->assertEquals(XdrSCValType::SCV_U64, $decoded->getType()->getValue());
            $this->assertEquals($value, $decoded->getU64());
            $this->assertEquals($encoded, $decoded->encode());
        }
    }

    /**
     * Test XdrSCVal for i64
     */
    public function testXdrSCValI64(): void
    {
        $testValues = [PHP_INT_MIN, -1000, 0, 1000, PHP_INT_MAX];

        foreach ($testValues as $value) {
            $original = XdrSCVal::forI64($value);

            $this->assertEquals(XdrSCValType::SCV_I64, $original->getType()->getValue());
            $this->assertEquals($value, $original->getI64());

            $encoded = $original->encode();
            $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

            $this->assertEquals(XdrSCValType::SCV_I64, $decoded->getType()->getValue());
            $this->assertEquals($value, $decoded->getI64());
            $this->assertEquals($encoded, $decoded->encode());
        }
    }

    /**
     * Test XdrSCVal for timepoint
     */
    public function testXdrSCValTimepoint(): void
    {
        $testValues = [0, 1609459200, 1735689600, 9223372036854775807];

        foreach ($testValues as $value) {
            $original = XdrSCVal::forTimepoint($value);

            $this->assertEquals(XdrSCValType::SCV_TIMEPOINT, $original->getType()->getValue());
            $this->assertEquals($value, $original->getTimepoint());

            $encoded = $original->encode();
            $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

            $this->assertEquals(XdrSCValType::SCV_TIMEPOINT, $decoded->getType()->getValue());
            $this->assertEquals($value, $decoded->getTimepoint());
            $this->assertEquals($encoded, $decoded->encode());
        }
    }

    /**
     * Test XdrSCVal for duration
     */
    public function testXdrSCValDuration(): void
    {
        $testValues = [0, 60, 3600, 86400, 9223372036854775807];

        foreach ($testValues as $value) {
            $original = XdrSCVal::forDuration($value);

            $this->assertEquals(XdrSCValType::SCV_DURATION, $original->getType()->getValue());
            $this->assertEquals($value, $original->getDuration());

            $encoded = $original->encode();
            $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

            $this->assertEquals(XdrSCValType::SCV_DURATION, $decoded->getType()->getValue());
            $this->assertEquals($value, $decoded->getDuration());
            $this->assertEquals($encoded, $decoded->encode());
        }
    }

    /**
     * Test XdrSCVal for u128
     */
    public function testXdrSCValU128(): void
    {
        $parts = new XdrUInt128Parts(123456789, 987654321);
        $original = XdrSCVal::forU128($parts);

        $this->assertEquals(XdrSCValType::SCV_U128, $original->getType()->getValue());
        $this->assertEquals(123456789, $original->getU128()->getHi());
        $this->assertEquals(987654321, $original->getU128()->getLo());

        $encoded = $original->encode();
        $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCValType::SCV_U128, $decoded->getType()->getValue());
        $this->assertEquals(123456789, $decoded->getU128()->getHi());
        $this->assertEquals(987654321, $decoded->getU128()->getLo());
        $this->assertEquals($encoded, $decoded->encode());
    }

    /**
     * Test XdrSCVal for u128 with forU128Parts
     */
    public function testXdrSCValU128Parts(): void
    {
        $original = XdrSCVal::forU128Parts(999999, 111111);

        $this->assertEquals(XdrSCValType::SCV_U128, $original->getType()->getValue());
        $this->assertEquals(999999, $original->getU128()->getHi());
        $this->assertEquals(111111, $original->getU128()->getLo());

        $encoded = $original->encode();
        $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCValType::SCV_U128, $decoded->getType()->getValue());
        $this->assertEquals(999999, $decoded->getU128()->getHi());
        $this->assertEquals(111111, $decoded->getU128()->getLo());
        $this->assertEquals($encoded, $decoded->encode());
    }

    /**
     * Test XdrSCVal for i128
     */
    public function testXdrSCValI128(): void
    {
        $parts = new XdrInt128Parts(-123456789, 987654321);
        $original = XdrSCVal::forI128($parts);

        $this->assertEquals(XdrSCValType::SCV_I128, $original->getType()->getValue());
        $this->assertEquals(-123456789, $original->getI128()->getHi());
        $this->assertEquals(987654321, $original->getI128()->getLo());

        $encoded = $original->encode();
        $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCValType::SCV_I128, $decoded->getType()->getValue());
        $this->assertEquals(-123456789, $decoded->getI128()->getHi());
        $this->assertEquals(987654321, $decoded->getI128()->getLo());
        $this->assertEquals($encoded, $decoded->encode());
    }

    /**
     * Test XdrSCVal for i128 with forI128Parts
     */
    public function testXdrSCValI128Parts(): void
    {
        $original = XdrSCVal::forI128Parts(-999999, 111111);

        $this->assertEquals(XdrSCValType::SCV_I128, $original->getType()->getValue());
        $this->assertEquals(-999999, $original->getI128()->getHi());
        $this->assertEquals(111111, $original->getI128()->getLo());

        $encoded = $original->encode();
        $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCValType::SCV_I128, $decoded->getType()->getValue());
        $this->assertEquals(-999999, $decoded->getI128()->getHi());
        $this->assertEquals(111111, $decoded->getI128()->getLo());
        $this->assertEquals($encoded, $decoded->encode());
    }

    /**
     * Test XdrSCVal for u256
     */
    public function testXdrSCValU256(): void
    {
        $parts = new XdrUInt256Parts(11111, 22222, 33333, 44444);
        $original = XdrSCVal::forU256($parts);

        $this->assertEquals(XdrSCValType::SCV_U256, $original->getType()->getValue());
        $this->assertEquals(11111, $original->getU256()->getHiHi());
        $this->assertEquals(22222, $original->getU256()->getHiLo());
        $this->assertEquals(33333, $original->getU256()->getLoHi());
        $this->assertEquals(44444, $original->getU256()->getLoLo());

        $encoded = $original->encode();
        $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCValType::SCV_U256, $decoded->getType()->getValue());
        $this->assertEquals(11111, $decoded->getU256()->getHiHi());
        $this->assertEquals(22222, $decoded->getU256()->getHiLo());
        $this->assertEquals(33333, $decoded->getU256()->getLoHi());
        $this->assertEquals(44444, $decoded->getU256()->getLoLo());
        $this->assertEquals($encoded, $decoded->encode());
    }

    /**
     * Test XdrSCVal for i256
     */
    public function testXdrSCValI256(): void
    {
        $parts = new XdrInt256Parts(-11111, 22222, 33333, 44444);
        $original = XdrSCVal::forI256($parts);

        $this->assertEquals(XdrSCValType::SCV_I256, $original->getType()->getValue());
        $this->assertEquals(-11111, $original->getI256()->getHiHi());
        $this->assertEquals(22222, $original->getI256()->getHiLo());
        $this->assertEquals(33333, $original->getI256()->getLoHi());
        $this->assertEquals(44444, $original->getI256()->getLoLo());

        $encoded = $original->encode();
        $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCValType::SCV_I256, $decoded->getType()->getValue());
        $this->assertEquals(-11111, $decoded->getI256()->getHiHi());
        $this->assertEquals(22222, $decoded->getI256()->getHiLo());
        $this->assertEquals(33333, $decoded->getI256()->getLoHi());
        $this->assertEquals(44444, $decoded->getI256()->getLoLo());
        $this->assertEquals($encoded, $decoded->encode());
    }

    /**
     * Test XdrSCVal for u128 BigInt
     */
    public function testXdrSCValU128BigInt(): void
    {
        $testValues = [
            '0',
            '1',
            '340282366920938463463374607431768211455',
        ];

        foreach ($testValues as $value) {
            $original = XdrSCVal::forU128BigInt($value);

            $this->assertEquals(XdrSCValType::SCV_U128, $original->getType()->getValue());

            $encoded = $original->encode();
            $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

            $this->assertEquals(XdrSCValType::SCV_U128, $decoded->getType()->getValue());

            $bigInt = $decoded->toBigInt();
            $this->assertEquals($value, gmp_strval($bigInt));
            $this->assertEquals($encoded, $decoded->encode());
        }
    }

    /**
     * Test XdrSCVal for i128 BigInt
     */
    public function testXdrSCValI128BigInt(): void
    {
        $testValues = [
            '-170141183460469231731687303715884105728',
            '-1',
            '0',
            '1',
            '170141183460469231731687303715884105727',
        ];

        foreach ($testValues as $value) {
            $original = XdrSCVal::forI128BigInt($value);

            $this->assertEquals(XdrSCValType::SCV_I128, $original->getType()->getValue());

            $encoded = $original->encode();
            $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

            $this->assertEquals(XdrSCValType::SCV_I128, $decoded->getType()->getValue());

            $bigInt = $decoded->toBigInt();
            $this->assertEquals($value, gmp_strval($bigInt));
            $this->assertEquals($encoded, $decoded->encode());
        }
    }

    /**
     * Test XdrSCVal for u256 BigInt
     */
    public function testXdrSCValU256BigInt(): void
    {
        $testValues = [
            '0',
            '1',
            '115792089237316195423570985008687907853269984665640564039457584007913129639935',
        ];

        foreach ($testValues as $value) {
            $original = XdrSCVal::forU256BigInt($value);

            $this->assertEquals(XdrSCValType::SCV_U256, $original->getType()->getValue());

            $encoded = $original->encode();
            $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

            $this->assertEquals(XdrSCValType::SCV_U256, $decoded->getType()->getValue());

            $bigInt = $decoded->toBigInt();
            $this->assertEquals($value, gmp_strval($bigInt));
            $this->assertEquals($encoded, $decoded->encode());
        }
    }

    /**
     * Test XdrSCVal for i256 BigInt
     */
    public function testXdrSCValI256BigInt(): void
    {
        $testValues = [
            '-57896044618658097711785492504343953926634992332820282019728792003956564819968',
            '-1',
            '0',
            '1',
            '57896044618658097711785492504343953926634992332820282019728792003956564819967',
        ];

        foreach ($testValues as $value) {
            $original = XdrSCVal::forI256BigInt($value);

            $this->assertEquals(XdrSCValType::SCV_I256, $original->getType()->getValue());

            $encoded = $original->encode();
            $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

            $this->assertEquals(XdrSCValType::SCV_I256, $decoded->getType()->getValue());

            $bigInt = $decoded->toBigInt();
            $this->assertEquals($value, gmp_strval($bigInt));
            $this->assertEquals($encoded, $decoded->encode());
        }
    }

    /**
     * Test XdrSCVal for bytes
     */
    public function testXdrSCValBytes(): void
    {
        $testValues = [
            "\x00",
            "\x01\x02\x03",
            "hello world",
            str_repeat("\xFF", 32),
        ];

        foreach ($testValues as $value) {
            $original = XdrSCVal::forBytes($value);

            $this->assertEquals(XdrSCValType::SCV_BYTES, $original->getType()->getValue());
            $this->assertEquals($value, $original->getBytes()->getValue());

            $encoded = $original->encode();
            $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

            $this->assertEquals(XdrSCValType::SCV_BYTES, $decoded->getType()->getValue());
            $this->assertEquals($value, $decoded->getBytes()->getValue());
            $this->assertEquals($encoded, $decoded->encode());
        }
    }

    /**
     * Test XdrSCVal for string
     */
    public function testXdrSCValString(): void
    {
        $testValues = [
            "",
            "hello",
            "Hello World",
            "Test String 123",
            "Unicode test: 你好",
        ];

        foreach ($testValues as $value) {
            $original = XdrSCVal::forString($value);

            $this->assertEquals(XdrSCValType::SCV_STRING, $original->getType()->getValue());
            $this->assertEquals($value, $original->getStr());

            $encoded = $original->encode();
            $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

            $this->assertEquals(XdrSCValType::SCV_STRING, $decoded->getType()->getValue());
            $this->assertEquals($value, $decoded->getStr());
            $this->assertEquals($encoded, $decoded->encode());
        }
    }

    /**
     * Test XdrSCVal for symbol
     */
    public function testXdrSCValSymbol(): void
    {
        $testValues = [
            "approve",
            "transfer",
            "mint",
            "burn",
            "balance",
        ];

        foreach ($testValues as $value) {
            $original = XdrSCVal::forSymbol($value);

            $this->assertEquals(XdrSCValType::SCV_SYMBOL, $original->getType()->getValue());
            $this->assertEquals($value, $original->getSym());

            $encoded = $original->encode();
            $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

            $this->assertEquals(XdrSCValType::SCV_SYMBOL, $decoded->getType()->getValue());
            $this->assertEquals($value, $decoded->getSym());
            $this->assertEquals($encoded, $decoded->encode());
        }
    }

    /**
     * Test XdrSCVal for empty vec
     */
    public function testXdrSCValVecEmpty(): void
    {
        $original = XdrSCVal::forVec([]);

        $this->assertEquals(XdrSCValType::SCV_VEC, $original->getType()->getValue());
        $this->assertEmpty($original->getVec());

        $encoded = $original->encode();
        $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCValType::SCV_VEC, $decoded->getType()->getValue());
        $this->assertEmpty($decoded->getVec());
        $this->assertEquals($encoded, $decoded->encode());
    }

    /**
     * Test XdrSCVal for vec with single element
     */
    public function testXdrSCValVecSingleElement(): void
    {
        $vec = [XdrSCVal::forU32(42)];
        $original = XdrSCVal::forVec($vec);

        $this->assertEquals(XdrSCValType::SCV_VEC, $original->getType()->getValue());
        $this->assertCount(1, $original->getVec());

        $encoded = $original->encode();
        $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCValType::SCV_VEC, $decoded->getType()->getValue());
        $this->assertCount(1, $decoded->getVec());
        $this->assertEquals(42, $decoded->getVec()[0]->getU32());
        $this->assertEquals($encoded, $decoded->encode());
    }

    /**
     * Test XdrSCVal for vec with multiple elements
     */
    public function testXdrSCValVecMultipleElements(): void
    {
        $vec = [
            XdrSCVal::forU32(1),
            XdrSCVal::forU32(2),
            XdrSCVal::forU32(3),
            XdrSCVal::forString("test"),
            XdrSCVal::forBool(true),
        ];
        $original = XdrSCVal::forVec($vec);

        $this->assertEquals(XdrSCValType::SCV_VEC, $original->getType()->getValue());
        $this->assertCount(5, $original->getVec());

        $encoded = $original->encode();
        $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCValType::SCV_VEC, $decoded->getType()->getValue());
        $this->assertCount(5, $decoded->getVec());
        $this->assertEquals(1, $decoded->getVec()[0]->getU32());
        $this->assertEquals(2, $decoded->getVec()[1]->getU32());
        $this->assertEquals(3, $decoded->getVec()[2]->getU32());
        $this->assertEquals("test", $decoded->getVec()[3]->getStr());
        $this->assertTrue($decoded->getVec()[4]->getB());
        $this->assertEquals($encoded, $decoded->encode());
    }

    /**
     * Test XdrSCVal for nested vec
     */
    public function testXdrSCValVecNested(): void
    {
        $innerVec = [XdrSCVal::forU32(10), XdrSCVal::forU32(20)];
        $outerVec = [
            XdrSCVal::forVec($innerVec),
            XdrSCVal::forString("outer"),
        ];
        $original = XdrSCVal::forVec($outerVec);

        $encoded = $original->encode();
        $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCValType::SCV_VEC, $decoded->getType()->getValue());
        $this->assertCount(2, $decoded->getVec());
        $this->assertEquals(XdrSCValType::SCV_VEC, $decoded->getVec()[0]->getType()->getValue());
        $this->assertCount(2, $decoded->getVec()[0]->getVec());
        $this->assertEquals(10, $decoded->getVec()[0]->getVec()[0]->getU32());
        $this->assertEquals(20, $decoded->getVec()[0]->getVec()[1]->getU32());
        $this->assertEquals("outer", $decoded->getVec()[1]->getStr());
        $this->assertEquals($encoded, $decoded->encode());
    }

    /**
     * Test XdrSCVal for empty map
     */
    public function testXdrSCValMapEmpty(): void
    {
        $original = XdrSCVal::forMap([]);

        $this->assertEquals(XdrSCValType::SCV_MAP, $original->getType()->getValue());
        $this->assertEmpty($original->getMap());

        $encoded = $original->encode();
        $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCValType::SCV_MAP, $decoded->getType()->getValue());
        $this->assertEmpty($decoded->getMap());
        $this->assertEquals($encoded, $decoded->encode());
    }

    /**
     * Test XdrSCVal for map with single entry
     */
    public function testXdrSCValMapSingleEntry(): void
    {
        $entry = new XdrSCMapEntry(
            XdrSCVal::forSymbol("key"),
            XdrSCVal::forU32(42)
        );
        $original = XdrSCVal::forMap([$entry]);

        $this->assertEquals(XdrSCValType::SCV_MAP, $original->getType()->getValue());
        $this->assertCount(1, $original->getMap());

        $encoded = $original->encode();
        $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCValType::SCV_MAP, $decoded->getType()->getValue());
        $this->assertCount(1, $decoded->getMap());
        $this->assertEquals("key", $decoded->getMap()[0]->getKey()->getSym());
        $this->assertEquals(42, $decoded->getMap()[0]->getVal()->getU32());
        $this->assertEquals($encoded, $decoded->encode());
    }

    /**
     * Test XdrSCVal for map with multiple entries
     */
    public function testXdrSCValMapMultipleEntries(): void
    {
        $entries = [
            new XdrSCMapEntry(XdrSCVal::forSymbol("key1"), XdrSCVal::forU32(1)),
            new XdrSCMapEntry(XdrSCVal::forSymbol("key2"), XdrSCVal::forU32(2)),
            new XdrSCMapEntry(XdrSCVal::forSymbol("key3"), XdrSCVal::forString("value3")),
        ];
        $original = XdrSCVal::forMap($entries);

        $this->assertEquals(XdrSCValType::SCV_MAP, $original->getType()->getValue());
        $this->assertCount(3, $original->getMap());

        $encoded = $original->encode();
        $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCValType::SCV_MAP, $decoded->getType()->getValue());
        $this->assertCount(3, $decoded->getMap());
        $this->assertEquals("key1", $decoded->getMap()[0]->getKey()->getSym());
        $this->assertEquals(1, $decoded->getMap()[0]->getVal()->getU32());
        $this->assertEquals("key2", $decoded->getMap()[1]->getKey()->getSym());
        $this->assertEquals(2, $decoded->getMap()[1]->getVal()->getU32());
        $this->assertEquals("key3", $decoded->getMap()[2]->getKey()->getSym());
        $this->assertEquals("value3", $decoded->getMap()[2]->getVal()->getStr());
        $this->assertEquals($encoded, $decoded->encode());
    }

    /**
     * Test XdrSCVal for nested map
     */
    public function testXdrSCValMapNested(): void
    {
        $innerEntry = new XdrSCMapEntry(
            XdrSCVal::forSymbol("inner_key"),
            XdrSCVal::forU32(100)
        );
        $innerMap = XdrSCVal::forMap([$innerEntry]);

        $outerEntry = new XdrSCMapEntry(
            XdrSCVal::forSymbol("outer_key"),
            $innerMap
        );
        $original = XdrSCVal::forMap([$outerEntry]);

        $encoded = $original->encode();
        $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCValType::SCV_MAP, $decoded->getType()->getValue());
        $this->assertCount(1, $decoded->getMap());
        $this->assertEquals("outer_key", $decoded->getMap()[0]->getKey()->getSym());
        $this->assertEquals(XdrSCValType::SCV_MAP, $decoded->getMap()[0]->getVal()->getType()->getValue());
        $this->assertEquals("inner_key", $decoded->getMap()[0]->getVal()->getMap()[0]->getKey()->getSym());
        $this->assertEquals(100, $decoded->getMap()[0]->getVal()->getMap()[0]->getVal()->getU32());
        $this->assertEquals($encoded, $decoded->encode());
    }

    /**
     * Test XdrSCVal for address with account
     */
    public function testXdrSCValAddressAccount(): void
    {
        $address = XdrSCAddress::forAccountId(self::TEST_ACCOUNT_ID);
        $original = XdrSCVal::forAddress($address);

        $this->assertEquals(XdrSCValType::SCV_ADDRESS, $original->getType()->getValue());
        $this->assertEquals(XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT, $original->getAddress()->getType()->getValue());

        $encoded = $original->encode();
        $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCValType::SCV_ADDRESS, $decoded->getType()->getValue());
        $this->assertEquals(XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT, $decoded->getAddress()->getType()->getValue());
        $this->assertEquals(self::TEST_ACCOUNT_ID, $decoded->getAddress()->getAccountId()->getAccountId());
        $this->assertEquals($encoded, $decoded->encode());
    }

    /**
     * Test XdrSCVal for address with contract
     */
    public function testXdrSCValAddressContract(): void
    {
        $address = XdrSCAddress::forContractId(self::TEST_CONTRACT_ID);
        $original = XdrSCVal::forAddress($address);

        $this->assertEquals(XdrSCValType::SCV_ADDRESS, $original->getType()->getValue());
        $this->assertEquals(XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT, $original->getAddress()->getType()->getValue());

        $encoded = $original->encode();
        $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCValType::SCV_ADDRESS, $decoded->getType()->getValue());
        $this->assertEquals(XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT, $decoded->getAddress()->getType()->getValue());
        $this->assertEquals($encoded, $decoded->encode());
    }

    /**
     * Test XdrSCVal for ledger key contract instance
     */
    public function testXdrSCValLedgerKeyContractInstance(): void
    {
        $original = XdrSCVal::forLedgerKeyContractInstance();

        $this->assertEquals(XdrSCValType::SCV_LEDGER_KEY_CONTRACT_INSTANCE, $original->getType()->getValue());

        $encoded = $original->encode();
        $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCValType::SCV_LEDGER_KEY_CONTRACT_INSTANCE, $decoded->getType()->getValue());
        $this->assertEquals($encoded, $decoded->encode());
    }

    /**
     * Test XdrSCVal for ledger nonce key
     */
    public function testXdrSCValLedgerNonceKey(): void
    {
        $nonceKey = new XdrSCNonceKey(12345678);
        $original = XdrSCVal::forLedgerNonceKey($nonceKey);

        $this->assertEquals(XdrSCValType::SCV_LEDGER_KEY_NONCE, $original->getType()->getValue());
        $this->assertEquals(12345678, $original->getNonceKey()->getNonce());

        $encoded = $original->encode();
        $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCValType::SCV_LEDGER_KEY_NONCE, $decoded->getType()->getValue());
        $this->assertEquals(12345678, $decoded->getNonceKey()->getNonce());
        $this->assertEquals($encoded, $decoded->encode());
    }

    /**
     * Test XdrSCVal for contract instance
     */
    public function testXdrSCValContractInstance(): void
    {
        $executable = new XdrContractExecutable(XdrContractExecutableType::CONTRACT_EXECUTABLE_WASM());
        $executable->setWasmIdHex(str_repeat("01", 32));

        $storage = [
            new XdrSCMapEntry(
                XdrSCVal::forSymbol("storage_key"),
                XdrSCVal::forU32(999)
            )
        ];

        $instance = new XdrSCContractInstance($executable, $storage);
        $original = XdrSCVal::forContractInstance($instance);

        $this->assertEquals(XdrSCValType::SCV_CONTRACT_INSTANCE, $original->getType()->getValue());

        $encoded = $original->encode();
        $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCValType::SCV_CONTRACT_INSTANCE, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getInstance());
        $this->assertCount(1, $decoded->getInstance()->getStorage());
        $this->assertEquals("storage_key", $decoded->getInstance()->getStorage()[0]->getKey()->getSym());
        $this->assertEquals(999, $decoded->getInstance()->getStorage()[0]->getVal()->getU32());
        $this->assertEquals($encoded, $decoded->encode());
    }

    /**
     * Test XdrSCVal base64 encoding/decoding
     */
    public function testXdrSCValBase64RoundTrip(): void
    {
        $original = XdrSCVal::forU32(42);

        $base64 = $original->toBase64Xdr();
        $this->assertNotEmpty($base64);

        $decoded = XdrSCVal::fromBase64Xdr($base64);

        $this->assertEquals(XdrSCValType::SCV_U32, $decoded->getType()->getValue());
        $this->assertEquals(42, $decoded->getU32());
        $this->assertEquals($base64, $decoded->toBase64Xdr());
    }

    /**
     * Test XdrSCVal forContractId
     */
    public function testXdrSCValForContractId(): void
    {
        $contractIdHex = "0000000000000000000000000000000000000000000000000000000000000001";
        $original = XdrSCVal::forContractId($contractIdHex);

        $this->assertEquals(XdrSCValType::SCV_BYTES, $original->getType()->getValue());
        $this->assertNotNull($original->getBytes());

        $encoded = $original->encode();
        $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCValType::SCV_BYTES, $decoded->getType()->getValue());
        $this->assertEquals($encoded, $decoded->encode());
    }

    /**
     * Test XdrSCVal forWasmId
     */
    public function testXdrSCValForWasmId(): void
    {
        $wasmIdHex = "FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF";
        $original = XdrSCVal::forWasmId($wasmIdHex);

        $this->assertEquals(XdrSCValType::SCV_BYTES, $original->getType()->getValue());
        $this->assertNotNull($original->getBytes());

        $encoded = $original->encode();
        $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCValType::SCV_BYTES, $decoded->getType()->getValue());
        $this->assertEquals($encoded, $decoded->encode());
    }

    /**
     * Test XdrInt128Parts encode/decode
     */
    public function testXdrInt128Parts(): void
    {
        $testCases = [
            [0, 0],
            [1, 1],
            [-1, 0],
            [PHP_INT_MAX, PHP_INT_MAX],
            [PHP_INT_MIN, 0],
        ];

        foreach ($testCases as [$hi, $lo]) {
            $original = new XdrInt128Parts($hi, $lo);

            $encoded = $original->encode();
            $decoded = XdrInt128Parts::decode(new XdrBuffer($encoded));

            $this->assertEquals($hi, $decoded->getHi());
            $this->assertEquals($lo, $decoded->getLo());
            $this->assertEquals($encoded, $decoded->encode());
        }
    }

    /**
     * Test XdrUInt128Parts encode/decode
     */
    public function testXdrUInt128Parts(): void
    {
        $testCases = [
            [0, 0],
            [1, 1],
            [PHP_INT_MAX, PHP_INT_MAX],
            [123456789, 987654321],
        ];

        foreach ($testCases as [$hi, $lo]) {
            $original = new XdrUInt128Parts($hi, $lo);

            $encoded = $original->encode();
            $decoded = XdrUInt128Parts::decode(new XdrBuffer($encoded));

            $this->assertEquals($hi, $decoded->getHi());
            $this->assertEquals($lo, $decoded->getLo());
            $this->assertEquals($encoded, $decoded->encode());
        }
    }

    /**
     * Test XdrInt256Parts encode/decode
     */
    public function testXdrInt256Parts(): void
    {
        $testCases = [
            [0, 0, 0, 0],
            [1, 1, 1, 1],
            [-1, 0, 0, 0],
            [PHP_INT_MAX, PHP_INT_MAX, PHP_INT_MAX, PHP_INT_MAX],
            [PHP_INT_MIN, 0, 0, 0],
        ];

        foreach ($testCases as [$hiHi, $hiLo, $loHi, $loLo]) {
            $original = new XdrInt256Parts($hiHi, $hiLo, $loHi, $loLo);

            $encoded = $original->encode();
            $decoded = XdrInt256Parts::decode(new XdrBuffer($encoded));

            $this->assertEquals($hiHi, $decoded->getHiHi());
            $this->assertEquals($hiLo, $decoded->getHiLo());
            $this->assertEquals($loHi, $decoded->getLoHi());
            $this->assertEquals($loLo, $decoded->getLoLo());
            $this->assertEquals($encoded, $decoded->encode());
        }
    }

    /**
     * Test XdrUInt256Parts encode/decode
     */
    public function testXdrUInt256Parts(): void
    {
        $testCases = [
            [0, 0, 0, 0],
            [1, 1, 1, 1],
            [PHP_INT_MAX, PHP_INT_MAX, PHP_INT_MAX, PHP_INT_MAX],
            [111, 222, 333, 444],
        ];

        foreach ($testCases as [$hiHi, $hiLo, $loHi, $loLo]) {
            $original = new XdrUInt256Parts($hiHi, $hiLo, $loHi, $loLo);

            $encoded = $original->encode();
            $decoded = XdrUInt256Parts::decode(new XdrBuffer($encoded));

            $this->assertEquals($hiHi, $decoded->getHiHi());
            $this->assertEquals($hiLo, $decoded->getHiLo());
            $this->assertEquals($loHi, $decoded->getLoHi());
            $this->assertEquals($loLo, $decoded->getLoLo());
            $this->assertEquals($encoded, $decoded->encode());
        }
    }

    /**
     * Test XdrSCMapEntry encode/decode
     */
    public function testXdrSCMapEntry(): void
    {
        $key = XdrSCVal::forSymbol("test_key");
        $val = XdrSCVal::forU32(12345);

        $original = new XdrSCMapEntry($key, $val);

        $encoded = $original->encode();
        $decoded = XdrSCMapEntry::decode(new XdrBuffer($encoded));

        $this->assertEquals("test_key", $decoded->getKey()->getSym());
        $this->assertEquals(12345, $decoded->getVal()->getU32());
        $this->assertEquals($encoded, $decoded->encode());
    }

    /**
     * Test XdrSCAddressType encode/decode
     */
    public function testXdrSCAddressType(): void
    {
        $types = [
            XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT,
            XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT,
            XdrSCAddressType::SC_ADDRESS_TYPE_MUXED_ACCOUNT,
            XdrSCAddressType::SC_ADDRESS_TYPE_CLAIMABLE_BALANCE,
            XdrSCAddressType::SC_ADDRESS_TYPE_LIQUIDITY_POOL,
        ];

        foreach ($types as $typeValue) {
            $original = new XdrSCAddressType($typeValue);

            $encoded = $original->encode();
            $decoded = XdrSCAddressType::decode(new XdrBuffer($encoded));

            $this->assertEquals($typeValue, $decoded->getValue());
            $this->assertEquals($encoded, $decoded->encode());
        }
    }

    /**
     * Test XdrSCNonceKey encode/decode
     */
    public function testXdrSCNonceKey(): void
    {
        $testValues = [0, 1, 12345, 9223372036854775807];

        foreach ($testValues as $nonce) {
            $original = new XdrSCNonceKey($nonce);

            $encoded = $original->encode();
            $decoded = XdrSCNonceKey::decode(new XdrBuffer($encoded));

            $this->assertEquals($nonce, $decoded->getNonce());
            $this->assertEquals($encoded, $decoded->encode());
        }
    }

    /**
     * Test complex nested structure
     */
    public function testComplexNestedStructure(): void
    {
        $innerMap = [
            new XdrSCMapEntry(
                XdrSCVal::forSymbol("inner1"),
                XdrSCVal::forU32(100)
            ),
            new XdrSCMapEntry(
                XdrSCVal::forSymbol("inner2"),
                XdrSCVal::forString("inner_value")
            ),
        ];

        $vec = [
            XdrSCVal::forU32(1),
            XdrSCVal::forU32(2),
            XdrSCVal::forMap($innerMap),
            XdrSCVal::forBool(true),
        ];

        $outerMap = [
            new XdrSCMapEntry(
                XdrSCVal::forSymbol("vec_key"),
                XdrSCVal::forVec($vec)
            ),
            new XdrSCMapEntry(
                XdrSCVal::forSymbol("address_key"),
                XdrSCVal::forAddress(XdrSCAddress::forAccountId(self::TEST_ACCOUNT_ID))
            ),
        ];

        $original = XdrSCVal::forMap($outerMap);

        $encoded = $original->encode();
        $decoded = XdrSCVal::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCValType::SCV_MAP, $decoded->getType()->getValue());
        $this->assertCount(2, $decoded->getMap());

        $vecEntry = $decoded->getMap()[0];
        $this->assertEquals("vec_key", $vecEntry->getKey()->getSym());
        $this->assertEquals(XdrSCValType::SCV_VEC, $vecEntry->getVal()->getType()->getValue());
        $this->assertCount(4, $vecEntry->getVal()->getVec());

        $nestedMap = $vecEntry->getVal()->getVec()[2];
        $this->assertEquals(XdrSCValType::SCV_MAP, $nestedMap->getType()->getValue());
        $this->assertCount(2, $nestedMap->getMap());
        $this->assertEquals("inner1", $nestedMap->getMap()[0]->getKey()->getSym());
        $this->assertEquals(100, $nestedMap->getMap()[0]->getVal()->getU32());

        $addressEntry = $decoded->getMap()[1];
        $this->assertEquals("address_key", $addressEntry->getKey()->getSym());
        $this->assertEquals(XdrSCValType::SCV_ADDRESS, $addressEntry->getVal()->getType()->getValue());

        $this->assertEquals($encoded, $decoded->encode());
    }

    /**
     * Test all getter and setter methods
     */
    public function testGettersAndSetters(): void
    {
        $scVal = new XdrSCVal(XdrSCValType::U32());

        $scVal->setType(XdrSCValType::I32());
        $this->assertEquals(XdrSCValType::SCV_I32, $scVal->getType()->getValue());

        $scVal->setB(true);
        $this->assertTrue($scVal->getB());

        $error = new XdrSCError(XdrSCErrorType::SCE_CONTRACT());
        $scVal->setError($error);
        $this->assertEquals($error, $scVal->getError());

        $scVal->setU32(100);
        $this->assertEquals(100, $scVal->getU32());

        $scVal->setI32(-100);
        $this->assertEquals(-100, $scVal->getI32());

        $scVal->setU64(1000);
        $this->assertEquals(1000, $scVal->getU64());

        $scVal->setI64(-1000);
        $this->assertEquals(-1000, $scVal->getI64());

        $scVal->setTimepoint(1609459200);
        $this->assertEquals(1609459200, $scVal->getTimepoint());

        $scVal->setDuration(3600);
        $this->assertEquals(3600, $scVal->getDuration());

        $u128 = new XdrUInt128Parts(1, 2);
        $scVal->setU128($u128);
        $this->assertEquals($u128, $scVal->getU128());

        $i128 = new XdrInt128Parts(3, 4);
        $scVal->setI128($i128);
        $this->assertEquals($i128, $scVal->getI128());

        $u256 = new XdrUInt256Parts(1, 2, 3, 4);
        $scVal->setU256($u256);
        $this->assertEquals($u256, $scVal->getU256());

        $i256 = new XdrInt256Parts(5, 6, 7, 8);
        $scVal->setI256($i256);
        $this->assertEquals($i256, $scVal->getI256());

        $bytes = new XdrDataValueMandatory("test");
        $scVal->setBytes($bytes);
        $this->assertEquals($bytes, $scVal->getBytes());

        $scVal->setStr("test string");
        $this->assertEquals("test string", $scVal->getStr());

        $scVal->setSym("test_symbol");
        $this->assertEquals("test_symbol", $scVal->getSym());

        $vec = [XdrSCVal::forU32(1)];
        $scVal->setVec($vec);
        $this->assertEquals($vec, $scVal->getVec());

        $map = [new XdrSCMapEntry(XdrSCVal::forU32(1), XdrSCVal::forU32(2))];
        $scVal->setMap($map);
        $this->assertEquals($map, $scVal->getMap());

        $executable = new XdrContractExecutable(XdrContractExecutableType::CONTRACT_EXECUTABLE_WASM());
        $instance = new XdrSCContractInstance($executable, null);
        $scVal->setInstance($instance);
        $this->assertEquals($instance, $scVal->getInstance());

        $address = XdrSCAddress::forAccountId(self::TEST_ACCOUNT_ID);
        $scVal->setAddress($address);
        $this->assertEquals($address, $scVal->getAddress());

        $nonceKey = new XdrSCNonceKey(999);
        $scVal->setNonceKey($nonceKey);
        $this->assertEquals($nonceKey, $scVal->getNonceKey());
    }
}
