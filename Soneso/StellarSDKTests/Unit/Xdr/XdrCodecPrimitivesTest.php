<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Xdr\XdrDecoder;
use Soneso\StellarSDK\Xdr\XdrEncoder;

/**
 * Tests for the low-level XDR primitive codec (XdrDecoder / XdrEncoder).
 */
class XdrCodecPrimitivesTest extends TestCase
{
    public function testBooleanDecodesTrueAndFalse(): void
    {
        // XDR boolean is a 4-byte big-endian uint32: 0 = false, 1 = true.
        $this->assertFalse(XdrDecoder::boolean("\x00\x00\x00\x00"));
        $this->assertTrue(XdrDecoder::boolean("\x00\x00\x00\x01"));
    }

    public function testBooleanRejectsNonBooleanValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unexpected XDR for a boolean value');
        XdrDecoder::boolean("\x00\x00\x00\x02");
    }

    public function testUnsignedInteger32RoundTrip(): void
    {
        foreach ([0, 1, 255, 65535, 2147483648, 4294967295] as $v) {
            $this->assertSame($v, XdrDecoder::unsignedInteger(XdrEncoder::unsignedInteger32($v)));
        }
    }

    public function testSignedInteger32RoundTrip(): void
    {
        foreach ([0, 1, -1, 2147483647, -2147483648] as $v) {
            $this->assertSame($v, XdrDecoder::signedInteger(XdrEncoder::integer32($v)));
        }
    }

    public function testInteger64RoundTrip(): void
    {
        foreach ([0, 1, -1, PHP_INT_MAX, PHP_INT_MIN] as $v) {
            $this->assertSame($v, XdrDecoder::signedInteger64(XdrEncoder::integer64($v)));
        }
        foreach ([0, 1, 9223372036854775807] as $v) {
            $this->assertSame($v, XdrDecoder::unsignedInteger64(XdrEncoder::unsignedInteger64($v)));
        }
    }

    public function testOpaqueFixedRejectsOverlongValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        XdrEncoder::opaqueFixed("\x01\x02\x03\x04\x05", 4);
    }

    public function testOpaqueFixedPadsToExpectedLength(): void
    {
        // 3 bytes padded to a 4-byte boundary (XDR pads to multiples of 4).
        $encoded = XdrEncoder::opaqueFixed("\x01\x02\x03", 3);
        $this->assertSame(4, strlen($encoded));
        $this->assertSame("\x01\x02\x03\x00", $encoded);
    }

    public function testOpaqueFixedPadsShortValueToFullDeclaredLength(): void
    {
        // A short value with padUnexpectedLength pads with NULs to exactly
        // the declared fixed length, including the empty value: a decoded
        // all-NUL fixed opaque stored as '' must re-encode to its full
        // declared width, not to zero bytes.
        $this->assertSame(str_repeat("\x00", 12), XdrEncoder::opaqueFixed('', 12, true));
        $this->assertSame("AB" . str_repeat("\x00", 10), XdrEncoder::opaqueFixed('AB', 12, true));
        $this->assertSame(str_repeat("\x00", 4), XdrEncoder::opaqueFixed('', 4, true));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function variableLengthDecoders(): array
    {
        return ['opaqueVariable' => ['opaqueVariable'], 'string' => ['string']];
    }

    #[DataProvider('variableLengthDecoders')]
    public function testVariableLengthDecodeReturnsTheValueAtTheStart(string $method): void
    {
        // Bytes after the padding, such as the following field, are not part of the value.
        $this->assertSame('abc', XdrDecoder::{$method}("\x00\x00\x00\x03abc\x00\xCA\xFE\xBA\xBE"));
        $this->assertSame('', XdrDecoder::{$method}("\x00\x00\x00\x00"));
        $this->assertSame('abcd', XdrDecoder::{$method}("\x00\x00\x00\x04abcd"));
        $this->assertSame("\x00\xFF\x80", XdrDecoder::{$method}("\x00\x00\x00\x03\x00\xFF\x80\x00"));
    }

    #[DataProvider('variableLengthDecoders')]
    public function testVariableLengthDecodeRejectsNonZeroPadding(string $method): void
    {
        $cases = [
            ["\x00\x00\x00\x01a\x01\x00\x00", 'XDR padding must be zero, byte 0x01 found at offset 5'],
            ["\x00\x00\x00\x01a\x00\x01\x00", 'XDR padding must be zero, byte 0x01 found at offset 6'],
            ["\x00\x00\x00\x01a\x00\x00\x01", 'XDR padding must be zero, byte 0x01 found at offset 7'],
            ["\x00\x00\x00\x03abc\x01", 'XDR padding must be zero, byte 0x01 found at offset 7'],
            ["\x00\x00\x00\x03abc\x80", 'XDR padding must be zero, byte 0x80 found at offset 7'],
            ["\x00\x00\x00\x03abc\xFF", 'XDR padding must be zero, byte 0xFF found at offset 7'],
        ];
        foreach ($cases as [$bytes, $message]) {
            $this->assertDecodeRejected($method, $bytes, $message);
        }
    }

    #[DataProvider('variableLengthDecoders')]
    public function testVariableLengthDecodeRejectsCutOffInput(string $method): void
    {
        // No length prefix, half a length prefix, a value cut off inside its data, missing padding.
        foreach (['', "\x00\x00", "\x00\x00\x00\x05ab", "\x00\x00\x00\x03abc"] as $bytes) {
            $this->assertDecodeRejected($method, $bytes, 'Unexpected end of XDR data');
        }
    }

    /**
     * Decodes $bytes with the named XdrDecoder static and asserts the exact exception message.
     */
    private function assertDecodeRejected(string $method, string $bytes, string $message): void
    {
        try {
            XdrDecoder::{$method}($bytes);
            $this->fail(sprintf('XdrDecoder::%s() accepted %s', $method, bin2hex($bytes)));
        } catch (InvalidArgumentException $e) {
            $this->assertSame($message, $e->getMessage());
        }
    }
}
