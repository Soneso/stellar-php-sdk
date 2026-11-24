<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use InvalidArgumentException;
use phpseclib3\Math\BigInteger;
use Soneso\StellarSDK\Soroban\SecretSeedRedaction;

/**
 * See: https://tools.ietf.org/html/rfc4506
 *
 * - Data is stored in big endian
 */
class XdrEncoder
{
    private static ?bool $nativeIsBigEndian = null; // used for caching whether the current platform is big endian.

    /**
     * Checks a fixed-length opaque value against the byte length its XDR definition declares.
     *
     * Serves opaqueFixed(), TxRepHelper and the generated XDR-JSON and TxRep readers and writers.
     * When $allowShorter is true, values shorter than $expectedLength are accepted (for asset codes
     * that are right-padded with NUL bytes on binary encode).
     *
     * @internal
     * @param string $value raw bytes to check
     * @param int $expectedLength fixed byte length declared by the XDR definition
     * @param bool $allowShorter if true, accept byte lengths from 0 up to $expectedLength
     * @return string the unchanged $value
     * @throws InvalidArgumentException if $value does not match the declared fixed length
     */
    public static function checkFixedLength(string $value, int $expectedLength, bool $allowShorter = false): string
    {
        $length = strlen($value);
        if ($length > $expectedLength || (!$allowShorter && $length !== $expectedLength)) {
            throw new InvalidArgumentException(sprintf('Unexpected length for value. Has length %s, expected %s', $length, $expectedLength));
        }
        return $value;
    }

    /**
     * @param string $value
     * @param int|null $expectedLength in bytes
     * @param bool $padUnexpectedLength If true, an unexpected length is padded instead of throwing an exception
     * @return string
     */
    public static function opaqueFixed(string $value, ?int $expectedLength = null, bool $padUnexpectedLength = false): string
    {
        if ($expectedLength) {
            self::checkFixedLength($value, $expectedLength, $padUnexpectedLength);
            if (strlen($value) < $expectedLength) {
                // Pad to exactly the declared fixed length. str_pad rather than
                // applyPadding: the latter pads to the next multiple of the
                // target length, which leaves an empty value unpadded.
                $value = str_pad($value, $expectedLength, chr(0), STR_PAD_RIGHT);
            }
        }

        return self::applyPadding($value);
    }

    /**
     * Variable-length opaque data
     *
     * Maximum length is 2^32 - 1
     *
     * @param string $value
     * @return string
     * @throws InvalidArgumentException if the byte length exceeds 4294967295
     */
    public static function opaqueVariable(string $value): string
    {
        $maxLength = 4294967295;
        if (strlen($value) > $maxLength) throw new InvalidArgumentException(sprintf('Value of length %s is greater than the maximum allowed length of %s', strlen($value), $maxLength));

        $bytes = '';

        $bytes .= self::unsignedInteger32(strlen($value));
        $bytes .= self::applyPadding($value);

        return $bytes;
    }

    /**
     * Checks a value against the maximum length its XDR definition declares.
     *
     * Serves the generated encode(), fromTxRep() and fromJsonValue() methods: $length is the
     * byte length of a string or variable opaque value, or the element count of an array.
     *
     * @internal
     * @param int $length byte length or element count of the value
     * @param int $max the maximum the XDR definition declares
     * @param string $name the value's type and field, or its TxRep key, used in the error message
     * @return void
     * @throws InvalidArgumentException if $length exceeds $max
     */
    public static function assertMaxLength(int $length, int $max, string $name): void
    {
        if ($length > $max) {
            throw new InvalidArgumentException(sprintf(
                '%s length %d exceeds the maximum of %d',
                SecretSeedRedaction::redactSecretSeeds($name),
                $length,
                $max
            ));
        }
    }

    /**
     * @param int $value
     * @return string
     * @throws InvalidArgumentException if $value is outside [-2147483648, 2147483647]
     */
    public static function integer32($value): string
    {
        if ($value < -2147483648 || $value > 2147483647) {
            throw new InvalidArgumentException(sprintf('integer32 value %s is outside [-2147483648, 2147483647]', self::integerValueForMessage($value)));
        }

        // pack() does not support a signed 32-byte int, so work around this with
        // custom encoding
        return (self::nativeIsBigEndian()) ? pack('l', $value) : strrev(pack('l', $value));
    }

    /**
     * @param int $value
     * @return string
     * @throws InvalidArgumentException if $value is outside [0, 4294967295]
     */
    public static function unsignedInteger32($value): string
    {
        if ($value < 0 || $value > 4294967295) {
            throw new InvalidArgumentException(sprintf('unsignedInteger32 value %s is outside [0, 4294967295]', self::integerValueForMessage($value)));
        }

        // unsigned 32-bit big-endian
        return pack('N', $value);
    }

