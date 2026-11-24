<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use InvalidArgumentException;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrBumpSequenceOperation;
use Soneso\StellarSDK\Xdr\XdrEncoder;
use Soneso\StellarSDK\Xdr\XdrEnvelopeType;
use Soneso\StellarSDK\Xdr\XdrMemo;
use Soneso\StellarSDK\Xdr\XdrMemoType;
use Soneso\StellarSDK\Xdr\XdrMuxedAccount;
use Soneso\StellarSDK\Xdr\XdrOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValType;
use Soneso\StellarSDK\Xdr\XdrSequenceNumber;
use Soneso\StellarSDK\Xdr\XdrSignerKey;
use Soneso\StellarSDK\Xdr\XdrSignerKeyType;
use Soneso\StellarSDK\Xdr\XdrTransaction;
use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;
use Soneso\StellarSDK\Xdr\XdrTransactionV1Envelope;

/**
 * Tests for the array count, extension point, optional presence, signed 64-bit and variable-length
 * reads of XdrBuffer, and for its padding check as generated decoders reach it.
 */
class XdrBufferTest extends TestCase
{
    public function testGetPositionTracksConsumedBytes(): void
    {
        $buffer = new XdrBuffer(pack('N', 7) . XdrEncoder::string('abc'));
        $this->assertTrue(method_exists($buffer, 'getPosition'));
        $this->assertSame(0, $buffer->getPosition());
        $this->assertSame(7, $buffer->readUnsignedInteger32());
        $this->assertSame(4, $buffer->getPosition());
        $this->assertSame('abc', $buffer->readString());
        $this->assertSame(12, $buffer->getPosition());
        $buffer->assertFullyConsumed('test');
    }

    public function testAssertFullyConsumedAtEnd(): void
    {
        $buffer = new XdrBuffer(pack('N', 7));
        $this->assertSame(7, $buffer->readInteger32());
        $buffer->assertFullyConsumed('XdrExample');
        $empty = new XdrBuffer('');
        $empty->assertFullyConsumed('XdrEmpty');
    }

    public function testAssertFullyConsumedRejectsUnreadBytes(): void
    {
        $buffer = new XdrBuffer(pack('N', 7) . "\x01\x02\x03");
        $this->assertSame(7, $buffer->readInteger32());
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('XdrExample leaves 3 of 7 bytes unread');
        $buffer->assertFullyConsumed('XdrExample');
    }

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

    public function testReadBoundedArrayLengthAcceptsTheDeclaredMaximum(): void
    {
        $this->assertSame(100, (new XdrBuffer(pack('N', 100) . str_repeat("\x00", 400)))->readBoundedArrayLength(100));
        $this->assertSame(101, (new XdrBuffer(pack('N', 101) . str_repeat("\x00", 404)))->readArrayLength());
    }

