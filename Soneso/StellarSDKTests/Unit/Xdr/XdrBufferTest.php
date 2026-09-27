<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Xdr\XdrBuffer;

/**
 * Tests for the array count, extension point and optional presence reads of XdrBuffer.
 */
class XdrBufferTest extends TestCase
{
    public function testReadArrayLengthRejectsCountWithAllBitsSetAsNegative(): void
    {
        // 0xffffffff is -1 as a signed int32.
        $buffer = new XdrBuffer("\xff\xff\xff\xff" . str_repeat("\x00", 8));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('XDR array count cannot be negative, got -1');
        $buffer->readArrayLength();
    }

    public function testReadArrayLengthRejectsCountWithOnlyHighBitSetAsNegative(): void
    {
        // 0x80000000 is the smallest signed int32.
        $buffer = new XdrBuffer("\x80\x00\x00\x00" . str_repeat("\x00", 8));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('XDR array count cannot be negative, got -2147483648');
        $buffer->readArrayLength();
    }

    public function testReadArrayLengthRejectsCountAboveQuarterOfRemainingBytes(): void
    {
        $buffer = new XdrBuffer(pack('N', 3) . str_repeat("\x00", 8));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('XDR array count 3 exceeds the maximum of 2 for the 8 remaining bytes');
        $buffer->readArrayLength();
    }

    public function testReadArrayLengthRoundsTheMaximumDown(): void
    {
        // 11 remaining bytes hold at most two 4-byte elements.
        $buffer = new XdrBuffer(pack('N', 3) . str_repeat("\x00", 11));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('XDR array count 3 exceeds the maximum of 2 for the 11 remaining bytes');
        $buffer->readArrayLength();
    }

    public function testReadArrayLengthAcceptsCountAtTheBoundary(): void
    {
        $buffer = new XdrBuffer(pack('N', 2) . pack('N', 7) . pack('N', 9));

        $this->assertSame(2, $buffer->readArrayLength());
        $this->assertSame(7, $buffer->readInteger32());
        $this->assertSame(9, $buffer->readInteger32());
    }

    public function testReadArrayLengthAcceptsCountBelowTheBoundaryWithUnalignedTail(): void
    {
        $buffer = new XdrBuffer(pack('N', 2) . str_repeat("\x00", 11));

        $this->assertSame(2, $buffer->readArrayLength());
    }

    public function testReadArrayLengthAcceptsZeroAtTheEndOfTheBuffer(): void
    {
        $buffer = new XdrBuffer(pack('N', 0));

        $this->assertSame(0, $buffer->readArrayLength());
    }

    public function testReadArrayLengthRejectsTruncatedCount(): void
    {
        $buffer = new XdrBuffer("\x00\x00\x01");

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unexpected end of XDR data');
        $buffer->readArrayLength();
    }

    public function testReadExtensionPointAcceptsZero(): void
    {
        $buffer = new XdrBuffer(pack('N', 0) . pack('N', 5));

        $buffer->readExtensionPoint('XdrExample');
        $this->assertSame(5, $buffer->readInteger32());
    }

    public function testReadExtensionPointRejectsNonZero(): void
    {
        $buffer = new XdrBuffer(pack('N', 1));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('XdrExample extension point must be 0, got 1');
        $buffer->readExtensionPoint('XdrExample');
    }

    public function testReadExtensionPointRejectsNegative(): void
    {
        $buffer = new XdrBuffer("\xff\xff\xff\xff");

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('XdrExample extension point must be 0, got -1');
        $buffer->readExtensionPoint('XdrExample');
    }

    public function testReadOptionalPresenceReturnsFalseForZero(): void
    {
        $buffer = new XdrBuffer(pack('N', 0) . pack('N', 5));

        $this->assertFalse($buffer->readOptionalPresence('XdrExample.field'));
        $this->assertSame(5, $buffer->readInteger32());
    }

    public function testReadOptionalPresenceReturnsTrueForOne(): void
    {
        $buffer = new XdrBuffer(pack('N', 1) . pack('N', 5));

        $this->assertTrue($buffer->readOptionalPresence('XdrExample.field'));
        $this->assertSame(5, $buffer->readInteger32());
    }

    public function testReadOptionalPresenceRejectsTwo(): void
    {
        $this->assertOptionalPresenceRejected(
            pack('N', 2),
            'XdrExample.field presence flag must be 0 or 1, got 2'
        );
    }

    public function testReadOptionalPresenceRejectsNegative(): void
    {
        // 0xffffffff is -1 as a signed int32.
        $this->assertOptionalPresenceRejected(
            "\xff\xff\xff\xff",
            'XdrExample.field presence flag must be 0 or 1, got -1'
        );
    }

    public function testReadOptionalPresenceRejectsTruncatedWord(): void
    {
        $this->assertOptionalPresenceRejected("\x00\x00\x00", 'Unexpected end of XDR data');
    }

    /**
     * Reads a presence flag from the given bytes and asserts the exact exception message.
     */
    private function assertOptionalPresenceRejected(string $bytes, string $message): void
    {
        $buffer = new XdrBuffer($bytes);
        try {
            $buffer->readOptionalPresence('XdrExample.field');
        } catch (InvalidArgumentException $e) {
            $this->assertSame($message, $e->getMessage());
            return;
        }
        $this->fail('readOptionalPresence accepted ' . bin2hex($bytes));
    }
}