    /**
     * @param int $value
     * @return string
     */
    public static function integer64($value): string
    {
        // pack() does not support a signed 64-byte int, so work around this with
        // custom encoding
        return (self::nativeIsBigEndian()) ? pack('q', $value) : strrev(pack('q', $value));
    }

    /**
     * Converts $value to a signed 8-byte big endian int64
     *
     * @param BigInteger $value
     * @return string
     * @throws InvalidArgumentException if $value is outside [-9223372036854775808, 9223372036854775807]
     */
    public static function bigInteger64(BigInteger $value): string
    {
        if ($value->compare(new BigInteger('-9223372036854775808')) < 0 ||
            $value->compare(new BigInteger('9223372036854775807')) > 0) {
            throw new InvalidArgumentException(sprintf('bigInteger64 value %s is outside [-9223372036854775808, 9223372036854775807]', substr($value->toString(), 0, 80)));
        }

        $xdrBytes = '';
        $bigIntBytes = $value->toBytes(true);
        $bigIntBits = '';
        if($value != new BigInteger(0)) {
            $bigIntBits = $value->toBits(true);
        }

        // Explicit BigInteger precision can add a leading zero byte.
        if (strlen($bigIntBytes) === 9 && str_starts_with($value->toHex(true), '00')) {
            $bigIntBytes = substr($bigIntBytes, 1);
        }

        $paddingChar = chr(0);
        // If the number is negative, pad with 0xFF
        if (substr($bigIntBits, 0, 1) == 1) {
            $paddingChar = chr(255);
        }

        $paddingBytes = 8 - strlen($bigIntBytes);
        while ($paddingBytes > 0) {
            $xdrBytes .= $paddingChar;
            $paddingBytes--;
        }

        $xdrBytes .= $bigIntBytes;

        return XdrEncoder::opaqueFixed($xdrBytes, 8);
    }

    /**
     * Converts $value to an unsigned 8-byte big endian uint64
     *
     * @param BigInteger $value
     * @return string
     * @throws InvalidArgumentException if $value is outside [0, 18446744073709551615]
     */
    public static function unsignedBigInteger64(BigInteger $value): string
    {
        if ($value->compare(new BigInteger('0')) < 0 ||
            $value->compare(new BigInteger('18446744073709551615')) > 0) {
            throw new InvalidArgumentException(sprintf('unsignedBigInteger64 value %s is outside [0, 18446744073709551615]', substr($value->toString(), 0, 80)));
        }

        $xdrBytes = '';
        $bigIntBytes = $value->toBytes(true);

        // Special case: MAX_UINT_64 will look like 00ffffffffffffffff and have an
        // extra preceeding byte we need to get rid of
        if (strlen($bigIntBytes) === 9 && str_starts_with($value->toHex(true), '00')) {
            $bigIntBytes = substr($bigIntBytes, 1);
        }

        $paddingChar = chr(0);

        $paddingBytes = 8 - strlen($bigIntBytes);
        while ($paddingBytes > 0) {
            $xdrBytes .= $paddingChar;
            $paddingBytes--;
        }

        $xdrBytes .= $bigIntBytes;

        return XdrEncoder::opaqueFixed($xdrBytes, 8);
    }

    /**
     * Use this to write raw bytes representing a 64-bit integer
     *
     * This value will be padded up to 8 bytes
     *
     * @param string $value
     * @return string
     */
    public static function integer64RawBytes($value) : string
    {
        // Some libraries will give a 4-byte value here but it must be encoded
        // as 8
        return self::applyPadding($value, 8, false);
    }

    /**
     * Writes a PHP int's 64-bit pattern as uint64. Negative ints represent the upper half of uint64.
     * Signed decimal integer strings are accepted only within [PHP_INT_MIN, PHP_INT_MAX].
     *
     * @param mixed $value a PHP int bit pattern or a signed decimal integer string; other types are rejected
     * @return string
     * @throws InvalidArgumentException if $value is neither an int nor a decimal integer string in range
     */
    public static function unsignedInteger64($value): string
    {
        if (!is_int($value)) {
            if (!is_string($value) || preg_match('/\A[+-]?[0-9]+\z/', $value) !== 1) {
                throw new InvalidArgumentException(sprintf('unsignedInteger64 value %s is not an int or a decimal integer string in [%s, %s]', self::integerValueForMessage($value), PHP_INT_MIN, PHP_INT_MAX));
            }
            $integer = new BigInteger(ltrim($value, '+'));
            if ($integer->compare(new BigInteger(PHP_INT_MIN)) < 0 ||
                $integer->compare(new BigInteger(PHP_INT_MAX)) > 0) {
                throw new InvalidArgumentException(sprintf('unsignedInteger64 value %s is outside [%s, %s]', self::integerValueForMessage($value), PHP_INT_MIN, PHP_INT_MAX));
            }
            $value = (int) $value;
        }

        // unsigned 64-bit big-endian
        return pack('J', $value);
    }

