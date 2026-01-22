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
}
