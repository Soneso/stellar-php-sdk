<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Core;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Memo;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertNull;

class MemoTest extends TestCase
{
    public function setUp(): void
    {
        error_reporting(E_ALL);
    }

    public function testMemoNone()
    {
        $memo = Memo::none();
        assertEquals(Memo::MEMO_TYPE_NONE, $memo->getType());
        assertNull($memo->getValue());
    }

    public function testMemoText()
    {
        $text = "Test payment";
        $memo = Memo::text($text);

        assertEquals(Memo::MEMO_TYPE_TEXT, $memo->getType());
        assertEquals($text, $memo->getValue());
    }

    public function testMemoTextMaxLength()
    {
        $text = str_repeat("a", 28);
        $memo = Memo::text($text);
        assertEquals($text, $memo->getValue());
    }

    public function testMemoTextTooLong()
    {
        $this->expectException(InvalidArgumentException::class);
        $text = str_repeat("a", 29);
        Memo::text($text);
    }

    public function testMemoId()
    {
        $id = 123456789;
        $memo = Memo::id($id);

        assertEquals(Memo::MEMO_TYPE_ID, $memo->getType());
        assertEquals($id, $memo->getValue());
    }

    public function testMemoIdNegative()
    {
        $this->expectException(InvalidArgumentException::class);
        Memo::id(-1);
    }

    public function testMemoHash()
    {
        $hash = random_bytes(32);
        $memo = Memo::hash($hash);

        assertEquals(Memo::MEMO_TYPE_HASH, $memo->getType());
        assertEquals($hash, $memo->getValue());
    }

    public function testMemoHashInvalidLength()
    {
        $this->expectException(InvalidArgumentException::class);
        $hash = random_bytes(16);
        Memo::hash($hash);
    }

    public function testMemoReturn()
    {
        $hash = random_bytes(32);
        $memo = Memo::return($hash);

        assertEquals(Memo::MEMO_TYPE_RETURN, $memo->getType());
        assertEquals($hash, $memo->getValue());
    }

    public function testMemoReturnInvalidLength()
    {
        $this->expectException(InvalidArgumentException::class);
        $hash = random_bytes(31);
        Memo::return($hash);
    }

    public function testMemoTypeAsString()
    {
        assertEquals("none", Memo::none()->typeAsString());
        assertEquals("text", Memo::text("test")->typeAsString());
        assertEquals("id", Memo::id(123)->typeAsString());
        assertEquals("hash", Memo::hash(random_bytes(32))->typeAsString());
        assertEquals("return", Memo::return(random_bytes(32))->typeAsString());
    }

    public function testMemoValueAsStringNone()
    {
        $memo = Memo::none();
        assertNull($memo->valueAsString());
    }

    public function testMemoValueAsStringText()
    {
        $text = "Test payment";
        $memo = Memo::text($text);
        assertEquals($text, $memo->valueAsString());
    }

    public function testMemoValueAsStringId()
    {
        $id = 123456789;
        $memo = Memo::id($id);
        assertEquals("123456789", $memo->valueAsString());
    }

    public function testMemoValueAsStringHash()
    {
        $hash = random_bytes(32);
        $memo = Memo::hash($hash);
        assertEquals(base64_encode($hash), $memo->valueAsString());
    }

    public function testMemoValueAsStringReturn()
    {
        $hash = random_bytes(32);
        $memo = Memo::return($hash);
        assertEquals(base64_encode($hash), $memo->valueAsString());
    }

    public function testMemoToXdrNone()
    {
        $memo = Memo::none();
        $xdr = $memo->toXdr();
        assertNotNull($xdr);
        assertEquals(Memo::MEMO_TYPE_NONE, $xdr->getType()->getValue());
    }

    public function testMemoToXdrText()
    {
        $text = "Test payment";
        $memo = Memo::text($text);
        $xdr = $memo->toXdr();

        assertNotNull($xdr);
        assertEquals(Memo::MEMO_TYPE_TEXT, $xdr->getType()->getValue());
        assertEquals($text, $xdr->getText());
    }

    public function testMemoToXdrId()
    {
        $id = 123456789;
        $memo = Memo::id($id);
        $xdr = $memo->toXdr();

        assertNotNull($xdr);
        assertEquals(Memo::MEMO_TYPE_ID, $xdr->getType()->getValue());
        assertEquals($id, $xdr->getId());
    }

    public function testMemoToXdrHash()
    {
        $hash = random_bytes(32);
        $memo = Memo::hash($hash);
        $xdr = $memo->toXdr();

        assertNotNull($xdr);
        assertEquals(Memo::MEMO_TYPE_HASH, $xdr->getType()->getValue());
        assertEquals($hash, $xdr->getHash());
    }

    public function testMemoToXdrReturn()
    {
        $hash = random_bytes(32);
        $memo = Memo::return($hash);
        $xdr = $memo->toXdr();

        assertNotNull($xdr);
        assertEquals(Memo::MEMO_TYPE_RETURN, $xdr->getType()->getValue());
        assertEquals($hash, $xdr->getReturnHash());
    }

    public function testMemoFromXdrNone()
    {
        $memo = Memo::none();
        $xdr = $memo->toXdr();
        $parsed = Memo::fromXdr($xdr);

        assertEquals(Memo::MEMO_TYPE_NONE, $parsed->getType());
        assertNull($parsed->getValue());
    }

