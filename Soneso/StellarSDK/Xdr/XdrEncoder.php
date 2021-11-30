<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use phpseclib3\Math\BigInteger;

/**
 * See: https://tools.ietf.org/html/rfc4506
 *
 * - Data is stored in big endian
 */
class XdrEncoder
{
    /**
     * @param string $value
     * @param int|null $expectedLength in bytes
     * @param false $padUnexpectedLength If true, an unexpected length is padded instead of throwing an exception
     * @return string
     */
    public static function opaqueFixed(string $value, ?int $expectedLength = null, bool $padUnexpectedLength = false): string
    {
        // Length greater than expected length is always an error
        if ($expectedLength && strlen($value) > $expectedLength) throw new \InvalidArgumentException(sprintf('Unexpected length for value. Has length %s, expected %s', strlen($value), $expectedLength));
        if ($expectedLength && !$padUnexpectedLength && strlen($value) != $expectedLength) throw new \InvalidArgumentException(sprintf('Unexpected length for value. Has length %s, expected %s', strlen($value), $expectedLength));

        if ($expectedLength && strlen($value) != $expectedLength) {
            $value = self::applyPadding($value, $expectedLength);
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
     */
    public static function opaqueVariable(string $value): string
    {
        $maxLength = pow(2, 32) - 1;
        if (strlen($value) > $maxLength) throw new \InvalidArgumentException(sprintf('Value of length %s is greater than the maximum allowed length of %s', strlen($value), $maxLength));

        $bytes = '';

        $bytes .= self::unsignedInteger32(strlen($value));
        $bytes .= self::applyPadding($value);

        return $bytes;
    }

    public static function integer32($value): string
    {
        // pack() does not support a signed 32-byte int, so work around this with
        // custom encoding
        return (self::nativeIsBigEndian()) ? pack('l', $value) : strrev(pack('l', $value));
    }

    public static function unsignedInteger32($value): string
    {
        // unsigned 32-bit big-endian
        return pack('N', $value);
    }

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
     */
    public static function bigInteger64(BigInteger $value): string
    {
        $xdrBytes = '';
        $bigIntBytes = $value->toBytes(true);
        $bigIntBits = '';
        if($value != new BigInteger(0)) {
            $bigIntBits = $value->toBits(true);
        }

        // Special case: MAX_UINT_64 will look like 00ffffffffffffffff and have an
        // extra preceding byte we need to get rid of
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
     */
    public static function unsignedBigInteger64(BigInteger $value): string
    {
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
     * @param $value
     * @return string
     */
    public static function integer64RawBytes($value) : string
    {
        // Some libraries will give a 4-byte value here but it must be encoded
        // as 8
        return self::applyPadding($value, 8, false);
    }

    public static function unsignedInteger64($value): string
    {
        if ($value > PHP_INT_MAX) throw new \InvalidArgumentException('value is greater than PHP_INT_MAX');

        // unsigned 64-bit big-endian
        return pack('J', $value);
    }

    public static function hyper($value): string
    {
        return self::integer64($value);
    }

    public static function unsignedHyper($value): string
    {
        return self::unsignedInteger64($value);
    }

    public static function unsignedInteger256($value): string
    {
        return self::opaqueFixed($value, (256/8));
    }

    public static function boolean($value) : string
    {
        // Equivalent to 1 or 0 uint32
        return ($value) ? self::unsignedInteger32(1) : self::unsignedInteger32(0);
    }

    /**
     * @param string $value
     * @param int|null $maximumLength
     * @return string
     */
    public static function string(string $value, ?int $maximumLength = null): string
    {
        if ($maximumLength === null) $maximumLength = pow(2, 32) - 1;

        if (strlen($value) > $maximumLength) throw new \InvalidArgumentException('string exceeds maximum length');

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
     * @param $value
     * @return string
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
     * @param $value
     * @return string
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
        return pack('L', 1) === pack('N', 1);
    }
}