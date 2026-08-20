<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Util;

use InvalidArgumentException;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Crypto\CryptoKeyType;
use Soneso\StellarSDK\Util\CustomFriendBot;
use Soneso\StellarSDK\Util\Hash;
use Soneso\StellarSDK\Util\StellarAmount;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertTrue;

class UtilTest extends TestCase
{
    public function setUp(): void
    {
        error_reporting(E_ALL);
    }

    // StellarAmount Tests

    public function testStellarAmountFromString()
    {
        $amount = StellarAmount::fromString("100.5");
        assertEquals("100.5000000", $amount->getDecimalValueAsString());
        assertEquals("1005000000", $amount->getStroopsAsString());
    }

    public function testStellarAmountFromStringRejectsCommas()
    {
        // A comma groups digits in some locales and separates decimals in others, so "100,5" names
        // both 1005 and 100.5 and the intended one cannot be recovered from the string.
        foreach (["1,000.25", "100,5", "0,5"] as $amount) {
            try {
                StellarAmount::fromString($amount);
                $this->fail("expected a comma to be rejected: " . $amount);
            } catch (\InvalidArgumentException $e) {
                $this->assertStringStartsWith("Amount must not contain a comma", $e->getMessage());
            }
        }

        // A plain space stays accepted, matching the long-standing contract of this method.
        assertEquals("10002500000", StellarAmount::fromString("1 000.25")->getStroopsAsString());
    }

    public function testStellarAmountFromStringWithSpaces()
    {
        $amount = StellarAmount::fromString("1 000.25");
        assertEquals("1000.2500000", $amount->getDecimalValueAsString());
        assertEquals("10002500000", $amount->getStroopsAsString());
    }

    public function testStellarAmountFromStringMaxPrecision()
    {
        // Test 7 decimal places (maximum precision)
        $amount = StellarAmount::fromString("123.4567890");
        assertEquals("123.4567890", $amount->getDecimalValueAsString());
        assertEquals("1234567890", $amount->getStroopsAsString());
    }

    public function testStellarAmountFromStringMinimalDecimals()
    {
        $amount = StellarAmount::fromString("100.1");
        assertEquals("100.1000000", $amount->getDecimalValueAsString());
        assertEquals("1001000000", $amount->getStroopsAsString());
    }

    public function testStellarAmountFromStringNoDecimals()
    {
        $amount = StellarAmount::fromString("100");
        assertEquals("100.0000000", $amount->getDecimalValueAsString());
        assertEquals("1000000000", $amount->getStroopsAsString());
    }

    public function testStellarAmountFromStringZero()
    {
        $amount = StellarAmount::fromString("0");
        assertEquals("0.0000000", $amount->getDecimalValueAsString());
        assertEquals("0", $amount->getStroopsAsString());
    }

    public function testStellarAmountFromStringOnlyDecimals()
    {
        $amount = StellarAmount::fromString("0.0000001");
        assertEquals("0.0000001", $amount->getDecimalValueAsString());
        assertEquals("1", $amount->getStroopsAsString());
    }

    public function testStellarAmountFromFloat()
    {
        $amount = StellarAmount::fromFloat(100.5);
        assertEquals("100.5000000", $amount->getDecimalValueAsString());
        assertEquals("1005000000", $amount->getStroopsAsString());
    }

    public function testStellarAmountFromFloatZero()
    {
        $amount = StellarAmount::fromFloat(0.0);
        assertEquals("0.0000000", $amount->getDecimalValueAsString());
        assertEquals("0", $amount->getStroopsAsString());
    }

    public function testStellarAmountRejectsNegativeAmountsBelowOne()
    {
        // The sign of an amount whose integer part is zero lives nowhere else, so reading it off
        // that part alone turns a negative amount into a payable positive one.
        foreach ([-0.5, -0.0000123, -0.9999999] as $amount) {
            try {
                StellarAmount::fromFloat($amount);
                $this->fail("expected a negative amount to be rejected: " . var_export($amount, true));
            } catch (\InvalidArgumentException $e) {
                assertEquals("Amount cannot be negative", $e->getMessage());
            }
        }

        try {
            StellarAmount::fromString("-0.5");
            $this->fail("expected a negative amount string to be rejected");
        } catch (\InvalidArgumentException $e) {
            assertEquals("Amount cannot be negative", $e->getMessage());
        }
    }