    public function testMemoFromXdrText()
    {
        $text = "Test payment";
        $memo = Memo::text($text);
        $xdr = $memo->toXdr();
        $parsed = Memo::fromXdr($xdr);

        assertEquals(Memo::MEMO_TYPE_TEXT, $parsed->getType());
        assertEquals($text, $parsed->getValue());
    }

    public function testMemoFromXdrId()
    {
        $id = 123456789;
        $memo = Memo::id($id);
        $xdr = $memo->toXdr();
        $parsed = Memo::fromXdr($xdr);

        assertEquals(Memo::MEMO_TYPE_ID, $parsed->getType());
        assertEquals($id, $parsed->getValue());
    }

    public function testMemoFromXdrHash()
    {
        $hash = random_bytes(32);
        $memo = Memo::hash($hash);
        $xdr = $memo->toXdr();
        $parsed = Memo::fromXdr($xdr);

        assertEquals(Memo::MEMO_TYPE_HASH, $parsed->getType());
        assertEquals($hash, $parsed->getValue());
    }

    public function testMemoFromXdrReturn()
    {
        $hash = random_bytes(32);
        $memo = Memo::return($hash);
        $xdr = $memo->toXdr();
        $parsed = Memo::fromXdr($xdr);

        assertEquals(Memo::MEMO_TYPE_RETURN, $parsed->getType());
        assertEquals($hash, $parsed->getValue());
    }

    public function testMemoValidation()
    {
        $memo = new Memo(Memo::MEMO_TYPE_TEXT, "valid");
        $memo->validate();

        $this->expectException(InvalidArgumentException::class);
        $memo = new Memo(Memo::MEMO_TYPE_TEXT, str_repeat("a", 29));
    }

    public function testIdMemoFromStringMaxValueRoundTrip()
    {
        $memo = Memo::id("18446744073709551615");

        assertEquals("00000002ffffffffffffffff", bin2hex($memo->toXdr()->encode()));
        assertEquals("18446744073709551615", $memo->getIdAsString());

        $parsed = Memo::fromXdr($memo->toXdr());
        assertEquals("18446744073709551615", $parsed->getValue());
        assertEquals("18446744073709551615", $parsed->getIdAsString());
    }

    public function testIdMemoUpperRangeDecodesAsUnsignedString()
    {
        // id = 2^64 - 5, which does not fit in a signed PHP int
        $bytes = "\x00\x00\x00\x02\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFB";
        $memo = Memo::fromXdr(\Soneso\StellarSDK\Xdr\XdrMemo::decode(new \Soneso\StellarSDK\Xdr\XdrBuffer($bytes)));

        assertEquals("18446744073709551611", $memo->getValue());
        assertEquals("18446744073709551611", $memo->getIdAsString());
        assertEquals("18446744073709551611", $memo->valueAsString());
        assertEquals($bytes, $memo->toXdr()->encode());
    }

    public function testIdMemoSmallStringRoundTrip()
    {
        $memo = Memo::id("123");
        $parsed = Memo::fromXdr($memo->toXdr());

        assertEquals(123, $parsed->getValue());
        assertEquals("123", $parsed->getIdAsString());
    }

    public function testIdMemoRejectsNegativeIntInFactory()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("value cannot be negative");
        Memo::id(-5);
    }

    public function testIdMemoRejectsNegativeIntInConstructor()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("value cannot be negative");
        new Memo(Memo::MEMO_TYPE_ID, -5);
    }

    public function testIdMemoRejectsStringAboveUint64Max()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("value cannot be larger than 18446744073709551615");
        Memo::id("18446744073709551616");
    }

    public function testIdMemoRejectsNonIntNonStringValue()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("id memo value must be an int or an unsigned decimal string");
        new Memo(Memo::MEMO_TYPE_ID, 1.5);
    }

    public function testIdMemoRejectsMalformedStrings()
    {
        foreach (["abc", "-5", "01", "", "1.5"] as $bad) {
            try {
                Memo::id($bad);
                $this->fail("expected rejection of id memo value: " . $bad);
            } catch (InvalidArgumentException $e) {
                assertNotNull($e->getMessage());
            }
        }
    }

    public function testGetIdAsString()
    {
        assertEquals("12345", Memo::id(12345)->getIdAsString());
        assertNull(Memo::text("hello")->getIdAsString());
        assertNull(Memo::none()->getIdAsString());
    }

    public function testIdMemoIntStringCrossoverBoundary()
    {
        // PHP_INT_MAX as a string stays representable without wrapping.
        $maxInt = Memo::id("9223372036854775807");
        assertEquals("000000027fffffffffffffff", bin2hex($maxInt->toXdr()->encode()));
        assertEquals(PHP_INT_MAX, Memo::fromXdr($maxInt->toXdr())->getValue());

        // PHP_INT_MAX + 1 is the first value that requires the string representation.
        $aboveMax = Memo::id("9223372036854775808");
        assertEquals("000000028000000000000000", bin2hex($aboveMax->toXdr()->encode()));
        assertEquals("9223372036854775808", Memo::fromXdr($aboveMax->toXdr())->getValue());
    }
}
