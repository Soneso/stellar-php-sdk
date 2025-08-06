<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValType;
use Soneso\StellarSDK\Xdr\XdrUInt128Parts;
use Soneso\StellarSDK\Xdr\XdrInt128Parts;
use Soneso\StellarSDK\Xdr\XdrUInt256Parts;
use Soneso\StellarSDK\Xdr\XdrInt256Parts;
use Soneso\StellarSDK\Soroban\Contract\ContractSpec;
use Soneso\StellarSDK\Xdr\XdrSCSpecType;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeDef;

class XdrSCValBigIntTest extends TestCase
{
    /**
     * Test U128 BigInt conversion with various values
     */
    public function testU128BigIntConversion(): void
    {
        // Test zero
        $val = XdrSCVal::forU128BigInt("0");
        $this->assertEquals(XdrSCValType::SCV_U128, $val->type->value);
        $this->assertEquals(0, $val->u128->hi);
        $this->assertEquals(0, $val->u128->lo);
        $bigInt = $val->toBigInt();
        $this->assertEquals("0", gmp_strval($bigInt));

        // Test small positive value
        $val = XdrSCVal::forU128BigInt("12345");
        $bigInt = $val->toBigInt();
        $this->assertEquals("12345", gmp_strval($bigInt));

        // Test maximum 64-bit value
        $val = XdrSCVal::forU128BigInt("18446744073709551615"); // 2^64 - 1
        $bigInt = $val->toBigInt();
        $this->assertEquals("18446744073709551615", gmp_strval($bigInt));

        // Test value requiring both hi and lo parts
        $val = XdrSCVal::forU128BigInt("18446744073709551616"); // 2^64
        $bigInt = $val->toBigInt();
        $this->assertEquals("18446744073709551616", gmp_strval($bigInt));

        // Test large 128-bit value
        $largeValue = "123456789012345678901234567890";
        $val = XdrSCVal::forU128BigInt($largeValue);
        $bigInt = $val->toBigInt();
        $this->assertEquals($largeValue, gmp_strval($bigInt));

        // Test maximum U128 value
        $maxU128 = gmp_strval(gmp_sub(gmp_pow(2, 128), 1));
        $val = XdrSCVal::forU128BigInt($maxU128);
        $bigInt = $val->toBigInt();
        $this->assertEquals($maxU128, gmp_strval($bigInt));

        // Test with GMP resource
        $gmpValue = gmp_init("999999999999999999999999999999");
        $val = XdrSCVal::forU128BigInt($gmpValue);
        $bigInt = $val->toBigInt();
        $this->assertEquals("999999999999999999999999999999", gmp_strval($bigInt));

        // Test with integer
        $val = XdrSCVal::forU128BigInt(42);
        $bigInt = $val->toBigInt();
        $this->assertEquals("42", gmp_strval($bigInt));
    }

    /**
     * Test I128 BigInt conversion with various values
     */
    public function testI128BigIntConversion(): void
    {
        // Test zero
        $val = XdrSCVal::forI128BigInt("0");
        $this->assertEquals(XdrSCValType::SCV_I128, $val->type->value);
        $bigInt = $val->toBigInt();
        $this->assertEquals("0", gmp_strval($bigInt));

        // Test positive value
        $val = XdrSCVal::forI128BigInt("12345");
        $bigInt = $val->toBigInt();
        $this->assertEquals("12345", gmp_strval($bigInt));

        // Test negative value
        $val = XdrSCVal::forI128BigInt("-12345");
        $bigInt = $val->toBigInt();
        $this->assertEquals("-12345", gmp_strval($bigInt));

        // Test large positive value
        $largePositive = "85070591730234615865843651857942052863"; // Close to 2^127 - 1
        $val = XdrSCVal::forI128BigInt($largePositive);
        $bigInt = $val->toBigInt();
        $this->assertEquals($largePositive, gmp_strval($bigInt));

        // Test large negative value
        $largeNegative = "-85070591730234615865843651857942052863";
        $val = XdrSCVal::forI128BigInt($largeNegative);
        $bigInt = $val->toBigInt();
        $this->assertEquals($largeNegative, gmp_strval($bigInt));

        // Test maximum I128 value
        $maxI128 = gmp_strval(gmp_sub(gmp_pow(2, 127), 1));
        $val = XdrSCVal::forI128BigInt($maxI128);
        $bigInt = $val->toBigInt();
        $this->assertEquals($maxI128, gmp_strval($bigInt));

        // Test minimum I128 value
        $minI128 = gmp_strval(gmp_neg(gmp_pow(2, 127)));
        $val = XdrSCVal::forI128BigInt($minI128);
        $bigInt = $val->toBigInt();
        $this->assertEquals($minI128, gmp_strval($bigInt));

        // Test with negative integer
        $val = XdrSCVal::forI128BigInt(-42);
        $bigInt = $val->toBigInt();
        $this->assertEquals("-42", gmp_strval($bigInt));
    }

