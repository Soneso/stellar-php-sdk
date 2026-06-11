<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use InvalidArgumentException;
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
}