    public function testStellarAmountRejectsMalformedDecimalStrings()
    {
        // What follows the sign must be digits around at most one point. A second sign would
        // otherwise be read as part of the integer and negated back, turning "--5" into a payable
        // five, and a non-ASCII digit carries no value here.
        foreach (["--5", "--5.0", "5-3", "++5", "+-5", "abc", "5e3", "1.2.3", "1..2",
                     "\u{0665}\u{0660}\u{0660}", "\u{FF15}"] as $amount) {
            try {
                StellarAmount::fromString($amount);
                $this->fail("expected a malformed amount to be rejected: " . $amount);
            } catch (\InvalidArgumentException $e) {
                $this->assertStringStartsWith("Amount is not a decimal number", $e->getMessage());
            }
        }

        // Surrounding whitespace is padding and is removed.
        assertEquals("5000000", StellarAmount::fromString("0.5\n")->getStroopsAsString());
        assertEquals("1234000", StellarAmount::fromString("0.1234\n")->getStroopsAsString());
        assertEquals("50000000", StellarAmount::fromString("\t5\r\n")->getStroopsAsString());

        // Whitespace between digits is not padding, and joining the digits across it would name a
        // different amount; only a plain space groups digits. A NUL is not whitespace at all, so a
        // fixed-width or C-style buffer carrying one names no amount rather than the digits in it.
        foreach (["1\x0B0", "1\x0C0", "1\n000.25", "1\t5", "100.5\x00", "\x005"] as $amount) {
            try {
                StellarAmount::fromString($amount);
                $this->fail("expected the amount to be rejected: " . json_encode($amount));
            } catch (\InvalidArgumentException $e) {
                $this->assertStringStartsWith("Amount is not a decimal number", $e->getMessage());
            }
        }

        // A single leading sign stays valid, and so do the shapes without one.
        assertEquals("50000000", StellarAmount::fromString("+5")->getStroopsAsString());
        assertEquals("5000000", StellarAmount::fromString(".5")->getStroopsAsString());
        assertEquals("50000000", StellarAmount::fromString("5.")->getStroopsAsString());
    }

    public function testStellarAmountRejectsMoreThanSevenDecimalPlaces()
    {
        // An eighth significant decimal place names a value finer than a stroop, so it is rejected
        // rather than rounded away.
        foreach (["0.12345678", "1.23456789", "10.123456789", "0.00000005"] as $amount) {
            try {
                StellarAmount::fromString($amount);
                $this->fail("expected more than seven decimal places to be rejected: " . $amount);
            } catch (\InvalidArgumentException $e) {
                $this->assertStringStartsWith("Amount cannot have more than 7 decimal places", $e->getMessage());
            }
        }

        // Trailing zeroes carry no value and do not count towards the limit.
        assertEquals("5000000", StellarAmount::fromString("0.50000000")->getStroopsAsString());
        assertEquals("10000000", StellarAmount::fromString("1.0000000000")->getStroopsAsString());
        assertEquals("1", StellarAmount::fromString("0.0000001")->getStroopsAsString());
    }

    public function testStellarAmountRejectsStringsNamingNoAmount()
    {
        // An empty, sign-only or point-only string names no amount, so it is refused rather than
        // read as zero.
        foreach (["", ".", "+", "-", "   ", "\n", "+.", "-."] as $amount) {
            try {
                StellarAmount::fromString($amount);
                $this->fail("expected a string naming no amount to be rejected: " . json_encode($amount));
            } catch (\InvalidArgumentException $e) {
                $this->assertStringStartsWith("Amount is not a decimal number", $e->getMessage());
            }
        }

        // A zero amount is a real amount: a trustline limit of "0" removes the trustline.
        assertEquals("0", StellarAmount::fromString("0")->getStroopsAsString());
        assertEquals("0", StellarAmount::fromString("0.0000000")->getStroopsAsString());
    }