    /**
     * @param int $value
     * @return string
     */
    public static function hyper($value): string
    {
        return self::integer64($value);
    }

    /**
     * @param mixed $value a PHP int bit pattern or a signed decimal integer string; other types are rejected
     * @return string
     * @throws InvalidArgumentException if $value is neither an int nor a decimal integer string in range
     */
    public static function unsignedHyper($value): string
    {
        return self::unsignedInteger64($value);
    }

    /**
     * @param string $value
     * @return string
     */
    public static function unsignedInteger256($value): string
    {
        return self::opaqueFixed($value, (256/8));
    }

    /**
     * @param bool $value
     * @return string
     */
    public static function boolean($value) : string
    {
        // Equivalent to 1 or 0 uint32
        return ($value) ? self::unsignedInteger32(1) : self::unsignedInteger32(0);
    }

    /**
     * @param string $value
     * @param int|null $maximumLength
     * @return string
     * @throws InvalidArgumentException if the byte length exceeds $maximumLength or 4294967295
     */
    public static function string(string $value, ?int $maximumLength = null): string
    {
        if ($maximumLength === null) $maximumLength = 4294967295;

        if (strlen($value) > $maximumLength) throw new InvalidArgumentException('string exceeds maximum length');

        $bytes = self::unsignedInteger32(strlen($value));
        $bytes .= $value;

        // Pad with null bytes to get a multiple of 4 bytes
        $remainder = (strlen($value) % 4);
        if ($remainder) {
            while ($remainder < 4) {
                $bytes .= "\0";
                $remainder++;
            }
        }

        return $bytes;
    }

    /**
     * @param int|null $value
     * @return string
     * @throws InvalidArgumentException if $value is outside [0, 4294967295]
     */
    public static function optionalUnsignedInteger($value): string
    {
        $bytes = '';

        if ($value !== null) {
            $bytes .= self::boolean(true);
            $bytes .= static::unsignedInteger32($value);
        }
        else {
            $bytes .= self::boolean(false);
        }

        return $bytes;
    }

    /**
     * @param string|null $value
     * @param int|null $maximumLength
     * @return string
     * @throws InvalidArgumentException if the byte length exceeds $maximumLength or 4294967295
     */
    public static function optionalString(?string $value, $maximumLength): string
    {
        $bytes = '';

        if ($value !== null) {
            $bytes .= self::boolean(true);
            $bytes .= static::string($value, $maximumLength);
        }
        else {
            $bytes .= self::boolean(false);
        }

        return $bytes;
    }

    /**
     * Formats rejected integer inputs without exposing secret seeds or unbounded strings.
     *
     * @param mixed $value the rejected input
     * @return string the int, a redacted string of at most 80 bytes, or the input's type
     */
    private static function integerValueForMessage(mixed $value): string
    {
        if (is_int($value)) {
            return (string) $value;
        }
        if (is_string($value)) {
            return substr(SecretSeedRedaction::redactSecretSeeds($value), 0, 80);
        }
        return get_debug_type($value);
    }

    /**
     * Ensures $value's length is a multiple of $targetLength bytes
     *
     * The default value for XDR is 4
     *
     * @param string $value
     * @param int|null $targetLength - desired length after padding is applied
     * @param bool|null $rightPadding - pad on the right of the value, false to pad to the left
     * @return string
     */
    private static function applyPadding(string $value, ?int $targetLength = 4, ?bool $rightPadding = true): string
    {
        // No padding necessary if it's a multiple of 4 bytes
        if (strlen($value) % $targetLength === 0) return $value;

        $numPaddingChars = $targetLength - (strlen($value) % $targetLength);

        if ($rightPadding) {
            return $value . str_repeat(chr(0), $numPaddingChars);
        }
        else {
            return str_repeat(chr(0), $numPaddingChars) . $value;
        }
    }

    /**
     * @return bool
     */
    private static function nativeIsBigEndian(): bool
    {
        if (null === self::$nativeIsBigEndian) {
            self::$nativeIsBigEndian = pack('L', 1) === pack('N', 1);
        }
        return self::$nativeIsBigEndian;
    }
}