    /**
     * Test U256 BigInt conversion with various values
     */
    public function testU256BigIntConversion(): void
    {
        // Test zero
        $val = XdrSCVal::forU256BigInt("0");
        $this->assertEquals(XdrSCValType::SCV_U256, $val->type->value);
        $this->assertEquals(0, $val->u256->hiHi);
        $this->assertEquals(0, $val->u256->hiLo);
        $this->assertEquals(0, $val->u256->loHi);
        $this->assertEquals(0, $val->u256->loLo);
        $bigInt = $val->toBigInt();
        $this->assertEquals("0", gmp_strval($bigInt));

        // Test small positive value
        $val = XdrSCVal::forU256BigInt("12345");
        $bigInt = $val->toBigInt();
        $this->assertEquals("12345", gmp_strval($bigInt));

        // Test large 256-bit value (within range)
        $largeValue = "99999999999999999999999999999999999999999999999999999999999999999999999999";
        $val = XdrSCVal::forU256BigInt($largeValue);
        $bigInt = $val->toBigInt();
        $this->assertEquals($largeValue, gmp_strval($bigInt));

        // Test maximum U256 value
        $maxU256 = gmp_strval(gmp_sub(gmp_pow(2, 256), 1));
        $val = XdrSCVal::forU256BigInt($maxU256);
        $bigInt = $val->toBigInt();
        $this->assertEquals($maxU256, gmp_strval($bigInt));

        // Test with value that fills multiple parts
        $val = XdrSCVal::forU256BigInt(gmp_pow(2, 200));
        $bigInt = $val->toBigInt();
        $this->assertEquals(gmp_strval(gmp_pow(2, 200)), gmp_strval($bigInt));
    }

    /**
     * Test I256 BigInt conversion with various values
     */
    public function testI256BigIntConversion(): void
    {
        // Test zero
        $val = XdrSCVal::forI256BigInt("0");
        $this->assertEquals(XdrSCValType::SCV_I256, $val->type->value);
        $bigInt = $val->toBigInt();
        $this->assertEquals("0", gmp_strval($bigInt));

        // Test positive value
        $val = XdrSCVal::forI256BigInt("12345");
        $bigInt = $val->toBigInt();
        $this->assertEquals("12345", gmp_strval($bigInt));

        // Test negative value
        $val = XdrSCVal::forI256BigInt("-12345");
        $bigInt = $val->toBigInt();
        $this->assertEquals("-12345", gmp_strval($bigInt));

        // Test large positive value
        $largePositive = "5789604461865809771178549250434395392663499233282028201972879200395656481995";
        $val = XdrSCVal::forI256BigInt($largePositive);
        $bigInt = $val->toBigInt();
        $this->assertEquals($largePositive, gmp_strval($bigInt));

        // Test large negative value
        $largeNegative = "-5789604461865809771178549250434395392663499233282028201972879200395656481995";
        $val = XdrSCVal::forI256BigInt($largeNegative);
        $bigInt = $val->toBigInt();
        $this->assertEquals($largeNegative, gmp_strval($bigInt));

        // Test maximum I256 value
        $maxI256 = gmp_strval(gmp_sub(gmp_pow(2, 255), 1));
        $val = XdrSCVal::forI256BigInt($maxI256);
        $bigInt = $val->toBigInt();
        $this->assertEquals($maxI256, gmp_strval($bigInt));

        // Test minimum I256 value
        $minI256 = gmp_strval(gmp_neg(gmp_pow(2, 255)));
        $val = XdrSCVal::forI256BigInt($minI256);
        $bigInt = $val->toBigInt();
        $this->assertEquals($minI256, gmp_strval($bigInt));

        // Test with negative power of 2
        $val = XdrSCVal::forI256BigInt(gmp_neg(gmp_pow(2, 200)));
        $bigInt = $val->toBigInt();
        $this->assertEquals(gmp_strval(gmp_neg(gmp_pow(2, 200))), gmp_strval($bigInt));
    }

