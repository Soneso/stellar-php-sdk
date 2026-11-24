<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use InvalidArgumentException;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Account;
use Soneso\StellarSDK\BumpSequenceOperation;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\LedgerBounds;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\TransactionPreconditions;
use Soneso\StellarSDK\Xdr\XdrDecoder;
use Soneso\StellarSDK\Xdr\XdrEncoder;
use Soneso\StellarSDK\Xdr\XdrSCVal;

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

    /** @return array<string, array{string, int|BigInteger, string}> */
    public static function integerWriterBoundaries(): array
    {
        return [
            'int32 minimum' => ['integer32', -2147483648, '80000000'],
            'int32 maximum' => ['integer32', 2147483647, '7fffffff'],
            'uint32 minimum' => ['unsignedInteger32', 0, '00000000'],
            'uint32 maximum' => ['unsignedInteger32', 4294967295, 'ffffffff'],
            'int64 minimum' => ['bigInteger64', new BigInteger('-9223372036854775808'), '8000000000000000'],
            'int64 maximum' => ['bigInteger64', new BigInteger('9223372036854775807'), '7fffffffffffffff'],
            'int64 negative one' => ['bigInteger64', new BigInteger('-1'), 'ffffffffffffffff'],
            'int64 zero' => ['bigInteger64', new BigInteger('0'), '0000000000000000'],
            'int64 positive sign byte' => ['bigInteger64', new BigInteger('128'), '0000000000000080'],
            'uint64 minimum' => ['unsignedBigInteger64', new BigInteger('0'), '0000000000000000'],
            'uint64 maximum' => ['unsignedBigInteger64', new BigInteger('18446744073709551615'), 'ffffffffffffffff'],
            'uint64 high bit' => ['unsignedBigInteger64', new BigInteger('9223372036854775808'), '8000000000000000'],
        ];
    }

    #[DataProvider('integerWriterBoundaries')]
    public function testIntegerWriterEncodesBoundaries(string $writer, int|BigInteger $value, string $hex): void
    {
        $this->assertSame($hex, bin2hex(XdrEncoder::{$writer}($value)));
    }

    public function testBigInteger64PreservesLeadingZeroByteHandling(): void
    {
        $value = new BigInteger('1');
        $value->setPrecision(72);
        $this->assertSame('0000000000000001', bin2hex(XdrEncoder::bigInteger64($value)));
    }

    /** @return array<string, array{string, int|BigInteger, string}> */
    public static function outOfRangeIntegers(): array
    {
        return [
            'int32 below minimum' => ['integer32', -2147483649, 'integer32 value -2147483649 is outside [-2147483648, 2147483647]'],
            'int32 above maximum' => ['integer32', 2147483648, 'integer32 value 2147483648 is outside [-2147483648, 2147483647]'],
            'uint32 below minimum' => ['unsignedInteger32', -1, 'unsignedInteger32 value -1 is outside [0, 4294967295]'],
            'uint32 above maximum' => ['unsignedInteger32', 4294967296, 'unsignedInteger32 value 4294967296 is outside [0, 4294967295]'],
            'int64 below minimum' => ['bigInteger64', new BigInteger('-9223372036854775809'), 'bigInteger64 value -9223372036854775809 is outside [-9223372036854775808, 9223372036854775807]'],
            'int64 above maximum' => ['bigInteger64', new BigInteger('9223372036854775808'), 'bigInteger64 value 9223372036854775808 is outside [-9223372036854775808, 9223372036854775807]'],
            'int64 unsigned maximum' => ['bigInteger64', new BigInteger('18446744073709551615'), 'bigInteger64 value 18446744073709551615 is outside [-9223372036854775808, 9223372036854775807]'],
            'uint64 below minimum' => ['unsignedBigInteger64', new BigInteger('-1'), 'unsignedBigInteger64 value -1 is outside [0, 18446744073709551615]'],
            'uint64 above maximum' => ['unsignedBigInteger64', new BigInteger('18446744073709551616'), 'unsignedBigInteger64 value 18446744073709551616 is outside [0, 18446744073709551615]'],
            'uint64 negative high bit' => ['unsignedBigInteger64', new BigInteger('-9223372036854775808'), 'unsignedBigInteger64 value -9223372036854775808 is outside [0, 18446744073709551615]'],
        ];
    }

    #[DataProvider('outOfRangeIntegers')]
    public function testIntegerWriterRejectsOutOfRangeValues(string $writer, int|BigInteger $value, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        XdrEncoder::{$writer}($value);
    }

    /** @return array<string, array{int|string, string}> */
    public static function unsignedInteger64Inputs(): array
    {
        return [
            'int minimum' => [PHP_INT_MIN, '8000000000000000'],
            'int maximum' => [PHP_INT_MAX, '7fffffffffffffff'],
            'negative int' => [-1, 'ffffffffffffffff'],
            'zero int' => [0, '0000000000000000'],
            'string minimum' => ['-9223372036854775808', '8000000000000000'],
            'string maximum' => ['9223372036854775807', '7fffffffffffffff'],
            'negative string' => ['-1', 'ffffffffffffffff'],
            'explicit positive sign' => ['+1', '0000000000000001'],
            'leading zeroes' => ['0000000000000000000000000000000000000001', '0000000000000001'],
            'negative zero' => ['-000', '0000000000000000'],
        ];
    }

    #[DataProvider('unsignedInteger64Inputs')]
    public function testUnsignedInteger64PreservesIntBitPatterns(int|string $value, string $hex): void
    {
        $this->assertSame($hex, bin2hex(XdrEncoder::unsignedInteger64($value)));
    }

    /** @return array<string, array{mixed, string}> */
    public static function invalidUnsignedInteger64Inputs(): array
    {
        return [
            'below signed minimum' => ['-9223372036854775809', 'unsignedInteger64 value -9223372036854775809 is outside [-9223372036854775808, 9223372036854775807]'],
            'above signed maximum' => ['9223372036854775808', 'unsignedInteger64 value 9223372036854775808 is outside [-9223372036854775808, 9223372036854775807]'],
            'unsigned maximum' => ['18446744073709551615', 'unsignedInteger64 value 18446744073709551615 is outside [-9223372036854775808, 9223372036854775807]'],
            'positive sign overflow' => ['+9223372036854775808', 'unsignedInteger64 value +9223372036854775808 is outside [-9223372036854775808, 9223372036854775807]'],
            'leading zero overflow' => ['0009223372036854775808', 'unsignedInteger64 value 0009223372036854775808 is outside [-9223372036854775808, 9223372036854775807]'],
            'empty string' => ['', 'unsignedInteger64 value  is not an int or a decimal integer string in [-9223372036854775808, 9223372036854775807]'],
            'whitespace' => [' 1', 'unsignedInteger64 value  1 is not an int or a decimal integer string in [-9223372036854775808, 9223372036854775807]'],
            'trailing newline' => ["1\n", "unsignedInteger64 value 1\n is not an int or a decimal integer string in [-9223372036854775808, 9223372036854775807]"],
            'fraction string' => ['1.5', 'unsignedInteger64 value 1.5 is not an int or a decimal integer string in [-9223372036854775808, 9223372036854775807]'],
            'exponent string' => ['1e2', 'unsignedInteger64 value 1e2 is not an int or a decimal integer string in [-9223372036854775808, 9223372036854775807]'],
            'hex string' => ['0xff', 'unsignedInteger64 value 0xff is not an int or a decimal integer string in [-9223372036854775808, 9223372036854775807]'],
            'sign only' => ['-', 'unsignedInteger64 value - is not an int or a decimal integer string in [-9223372036854775808, 9223372036854775807]'],
            'text' => ['invalid', 'unsignedInteger64 value invalid is not an int or a decimal integer string in [-9223372036854775808, 9223372036854775807]'],
            'null' => [null, 'unsignedInteger64 value null is not an int or a decimal integer string in [-9223372036854775808, 9223372036854775807]'],
            'true' => [true, 'unsignedInteger64 value bool is not an int or a decimal integer string in [-9223372036854775808, 9223372036854775807]'],
            'false' => [false, 'unsignedInteger64 value bool is not an int or a decimal integer string in [-9223372036854775808, 9223372036854775807]'],
            'integral float' => [1.0, 'unsignedInteger64 value float is not an int or a decimal integer string in [-9223372036854775808, 9223372036854775807]'],
            'fractional float' => [1.5, 'unsignedInteger64 value float is not an int or a decimal integer string in [-9223372036854775808, 9223372036854775807]'],
            'infinity' => [INF, 'unsignedInteger64 value float is not an int or a decimal integer string in [-9223372036854775808, 9223372036854775807]'],
            'not a number' => [NAN, 'unsignedInteger64 value float is not an int or a decimal integer string in [-9223372036854775808, 9223372036854775807]'],
            'array' => [[], 'unsignedInteger64 value array is not an int or a decimal integer string in [-9223372036854775808, 9223372036854775807]'],
            'object' => [new BigInteger('1'), 'unsignedInteger64 value phpseclib3\\Math\\BigInteger is not an int or a decimal integer string in [-9223372036854775808, 9223372036854775807]'],
        ];
    }

    #[DataProvider('invalidUnsignedInteger64Inputs')]
    public function testUnsignedInteger64RejectsInvalidArguments(mixed $value, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        XdrEncoder::unsignedInteger64($value);
    }

    /** @return array<string, array{string, mixed, string}> */
    public static function rejectedIntegerMessageValues(): array
    {
        return [
            'int64 long positive BigInteger' => ['bigInteger64', new BigInteger(str_repeat('9', 81)), 'bigInteger64 value 99999999999999999999999999999999999999999999999999999999999999999999999999999999 is outside [-9223372036854775808, 9223372036854775807]'],
            'int64 long negative BigInteger' => ['bigInteger64', new BigInteger('-' . str_repeat('9', 80)), 'bigInteger64 value -9999999999999999999999999999999999999999999999999999999999999999999999999999999 is outside [-9223372036854775808, 9223372036854775807]'],
            'uint64 long BigInteger' => ['unsignedBigInteger64', new BigInteger(str_repeat('9', 81)), 'unsignedBigInteger64 value 99999999999999999999999999999999999999999999999999999999999999999999999999999999 is outside [0, 18446744073709551615]'],
            'signed numeric string' => ['integer32', '2147483648', 'integer32 value 2147483648 is outside [-2147483648, 2147483647]'],
            'unsigned numeric string' => ['unsignedInteger32', '4294967296', 'unsignedInteger32 value 4294967296 is outside [0, 4294967295]'],
            'signed float' => ['integer32', INF, 'integer32 value float is outside [-2147483648, 2147483647]'],
            'unsigned float' => ['unsignedInteger32', INF, 'unsignedInteger32 value float is outside [0, 4294967295]'],
            'signed array' => ['integer32', [], 'integer32 value array is outside [-2147483648, 2147483647]'],
            'unsigned array' => ['unsignedInteger32', [], 'unsignedInteger32 value array is outside [0, 4294967295]'],
            'signed long string' => ['integer32', str_repeat('9', 81), 'integer32 value 99999999999999999999999999999999999999999999999999999999999999999999999999999999 is outside [-2147483648, 2147483647]'],
            'unsigned long string' => ['unsignedInteger32', str_repeat('9', 81), 'unsignedInteger32 value 99999999999999999999999999999999999999999999999999999999999999999999999999999999 is outside [0, 4294967295]'],
            'uint64 long range string' => ['unsignedInteger64', str_repeat('9', 81), 'unsignedInteger64 value 99999999999999999999999999999999999999999999999999999999999999999999999999999999 is outside [-9223372036854775808, 9223372036854775807]'],
            'uint64 long syntax string' => ['unsignedInteger64', str_repeat('x', 81), 'unsignedInteger64 value xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx is not an int or a decimal integer string in [-9223372036854775808, 9223372036854775807]'],
        ];
    }

    #[DataProvider('rejectedIntegerMessageValues')]
    public function testRejectedIntegerMessageValue(string $writer, mixed $value, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        try {
            XdrEncoder::{$writer}($value);
        } catch (InvalidArgumentException $e) {
            $this->assertSame($message, $e->getMessage());
            throw $e;
        }
    }

    /** @return array<string, array{string, string}> */
    public static function redactedIntegerWriters(): array
    {
        return [
            'signed 32' => ['integer32', ' is outside [-2147483648, 2147483647]'],
            'unsigned 32' => ['unsignedInteger32', ' is outside [0, 4294967295]'],
            'unsigned 64' => ['unsignedInteger64', ' is not an int or a decimal integer string in [-9223372036854775808, 9223372036854775807]'],
        ];
    }

    #[DataProvider('redactedIntegerWriters')]
    public function testIntegerMessageRedactsBeforeTruncating(string $writer, string $suffix): void
    {
        $value = str_repeat('9', 70) . ':' . KeyPair::random()->getSecretSeed();
        $message = $writer . ' value 9999999999999999999999999999999999999999999999999999999999999999999999:a secret ' . $suffix;
        try {
            XdrEncoder::{$writer}($value);
            $this->fail('The writer must reject the invalid input.');
        } catch (InvalidArgumentException $e) {
            $this->assertTrue($message === $e->getMessage(), 'The message must redact before truncating to 80 bytes.');
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage($message);
            throw $e;
        }
    }

    public function testUnsignedInteger64RejectsResource(): void
    {
        $value = fopen('php://memory', 'r+');
        try {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('unsignedInteger64 value resource (stream) is not an int or a decimal integer string in [-9223372036854775808, 9223372036854775807]');
            XdrEncoder::unsignedInteger64($value);
        } finally {
            fclose($value);
        }
    }

    public function testOverflowingTransactionFeeFailsAtEncoding(): void
    {
        $account = new Account(KeyPair::random()->getAccountId(), new BigInteger('1'));
        $transaction = (new TransactionBuilder($account))
            ->addOperation(new BumpSequenceOperation(new BigInteger('2')))
            ->setMaxOperationFee(4294967296)
            ->build();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unsignedInteger32 value 4294967296 is outside [0, 4294967295]');
        $transaction->toXdrBase64();
    }

    public function testNegativeTransactionFeeFailsAtEncoding(): void
    {
        $account = new Account(KeyPair::random()->getAccountId(), new BigInteger('1'));
        $transaction = (new TransactionBuilder($account))
            ->addOperation(new BumpSequenceOperation(new BigInteger('2')))
            ->build();
        $transaction->setFee(-1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unsignedInteger32 value -1 is outside [0, 4294967295]');
        $transaction->toXdr()->encode();
    }

    public function testOverflowingU32SCValFailsAtEncoding(): void
    {
        $value = XdrSCVal::forU32(4294967296);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unsignedInteger32 value 4294967296 is outside [0, 4294967295]');
        $value->encode();
    }

    public function testOverflowingI32SCValFailsAtEncoding(): void
    {
        $value = XdrSCVal::forI32(2147483648);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('integer32 value 2147483648 is outside [-2147483648, 2147483647]');
        $value->encode();
    }

    public function testOverflowingLedgerBoundsFailAtEncoding(): void
    {
        $bounds = new LedgerBounds(4294967296, 4294967296);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unsignedInteger32 value 4294967296 is outside [0, 4294967295]');
        $bounds->toXdr()->encode();
    }

    public function testOverflowingLedgerGapFailsAtEncoding(): void
    {
        $preconditions = new TransactionPreconditions();
        $preconditions->setMinSeqLedgerGap(4294967296);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unsignedInteger32 value 4294967296 is outside [0, 4294967295]');
        $preconditions->toXdr()->encode();
    }

    public function testOverflowingTransactionSequenceFailsAtEncoding(): void
    {
        $account = new Account(KeyPair::random()->getAccountId(), new BigInteger('9223372036854775807'));
        $transaction = (new TransactionBuilder($account))
            ->addOperation(new BumpSequenceOperation(new BigInteger('2')))
            ->build();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('bigInteger64 value 9223372036854775808 is outside [-9223372036854775808, 9223372036854775807]');
        $transaction->toXdrBase64();
    }

    public function testOverflowingBumpSequenceFailsAtEncoding(): void
    {
        $operation = new BumpSequenceOperation(new BigInteger('9223372036854775808'));
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('bigInteger64 value 9223372036854775808 is outside [-9223372036854775808, 9223372036854775807]');
        $operation->toXdr()->encode();
    }

    public function testOverflowingMinimumSequenceFailsAtEncoding(): void
    {
        $preconditions = new TransactionPreconditions();
        $preconditions->setMinSeqNumber(new BigInteger('9223372036854775808'));
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('bigInteger64 value 9223372036854775808 is outside [-9223372036854775808, 9223372036854775807]');
        $preconditions->toXdr()->encode();
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