    public function testReadBoundedArrayLengthRejectsCountAboveTheDeclaredMaximum(): void
    {
        $buffer = new XdrBuffer(pack('N', 101) . str_repeat("\x00", 404));
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/^XDR array count 101 exceeds the declared maximum of 100$/');
        $buffer->readBoundedArrayLength(100);
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

    public function testReadBigInteger64ReadsTwosComplement(): void
    {
        $cases = [
            ['ffffffffffffffff', '-1'],
            ['8000000000000000', '-9223372036854775808'],
            ['7fffffffffffffff', '9223372036854775807'],
        ];
        foreach ($cases as [$hex, $decimal]) {
            $value = (new XdrBuffer(hex2bin($hex)))->readBigInteger64();
            $this->assertSame($decimal, $value->toString());
            $this->assertSame($hex, bin2hex(XdrEncoder::bigInteger64($value)));
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function variableLengthReaders(): array
    {
        return ['readOpaqueVariable' => ['readOpaqueVariable'], 'readString' => ['readString']];
    }

    #[DataProvider('variableLengthReaders')]
    public function testVariableLengthReadConsumesZeroPadding(string $reader): void
    {
        $cases = [['', ''], ['a', "\x00\x00\x00"], ['ab', "\x00\x00"], ['abc', "\x00"], ['abcd', '']];
        foreach ($cases as [$value, $padding]) {
            $buffer = new XdrBuffer(pack('N', strlen($value)) . $value . $padding . pack('N', 0xCAFEBABE));
            $this->assertSame($value, $buffer->{$reader}());
            $this->assertSame(0xCAFEBABE, $buffer->readUnsignedInteger32());
            $buffer->assertFullyConsumed('XdrExample');
        }
    }

    #[DataProvider('variableLengthReaders')]
    public function testVariableLengthReadRejectsEachNonZeroPaddingByte(string $reader): void
    {
        // A leading word puts the value at offset 8, so the offsets count from the start of the input.
        $cases = [
            ['a', "\x01\x00\x00", 9],
            ['a', "\x00\x01\x00", 10],
            ['a', "\x00\x00\x01", 11],
            ['ab', "\x01\x00", 10],
            ['ab', "\x00\x01", 11],
            ['abc', "\x01", 11],
        ];
        foreach ($cases as [$value, $padding, $offset]) {
            $buffer = new XdrBuffer(pack('N', 7) . pack('N', strlen($value)) . $value . $padding);
            $this->assertSame(7, $buffer->readUnsignedInteger32());
            try {
                $buffer->{$reader}();
                $this->fail(sprintf('%s accepted the padding %s', $reader, bin2hex($padding)));
            } catch (InvalidArgumentException $e) {
                $this->assertSame("XDR padding must be zero, byte 0x01 found at offset {$offset}", $e->getMessage());
            }
        }
    }

    #[DataProvider('variableLengthReaders')]
    public function testVariableLengthReadRejectsMissingPadding(string $reader): void
    {
        $buffer = new XdrBuffer(pack('N', 3) . 'abc');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/^Unexpected end of XDR data$/');
        $buffer->{$reader}();
    }

    public function testVariableLengthReadChecksTheDeclaredMaximumBeforeThePadding(): void
    {
        $bytes = pack('N', 5) . 'abcde' . "\x01\x00\x00";
        $cases = [
            'readOpaqueVariable' => 'length of 5 exceeds max length of 4',
            'readString' => 'maxLength of 4 exceeded (string is 5 bytes)',
        ];
        foreach ($cases as $reader => $message) {
            try {
                (new XdrBuffer($bytes))->{$reader}(4);
                $this->fail($reader . ' accepted a 5-byte value against a maximum of 4');
            } catch (InvalidArgumentException $e) {
                $this->assertSame($message, $e->getMessage());
            }
        }
    }

    public function testMemoTextDecodesOnlyWithZeroPadding(): void
    {
        // MEMO_TEXT, length 3, "abc" and one padding byte at offset 11.
        $bytes = pack('N', XdrMemoType::MEMO_TEXT) . pack('N', 3) . 'abc' . "\x00";
        $this->assertSame('abc', XdrMemo::decode(new XdrBuffer($bytes))->text);
        $this->assertOnlyZeroPaddingDecodes($bytes, 11,
            fn (string $b): string => XdrMemo::decode(new XdrBuffer($b))->encode());
    }

    public function testTransactionEnvelopeDecodesOnlyWithZeroPaddingInItsMemo(): void
    {
        $memo = new XdrMemo(new XdrMemoType(XdrMemoType::MEMO_TEXT));
        $memo->text = 'abc';
        $body = new XdrOperationBody(new XdrOperationType(XdrOperationType::BUMP_SEQUENCE));
        $body->setBumpSequenceOp(new XdrBumpSequenceOperation(new XdrSequenceNumber(new BigInteger(100))));
        $transaction = new XdrTransaction(new XdrMuxedAccount(str_repeat("\x11", 32)),
            new XdrSequenceNumber(new BigInteger(12345)), [new XdrOperation($body)], 100, $memo);
        $envelope = new XdrTransactionEnvelope(new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX));
        $envelope->setV1(new XdrTransactionV1Envelope($transaction, []));
        $bytes = $envelope->encode();
        $memoBytes = $memo->encode();
        $this->assertSame(1, substr_count($bytes, $memoBytes));
        $this->assertOnlyZeroPaddingDecodes($bytes, strpos($bytes, $memoBytes) + 11,
            fn (string $b): string => base64_decode(
                XdrTransactionEnvelope::fromEnvelopeBase64XdrString(base64_encode($b))->toBase64Xdr()));
    }

    public function testSignedPayloadSignerKeyDecodesOnlyWithZeroPadding(): void
    {
        // Type, 32-byte signer, length 3, three payload bytes and one padding byte at offset 43.
        $bytes = pack('N', XdrSignerKeyType::SIGNER_KEY_TYPE_ED25519_SIGNED_PAYLOAD) . str_repeat("\x22", 32)
            . pack('N', 3) . "\x01\x02\x03" . "\x00";
        $this->assertOnlyZeroPaddingDecodes($bytes, 43,
            fn (string $b): string => XdrSignerKey::decode(new XdrBuffer($b))->encode());
    }

    public function testScValBytesAndStringDecodeOnlyWithZeroPadding(): void
    {
        // Type, length 2, two value bytes and padding at offsets 10 and 11.
        foreach ([XdrSCValType::SCV_BYTES => 10, XdrSCValType::SCV_STRING => 11] as $type => $offset) {
            $bytes = pack('N', $type) . pack('N', 2) . 'hi' . "\x00\x00";
            $this->assertOnlyZeroPaddingDecodes($bytes, $offset,
                fn (string $b): string => XdrSCVal::decode(new XdrBuffer($b))->encode());
        }
    }

    /**
     * Asserts that $bytes, which carry a zero padding byte at $offset, round-trip through
     * $roundTrip, and that the same bytes with 0x01 at $offset fail with the padding message.
     *
     * @param callable(string): string $roundTrip decodes the bytes and encodes the decoded value
     */
    private function assertOnlyZeroPaddingDecodes(string $bytes, int $offset, callable $roundTrip): void
    {
        $this->assertSame("\x00", $bytes[$offset]);
        $this->assertSame(bin2hex($bytes), bin2hex($roundTrip($bytes)));
        $bytes[$offset] = "\x01";
        try {
            $roundTrip($bytes);
            $this->fail(sprintf('Decoded 0x01 padding at offset %d of %s', $offset, bin2hex($bytes)));
        } catch (InvalidArgumentException $e) {
            $this->assertSame("XDR padding must be zero, byte 0x01 found at offset {$offset}", $e->getMessage());
        }
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