    /**
     * Test error handling for out-of-range values
     */
    public function testOutOfRangeValues(): void
    {
        // Test U128 with negative value
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("U128 value cannot be negative");
        XdrSCVal::forU128BigInt("-1");
    }

    /**
     * Test error handling for U128 overflow
     */
    public function testU128Overflow(): void
    {
        // Test U128 with value exceeding maximum
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Value exceeds U128 maximum");
        $overflowValue = gmp_strval(gmp_pow(2, 128)); // 2^128 is one more than max
        XdrSCVal::forU128BigInt($overflowValue);
    }

    /**
     * Test error handling for U256 with negative value
     */
    public function testU256Negative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("U256 value cannot be negative");
        XdrSCVal::forU256BigInt("-1");
    }

    /**
     * Test error handling for U256 overflow
     */
    public function testU256Overflow(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Value exceeds U256 maximum");
        $overflowValue = gmp_strval(gmp_pow(2, 256)); // 2^256 is one more than max
        XdrSCVal::forU256BigInt($overflowValue);
    }

    /**
     * Test error handling for I128 overflow
     */
    public function testI128Overflow(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Value out of I128 range");
        $overflowValue = gmp_strval(gmp_pow(2, 127)); // 2^127 is one more than max
        XdrSCVal::forI128BigInt($overflowValue);
    }

    /**
     * Test error handling for I128 underflow
     */
    public function testI128Underflow(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Value out of I128 range");
        $underflowValue = gmp_strval(gmp_sub(gmp_neg(gmp_pow(2, 127)), 1)); // One less than min
        XdrSCVal::forI128BigInt($underflowValue);
    }

    /**
     * Test error handling for I256 overflow
     */
    public function testI256Overflow(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Value out of I256 range");
        $overflowValue = gmp_strval(gmp_pow(2, 255)); // 2^255 is one more than max
        XdrSCVal::forI256BigInt($overflowValue);
    }

    /**
     * Test error handling for I256 underflow
     */
    public function testI256Underflow(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Value out of I256 range");
        $underflowValue = gmp_strval(gmp_sub(gmp_neg(gmp_pow(2, 255)), 1)); // One less than min
        XdrSCVal::forI256BigInt($underflowValue);
    }

    /**
     * Test toBigInt with unsupported types
     */
    public function testToBigIntUnsupportedTypes(): void
    {
        // Test with U32
        $val = XdrSCVal::forU32(42);
        $this->assertNull($val->toBigInt());

        // Test with String
        $val = XdrSCVal::forString("hello");
        $this->assertNull($val->toBigInt());

        // Test with Bool
        $val = XdrSCVal::forBool(true);
        $this->assertNull($val->toBigInt());
    }

    /**
     * Test ContractSpec integration with BigInt values
     */
    public function testContractSpecWithBigInt(): void
    {
        $spec = new ContractSpec([]);
        
        // Test U128 with string
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::U128());
        $val = $spec->nativeToXdrSCVal("123456789012345678901234567890", $typeDef);
        $this->assertEquals(XdrSCValType::SCV_U128, $val->type->value);
        $bigInt = $val->toBigInt();
        $this->assertEquals("123456789012345678901234567890", gmp_strval($bigInt));

        // Test I128 with negative string
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::I128());
        $val = $spec->nativeToXdrSCVal("-123456789012345678901234567890", $typeDef);
        $this->assertEquals(XdrSCValType::SCV_I128, $val->type->value);
        $bigInt = $val->toBigInt();
        $this->assertEquals("-123456789012345678901234567890", gmp_strval($bigInt));

        // Test U256 with GMP
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::U256());
        $gmpValue = gmp_init("9999999999999999999999999999999999999999999999999999999999999999999999999999");
        $val = $spec->nativeToXdrSCVal($gmpValue, $typeDef);
        $this->assertEquals(XdrSCValType::SCV_U256, $val->type->value);
        $bigInt = $val->toBigInt();
        $this->assertEquals("9999999999999999999999999999999999999999999999999999999999999999999999999999", gmp_strval($bigInt));

        // Test I256 with negative GMP
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::I256());
        $gmpValue = gmp_init("-9999999999999999999999999999999999999999999999999999999999999999999999999999");
        $val = $spec->nativeToXdrSCVal($gmpValue, $typeDef);
        $this->assertEquals(XdrSCValType::SCV_I256, $val->type->value);
        $bigInt = $val->toBigInt();
        $this->assertEquals("-9999999999999999999999999999999999999999999999999999999999999999999999999999", gmp_strval($bigInt));

        // Test backward compatibility with XdrUInt128Parts
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::U128());
        $parts = new XdrUInt128Parts(12345, 67890);
        $val = $spec->nativeToXdrSCVal($parts, $typeDef);
        $this->assertEquals(XdrSCValType::SCV_U128, $val->type->value);
        $this->assertEquals(12345, $val->u128->hi);
        $this->assertEquals(67890, $val->u128->lo);

        // Test backward compatibility with XdrInt256Parts
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::I256());
        $parts = new XdrInt256Parts(1, 2, 3, 4);
        $val = $spec->nativeToXdrSCVal($parts, $typeDef);
        $this->assertEquals(XdrSCValType::SCV_I256, $val->type->value);
        $this->assertEquals(1, $val->i256->hiHi);
        $this->assertEquals(2, $val->i256->hiLo);
        $this->assertEquals(3, $val->i256->loHi);
        $this->assertEquals(4, $val->i256->loLo);

        // Test small integer gets converted properly
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::U128());
        $val = $spec->nativeToXdrSCVal(42, $typeDef);
        $this->assertEquals(XdrSCValType::SCV_U128, $val->type->value);
        $bigInt = $val->toBigInt();
        $this->assertEquals("42", gmp_strval($bigInt));
    }

    /**
     * Test roundtrip conversion for all types
     */
    public function testRoundtripConversion(): void
    {
        // Test U128 roundtrip
        $original = "340282366920938463463374607431768211455"; // Max U128
        $val = XdrSCVal::forU128BigInt($original);
        $converted = $val->toBigInt();
        $this->assertEquals($original, gmp_strval($converted));

        // Test I128 roundtrip with negative
        $original = "-170141183460469231731687303715884105728"; // Min I128
        $val = XdrSCVal::forI128BigInt($original);
        $converted = $val->toBigInt();
        $this->assertEquals($original, gmp_strval($converted));

        // Test U256 roundtrip
        $original = "115792089237316195423570985008687907853269984665640564039457584007913129639935"; // Max U256
        $val = XdrSCVal::forU256BigInt($original);
        $converted = $val->toBigInt();
        $this->assertEquals($original, gmp_strval($converted));

        // Test I256 roundtrip with negative
        $original = "-57896044618658097711785492504343953926634992332820282019728792003956564819968"; // Min I256
        $val = XdrSCVal::forI256BigInt($original);
        $converted = $val->toBigInt();
        $this->assertEquals($original, gmp_strval($converted));

        // Test various edge cases
        $testCases = [
            "1",
            "-1",
            "0",
            "9999999999999999999999999999",
            "-9999999999999999999999999999",
            gmp_strval(gmp_pow(2, 64)),
            gmp_strval(gmp_pow(2, 127)),
            gmp_strval(gmp_neg(gmp_pow(2, 127))),
        ];

        foreach ($testCases as $testValue) {
            $gmpValue = gmp_init($testValue);
            
            // Test with appropriate type based on value range
            if (gmp_cmp($gmpValue, 0) >= 0 && gmp_cmp($gmpValue, gmp_sub(gmp_pow(2, 128), 1)) <= 0) {
                $val = XdrSCVal::forU128BigInt($testValue);
                $converted = $val->toBigInt();
                $this->assertEquals($testValue, gmp_strval($converted), "U128 roundtrip failed for: $testValue");
            }
            
            if (gmp_cmp($gmpValue, gmp_neg(gmp_pow(2, 127))) >= 0 && gmp_cmp($gmpValue, gmp_sub(gmp_pow(2, 127), 1)) <= 0) {
                $val = XdrSCVal::forI128BigInt($testValue);
                $converted = $val->toBigInt();
                $this->assertEquals($testValue, gmp_strval($converted), "I128 roundtrip failed for: $testValue");
            }
        }
    }
}