    public function testStellarAmountRejectsNonFiniteFloats()
    {
        foreach ([NAN, INF, -INF] as $amount) {
            try {
                StellarAmount::fromFloat($amount);
                $this->fail("expected a non-finite amount to be rejected");
            } catch (\InvalidArgumentException $e) {
                assertEquals("Amount must be a finite number", $e->getMessage());
            }
        }
    }

    public function testStellarAmountFromFloatRoundsTheStoredValue()
    {
        // The float nearest 1.5e-7 is 1.49999999999999993e-7, so one stroop is nearer than two.
        assertEquals("1", StellarAmount::fromFloat(1.5e-7)->getStroopsAsString());
    }

    public function testStellarAmountFromFloatInventsNoStroop()
    {
        // On PHP 8.4 and later, scaling to stroops by a decimal pre-round adds a stroop to each of
        // these; earlier versions round them correctly. The first three are carried exactly by the
        // float; the fourth is 780615714.94290959835052490234375 and rounds to the stroop asserted
        // here. The expected values are the correctly rounded ones on every version.
        assertEquals("5000000000000000", StellarAmount::fromFloat(500000000.0)->getStroopsAsString());
        assertEquals("9000000000000000", StellarAmount::fromFloat(900000000.0)->getStroopsAsString());
        assertEquals("4503599630000000", StellarAmount::fromFloat(450359963.0)->getStroopsAsString());
        assertEquals("7806157149429096", StellarAmount::fromFloat(780615714.9429096)->getStroopsAsString());
    }

    public function testStellarAmountFromFloatLargeNumber()
    {
        // Note: Float precision issues may occur with very large numbers
        // Use string representation for exact values
        $amount = StellarAmount::fromString("922337203685.4775807");
        assertEquals("922337203685.4775807", $amount->getDecimalValueAsString());
    }

    public function testStellarAmountMaximum()
    {
        $maxAmount = StellarAmount::maximum();
        assertEquals("922337203685.4775807", $maxAmount->getDecimalValueAsString());
        assertEquals("9223372036854775807", $maxAmount->getStroopsAsString());
    }

    public function testStellarAmountExceedsMaximum()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Maximum value exceeded");

        // Try to create amount larger than max
        StellarAmount::fromString("922337203686");
    }

    public function testStellarAmountExceedsMaximumByOneStroop()
    {
        // The maximum is 9223372036854775807 stroops; one stroop more must be rejected.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Maximum value exceeded");
        new StellarAmount(new \phpseclib3\Math\BigInteger('9223372036854775808'));
    }

    public function testStellarAmountNegative()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Amount cannot be negative");

        // Create with negative stroops
        new StellarAmount(new \phpseclib3\Math\BigInteger('-1'));
    }

    public function testStellarAmountSmallValues()
    {
        // Test smallest possible amount (1 stroop)
        $amount = StellarAmount::fromString("0.0000001");
        assertEquals("0.0000001", $amount->getDecimalValueAsString());
        assertEquals("1", $amount->getStroopsAsString());
    }

    public function testStellarAmountRounding()
    {
        // Test that amounts are properly formatted
        $amount = StellarAmount::fromString("1.1");
        assertEquals("1.1000000", $amount->getDecimalValueAsString());

        $amount2 = StellarAmount::fromString("50.0");
        assertEquals("50.0000000", $amount2->getDecimalValueAsString());
    }

    public function testStellarAmountGetStroops()
    {
        $amount = StellarAmount::fromString("100.5");
        $stroops = $amount->getStroops();

        assertTrue($stroops instanceof \phpseclib3\Math\BigInteger);
        assertEquals("1005000000", $stroops->toString());
    }

    // Hash Tests

    public function testHashGenerate()
    {
        $data = "test data";
        $hash = Hash::generate($data);

        // SHA-256 produces 32 bytes
        assertEquals(32, strlen($hash));

        // Same data should produce same hash
        $hash2 = Hash::generate($data);
        assertEquals($hash, $hash2);
    }

    public function testHashAsString()
    {
        $data = "test data";
        $hashString = Hash::asString($data);

        // SHA-256 hex string is 64 characters
        assertEquals(64, strlen($hashString));

        // Should be valid hex
        assertTrue(ctype_xdigit($hashString));

        // Same data should produce same hash
        $hashString2 = Hash::asString($data);
        assertEquals($hashString, $hashString2);
    }

    public function testHashConsistency()
    {
        $data = "test data";

        // Generate binary hash
        $binaryHash = Hash::generate($data);

        // Generate hex string hash
        $hexHash = Hash::asString($data);

        // Converting binary to hex should match hex hash
        assertEquals($hexHash, bin2hex($binaryHash));
    }

    public function testHashDifferentData()
    {
        $data1 = "test data 1";
        $data2 = "test data 2";

        $hash1 = Hash::generate($data1);
        $hash2 = Hash::generate($data2);

        // Different data should produce different hashes
        assertTrue($hash1 !== $hash2);
    }

    public function testHashEmptyString()
    {
        $hash = Hash::generate("");
        assertEquals(32, strlen($hash));

        $hashString = Hash::asString("");
        assertEquals(64, strlen($hashString));
    }

    public function testHashKnownValue()
    {
        // Test with known SHA-256 value
        $data = "hello";
        $expectedHex = "2cf24dba5fb0a30e26e83b2ac5b9e29e1b161e5c1fa7425e73043362938b9824";

        $hash = Hash::asString($data);
        assertEquals($expectedHex, $hash);
    }

    public function testHashBinaryData()
    {
        // Test hashing binary data
        $binaryData = pack("C*", 0, 1, 2, 3, 4, 5);
        $hash = Hash::generate($binaryData);

        assertEquals(32, strlen($hash));

        // Should be reproducible
        $hash2 = Hash::generate($binaryData);
        assertEquals($hash, $hash2);
    }

    // CryptoKeyType Tests

    public function testCryptoKeyTypeConstants()
    {
        assertEquals(0, CryptoKeyType::KEY_TYPE_ED25519);
        assertEquals(1, CryptoKeyType::KEY_TYPE_PRE_AUTH_TX);
        assertEquals(2, CryptoKeyType::KEY_TYPE_HASH_X);
        assertEquals(3, CryptoKeyType::KEY_TYPE_ED25519_SIGNED_PAYLOAD);
        assertEquals(256, CryptoKeyType::KEY_TYPE_MUXED_ED25519);
    }

    public function testCryptoKeyTypeConstructor()
    {
        $keyType = new CryptoKeyType(CryptoKeyType::KEY_TYPE_ED25519);
        assertTrue($keyType instanceof CryptoKeyType);
    }

    public function testCryptoKeyTypeXdrEncoding()
    {
        // Test ED25519 key type
        $keyType = new CryptoKeyType(CryptoKeyType::KEY_TYPE_ED25519);
        $xdr = $keyType->toXdr();
        assertEquals(1, strlen($xdr));

        // Decode back
        $decoded = CryptoKeyType::fromXdr($xdr);
        assertTrue($decoded instanceof CryptoKeyType);
    }

    public function testCryptoKeyTypeXdrRoundTrip()
    {
        // Test each key type
        $keyTypes = [
            CryptoKeyType::KEY_TYPE_ED25519,
            CryptoKeyType::KEY_TYPE_PRE_AUTH_TX,
            CryptoKeyType::KEY_TYPE_HASH_X,
            CryptoKeyType::KEY_TYPE_ED25519_SIGNED_PAYLOAD,
        ];

        foreach ($keyTypes as $typeValue) {
            $keyType = new CryptoKeyType($typeValue);
            $xdr = $keyType->toXdr();
            $decoded = CryptoKeyType::fromXdr($xdr);

            // Verify round trip
            assertEquals($xdr, $decoded->toXdr());
        }
    }

    public function testCryptoKeyTypeFromXdr()
    {
        // Create XDR for ED25519 (value 0)
        $xdr = pack('C', 0);
        $keyType = CryptoKeyType::fromXdr($xdr);

        assertTrue($keyType instanceof CryptoKeyType);
        assertEquals($xdr, $keyType->toXdr());
    }

    // CustomFriendBot Tests

    public function testCustomFriendBotConstructor(): void
    {
        $url = "http://localhost:8000/friendbot";
        $bot = new CustomFriendBot($url);

        $this->assertInstanceOf(CustomFriendBot::class, $bot);
        $this->assertEquals($url, $bot->friendBotUrl);
    }

    public function testCustomFriendBotGetFriendBotUrl(): void
    {
        $url = "https://custom-friendbot.example.com";
        $bot = new CustomFriendBot($url);

        $this->assertEquals($url, $bot->getFriendBotUrl());
    }

    public function testCustomFriendBotSetFriendBotUrl(): void
    {
        $originalUrl = "http://localhost:8000/friendbot";
        $newUrl = "https://new-friendbot.example.com";

        $bot = new CustomFriendBot($originalUrl);
        $this->assertEquals($originalUrl, $bot->getFriendBotUrl());

        $bot->setFriendBotUrl($newUrl);
        $this->assertEquals($newUrl, $bot->getFriendBotUrl());
    }

    public function testCustomFriendBotUrlPropertyAccess(): void
    {
        $url = "http://localhost:8000/friendbot";
        $bot = new CustomFriendBot($url);

        // Direct property access
        $this->assertEquals($url, $bot->friendBotUrl);

        // Modify via property
        $newUrl = "https://new-url.example.com";
        $bot->friendBotUrl = $newUrl;
        $this->assertEquals($newUrl, $bot->friendBotUrl);
        $this->assertEquals($newUrl, $bot->getFriendBotUrl());
    }

    // StellarAmount fromXdr Tests

    public function testStellarAmountFromXdr(): void
    {
        // Create XDR buffer with a 64-bit signed integer (100.5 XLM = 1005000000 stroops)
        $stroops = 1005000000;
        $xdrData = pack('J', $stroops); // 64-bit big-endian
        $xdrBuffer = new XdrBuffer($xdrData);

        $amount = StellarAmount::fromXdr($xdrBuffer);

        $this->assertEquals("100.5000000", $amount->getDecimalValueAsString());
        $this->assertEquals("1005000000", $amount->getStroopsAsString());
    }

    public function testStellarAmountFromXdrZero(): void
    {
        // Create XDR buffer with zero
        $xdrData = pack('J', 0);
        $xdrBuffer = new XdrBuffer($xdrData);

        $amount = StellarAmount::fromXdr($xdrBuffer);

        $this->assertEquals("0.0000000", $amount->getDecimalValueAsString());
        $this->assertEquals("0", $amount->getStroopsAsString());
    }

    public function testStellarAmountFromXdrSmallValue(): void
    {
        // Create XDR buffer with 1 stroop (smallest amount)
        $xdrData = pack('J', 1);
        $xdrBuffer = new XdrBuffer($xdrData);

        $amount = StellarAmount::fromXdr($xdrBuffer);

        $this->assertEquals("0.0000001", $amount->getDecimalValueAsString());
        $this->assertEquals("1", $amount->getStroopsAsString());
    }

    public function testStellarAmountFromXdrLargeValue(): void
    {
        // Create XDR buffer with a large value (1000 XLM)
        $stroops = 10000000000; // 1000 XLM
        $xdrData = pack('J', $stroops);
        $xdrBuffer = new XdrBuffer($xdrData);

        $amount = StellarAmount::fromXdr($xdrBuffer);

        $this->assertEquals("1000.0000000", $amount->getDecimalValueAsString());
        $this->assertEquals("10000000000", $amount->getStroopsAsString());
    }

    public function testStellarAmountFromStringAllZeroDecimals(): void
    {
        // Test with decimal part that is all zeros
        $amount = StellarAmount::fromString("100.0000000");
        $this->assertEquals("100.0000000", $amount->getDecimalValueAsString());
        $this->assertEquals("1000000000", $amount->getStroopsAsString());
    }

    public function testStellarAmountFromStringLeadingDecimalZeros(): void
    {
        // Test with leading zeros in decimal part
        $amount = StellarAmount::fromString("100.0001");
        $this->assertEquals("100.0001000", $amount->getDecimalValueAsString());
        $this->assertEquals("1000001000", $amount->getStroopsAsString());
    }
}
