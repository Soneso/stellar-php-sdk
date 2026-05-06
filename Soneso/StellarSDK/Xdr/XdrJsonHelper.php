<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use InvalidArgumentException;

/**
 * Primitive SEP-51 (XDR-JSON) helper methods.
 *
 * This class provides low-level encoding and decoding utilities for the SEP-0051
 * standard mapping between Stellar XDR structures and their JSON representations.
 * Higher-level toJson/fromJson methods on individual XDR classes delegate to
 * these primitives for the byte-level and numeric operations defined in the spec.
 *
 * All methods are stateless and operate only on their arguments.
 *
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0051.md
 */
final class XdrJsonHelper
{
    /**
     * SEP-51 String type: escape bytes per the SEP-51 String escape ladder.
     *
     * Escaping rules (applied byte-by-byte on the raw binary input):
     *   - 0x00 (NUL)  -> \0
     *   - 0x09 (TAB)  -> \t
     *   - 0x0A (LF)   -> \n
     *   - 0x0D (CR)   -> \r
     *   - 0x5C (BS)   -> \\
     *   - 0x20..0x7E (printable ASCII, excluding backslash) -> verbatim
     *   - all other bytes (0x01..0x08, 0x0B, 0x0C, 0x0E..0x1F, 0x7F, 0x80..0xFF)
     *     -> \xNN (lowercase hex, exactly two digits)
     *
     * The input is treated as a raw byte string (not UTF-8); strlen() and ord()
     * are used throughout — never mb_strlen() or mb_substr().
     *
     * When this output is subsequently stored inside a JSON string literal the
     * JSON encoder will escape each backslash a second time, producing the
     * double-escaped form the spec specifies (e.g. "\\xc3" in JSON text).
     *
     * @param string $bytes Raw binary input (any byte sequence).
     * @return string Escaped ASCII output (only printable ASCII bytes).
     */
    public static function escapeString(string $bytes): string
    {
        $len = strlen($bytes);
        $out = '';
        for ($i = 0; $i < $len; $i++) {
            $b = ord($bytes[$i]);
            if ($b === 0x00) {
                $out .= '\\0';
            } elseif ($b === 0x09) {
                $out .= '\\t';
            } elseif ($b === 0x0A) {
                $out .= '\\n';
            } elseif ($b === 0x0D) {
                $out .= '\\r';
            } elseif ($b === 0x5C) {
                $out .= '\\\\';
            } elseif ($b >= 0x20 && $b <= 0x7E) {
                $out .= $bytes[$i];
            } else {
                $out .= sprintf('\\x%02x', $b);
            }
        }
        return $out;
    }

    /**
     * Inverse of escapeString: decode a SEP-51 escaped-ASCII string back to raw bytes.
     *
     * Recognised escape sequences:
     *   - \0  -> 0x00
     *   - \t  -> 0x09
     *   - \n  -> 0x0A
     *   - \r  -> 0x0D
     *   - \\ -> 0x5C
     *   - \xNN (exactly two lowercase hex digits) -> byte 0xNN
     *
     * Any other character following a backslash is rejected. Unescaped printable
     * ASCII characters (0x20..0x7E) are returned as their byte value. The input
     * is validated strictly so that malformed sequences do not silently produce
     * wrong output.
     *
     * @param string $escaped The escaped-ASCII string (output of escapeString).
     * @return string Raw binary bytes.
     * @throws InvalidArgumentException On unrecognised escape sequence, truncated \x, or invalid hex digits.
     */
    public static function unescapeString(string $escaped): string
    {
        $len = strlen($escaped);
        $out = '';
        $i = 0;
        while ($i < $len) {
            $ch = $escaped[$i];
            if ($ch !== '\\') {
                $out .= $ch;
                $i++;
                continue;
            }
            // We have a backslash; require at least one more character.
            if ($i + 1 >= $len) {
                throw new InvalidArgumentException(
                    'Malformed SEP-51 escaped string: trailing backslash at position ' . $i
                );
            }
            $next = $escaped[$i + 1];
            if ($next === '0') {
                $out .= "\x00";
                $i += 2;
            } elseif ($next === 't') {
                $out .= "\x09";
                $i += 2;
            } elseif ($next === 'n') {
                $out .= "\x0A";
                $i += 2;
            } elseif ($next === 'r') {
                $out .= "\x0D";
                $i += 2;
            } elseif ($next === '\\') {
                $out .= "\x5C";
                $i += 2;
            } elseif ($next === 'x') {
                // Require exactly two hex digits.
                if ($i + 3 >= $len) {
                    throw new InvalidArgumentException(
                        'Malformed SEP-51 escaped string: truncated \\x escape at position ' . $i
                    );
                }
                $hi = $escaped[$i + 2];
                $lo = $escaped[$i + 3];
                // SEP-51 specifies lowercase hex only; \xNN must use digits 0-9 and a-f.
                // ctype_xdigit also accepts A-F, creating a de-canonicalisation surface
                // where \xff and \xFF would map to the same byte from distinct ASCII inputs.
                if (!preg_match('/^[0-9a-f]$/', $hi) || !preg_match('/^[0-9a-f]$/', $lo)) {
                    throw new InvalidArgumentException(
                        'Malformed SEP-51 escaped string: \\x escape requires lowercase hex digits '
                        . '[0-9a-f] at position ' . $i
                        . ' (got \\x' . bin2hex($hi) . bin2hex($lo) . ')'
                    );
                }
                $out .= chr(hexdec($hi . $lo));
                $i += 4;
            } else {
                throw new InvalidArgumentException(
                    'Malformed SEP-51 escaped string: unrecognised escape sequence \\' . bin2hex($next)
                    . ' at position ' . $i
                );
            }
        }
        return $out;
    }

    /**
     * SEP-51 Opaque type: encode raw bytes as a lowercase hex string.
     *
     * Per SEP-0051 the Opaque types (both fixed-length and variable-length)
     * are represented as hexadecimal strings. An empty byte sequence encodes
     * as an empty string "" (not "0" and not "00").
     *
     * @param string $bytes Raw binary input (any length, including zero).
     * @return string Lowercase hex string (even length; two hex chars per byte).
     */
    public static function bytesToHex(string $bytes): string
    {
        if ($bytes === '') {
            return '';
        }
        return bin2hex($bytes);
    }

    /**
     * Inverse of bytesToHex: decode a lowercase hex string back to raw bytes.
     *
     * Strict mode is enforced: only lowercase hexadecimal characters [0-9a-f]
     * are accepted. Uppercase hex is rejected; SEP-0051 §Opaque specifies
     * lowercase hex output, and the decoder mirrors that constraint so that
     * round-trips remain canonical.
     *
     * @param string $hex Lowercase hex string (even length; zero length allowed).
     * @return string Raw binary bytes.
     * @throws InvalidArgumentException On odd-length input, uppercase characters, or non-hex characters.
     */
    public static function hexToBytes(string $hex): string
    {
        if ($hex === '') {
            return '';
        }
        if (strlen($hex) % 2 !== 0) {
            throw new InvalidArgumentException(
                'SEP-51 hex string must have even length; got ' . strlen($hex) . ' characters'
            );
        }
        // Use \A and \z anchors (not ^ and $) so that a trailing newline does not bypass the
        // check — PHP PCRE treats $ as matching before a trailing \n in default mode.
        if (!preg_match('/\A[0-9a-f]+\z/', $hex)) {
            throw new InvalidArgumentException(
                'SEP-51 hex string must contain only lowercase hexadecimal characters [0-9a-f]; '
                . 'uppercase hex is not accepted (see SEP-0051 §Opaque)'
            );
        }
        return hex2bin($hex);
    }

    /**
     * 64-bit signed integer to base-10 string (SEP-51 Hyper Integer encoding).
     *
     * @param int $value A PHP int (64-bit signed on 64-bit systems).
     * @return string Base-10 decimal string representation.
     */
    public static function int64ToString(int $value): string
    {
        return (string) $value;
    }

    /**
     * Parse a base-10 integer string (or a PHP int) to a 64-bit signed int.
     *
     * Accepts int|string for compatibility with JSON producers that emit numbers
     * for 64-bit integers in addition to the spec-required strings.
     *
     * Strict validation rules:
     *   - Empty string is rejected.
     *   - Leading/trailing whitespace is rejected.
     *   - Scientific notation ("1e10") is rejected.
     *   - Decimal points ("1.0") are rejected.
     *   - Hex notation ("0x10") is rejected.
     *   - Leading "+" is rejected.
     *   - Only an optional leading "-" followed by one or more decimal digits is accepted.
     *   - Leading zeros are accepted (SEP-0051 does not forbid them in 64-bit string-encoded integers).
     *   - The resulting value must be in [-2^63, 2^63-1].
     *   - intval() is explicitly NOT used because intval("abc") silently returns 0.
     *
     * @param int|string $value The value to parse.
     * @return int The parsed 64-bit signed integer.
     * @throws InvalidArgumentException On any validation failure.
     */
    public static function stringToInt64(int|string $value): int
    {
        if (is_int($value)) {
            return $value;
        }
        self::validateInt64String($value);
        $gmp = gmp_init($value, 10);
        $min = gmp_init('-9223372036854775808');
        $max = gmp_init('9223372036854775807');
        if (gmp_cmp($gmp, $min) < 0 || gmp_cmp($gmp, $max) > 0) {
            throw new InvalidArgumentException(
                'int64 string of length ' . strlen($value) . ' is out of the range [-2^63, 2^63-1]'
            );
        }
        // gmp_intval is safe here: the range check above guarantees the GMP value fits in PHP int.
        return gmp_intval($gmp);
    }

    /**
     * 64-bit unsigned integer to base-10 string (SEP-51 Unsigned Hyper Integer encoding).
     *
     * PHP's int is a 64-bit signed integer on 64-bit systems, so the valid range
     * for the $value parameter is [0, PHP_INT_MAX] (i.e. [0, 2^63-1]). Values in
     * the upper uint64 range (2^63..2^64-1) cannot be represented as a PHP int
     * without overflow; callers holding such values must use stringToUint64() with
     * a string argument instead of this method.
     *
     * @param int $value A PHP int in [0, PHP_INT_MAX].
     * @return string Base-10 decimal string.
     * @throws InvalidArgumentException If $value is negative.
     */
    public static function uint64ToString(int $value): string
    {
        if ($value < 0) {
            // Negative PHP ints cannot represent valid uint64 values via this path.
            // Callers with uint64 values in (PHP_INT_MAX, 2^64-1] must use the
            // string-accepting stringToUint64() + sprintf pattern instead.
            throw new InvalidArgumentException(
                'uint64ToString accepts only non-negative values; got ' . $value
                . '. For uint64 values above PHP_INT_MAX, pass the value as a string to stringToUint64().'
            );
        }
        return (string) $value;
    }

    /**
     * Parse a base-10 unsigned-integer string (or a PHP int) to a uint64 value.
     *
     * Accepts int|string for compatibility with JSON producers that emit numbers
     * for 64-bit integers in addition to the spec-required strings.
     *
     * For uint64 values above PHP_INT_MAX (i.e. 9223372036854775808..18446744073709551615)
     * a string must be provided; those values cannot be expressed as a PHP native int.
     * The method uses GMP for range validation against [0, 2^64-1] and returns the value
     * as a PHP int, reinterpreting the upper-half as a negative signed int (two's
     * complement) — this matches how the XDR parts structs store uint64 limbs.
     *
     * Strict validation: same rules as stringToInt64 except no leading minus is allowed.
     *
     * @param int|string $value The value to parse.
     * @return int The uint64 value stored as a PHP int (may be negative for values > PHP_INT_MAX).
     * @throws InvalidArgumentException On any validation failure including values < 0 or > 2^64-1.
     */
    public static function stringToUint64(int|string $value): int
    {
        if (is_int($value)) {
            if ($value < 0) {
                throw new InvalidArgumentException(
                    'Negative PHP int cannot represent a valid uint64; got ' . $value
                );
            }
            return $value;
        }
        self::validateUint64String($value);
        $gmp = gmp_init($value, 10);
        $max = gmp_sub(gmp_pow(2, 64), 1); // 2^64-1 = 18446744073709551615
        if (gmp_cmp($gmp, 0) < 0 || gmp_cmp($gmp, $max) > 0) {
            throw new InvalidArgumentException(
                'uint64 string of length ' . strlen($value) . ' is out of the range [0, 2^64-1]'
            );
        }
        // Values > PHP_INT_MAX are reinterpreted as signed via two's-complement subtraction.
        // gmp_intval is safe on both branches: the range checks guarantee the GMP value fits in PHP int.
        $phpIntMax = gmp_init('9223372036854775807');
        if (gmp_cmp($gmp, $phpIntMax) > 0) {
            $signed = gmp_sub($gmp, gmp_pow(2, 64));
            return gmp_intval($signed);
        }
        return gmp_intval($gmp);
    }

    /**
     * Assemble a signed 128-bit integer from hi/lo limbs and return as a base-10 string.
     *
     * Per XDR Int128Parts (verified against XdrInt128Parts.php encode path):
     * hi is signed int64; lo is unsigned uint64.
     *
     *   result = hi_signed * 2^64 + lo_unsigned
     *
     * lo_unsigned is obtained by treating the PHP int stored in $lo as an unsigned 64-bit
     * value (i.e. adding 2^64 if negative). GMP is used throughout to handle the full range.
     *
     * @param string $hi Signed int64 as base-10 string (range [-2^63, 2^63-1]).
     * @param string $lo Unsigned uint64 as base-10 string. May be a negative base-10 string
     *     when the uint64 value exceeds PHP_INT_MAX and is stored as a signed PHP int (the
     *     negative form is reinterpreted as the unsigned bit pattern via two's-complement).
     * @return string Base-10 decimal string of the assembled 128-bit signed integer.
     * @throws InvalidArgumentException If hi or lo are not valid integer strings.
     */
    public static function int128PartsToString(string $hi, string $lo): string
    {
        self::validateInt64String($hi);
        self::validateInt64OrUint64HalfString($lo);
        $hiGmp = gmp_init($hi, 10);
        $loGmp = self::uint64HalfToGmp($lo);
        // Signed 128-bit assembly: hi (signed) * 2^64 + lo (unsigned).
        $result = gmp_add(gmp_mul($hiGmp, gmp_pow(2, 64)), $loGmp);
        return gmp_strval($result);
    }

    /**
     * Decompose a signed 128-bit integer string into hi/lo limbs.
     *
     * Per XDR Int128Parts (verified against XdrInt128Parts.php decode path):
     * hi is signed int64; lo is unsigned uint64.
     *
     * Returns an associative array with:
     *   - 'hi': signed int64 base-10 string (range [-2^63, 2^63-1])
     *   - 'lo': unsigned uint64 base-10 string (range [0, 2^64-1])
     *
     * @param string $value Base-10 decimal string of a signed 128-bit integer.
     * @return array{hi: string, lo: string}
     * @throws InvalidArgumentException If $value is not a valid int128 string.
     */
    public static function stringToInt128Parts(string $value): array
    {
        $gmp = self::parseAndValidateInt128($value);
        // For negative values, convert to the unsigned two's-complement 128-bit form.
        if (gmp_cmp($gmp, 0) < 0) {
            $gmp = gmp_add($gmp, gmp_pow(2, 128));
        }
        $mask64 = gmp_sub(gmp_pow(2, 64), 1);
        $lo = gmp_and($gmp, $mask64);
        $hi = gmp_div_q($gmp, gmp_pow(2, 64));
        // hi is now an unsigned 64-bit value; interpret as signed int64.
        $hiStr = self::unsignedGmpToSignedInt64String($hi);
        $loStr = gmp_strval($lo);
        return ['hi' => $hiStr, 'lo' => $loStr];
    }

    /**
     * Assemble an unsigned 128-bit integer from hi/lo uint64 parts and return as a base-10 string.
     *
     * For unsigned 128-bit (UInt128Parts) both the hi and lo limbs are unsigned uint64 values
     * stored as PHP signed ints (two's-complement). GMP is used to reconstruct the full value.
     *
     * @param string $hi Unsigned uint64 as base-10 string. May be a negative base-10 string
     *     when the uint64 value exceeds PHP_INT_MAX and is stored as a signed PHP int (the
     *     negative form is reinterpreted as the unsigned bit pattern via two's-complement).
     * @param string $lo Unsigned uint64 as base-10 string. May be a negative base-10 string
     *     when the uint64 value exceeds PHP_INT_MAX and is stored as a signed PHP int (the
     *     negative form is reinterpreted as the unsigned bit pattern via two's-complement).
     * @return string Base-10 decimal string of the assembled 128-bit unsigned integer.
     * @throws InvalidArgumentException If hi or lo are not valid int64-fitting strings.
     */
    public static function uint128PartsToString(string $hi, string $lo): string
    {
        self::validateInt64OrUint64HalfString($hi);
        self::validateInt64OrUint64HalfString($lo);
        $hiGmp = self::uint64HalfToGmp($hi);
        $loGmp = self::uint64HalfToGmp($lo);
        $result = gmp_add(gmp_mul($hiGmp, gmp_pow(2, 64)), $loGmp);
        return gmp_strval($result);
    }

    /**
     * Decompose an unsigned 128-bit integer string into hi/lo uint64 parts.
     *
     * Returns an associative array with keys 'hi' and 'lo', both as base-10
     * strings representing unsigned uint64 values in [0, 2^64-1].
     *
     * @param string $value Base-10 decimal string of an unsigned 128-bit integer.
     * @return array{hi: string, lo: string}
     * @throws InvalidArgumentException If $value is not a valid uint128 string.
     */
    public static function stringToUint128Parts(string $value): array
    {
        $gmp = self::parseAndValidateUint128($value);
        $mask64 = gmp_sub(gmp_pow(2, 64), 1);
        $lo = gmp_and($gmp, $mask64);
        $hi = gmp_div_q($gmp, gmp_pow(2, 64));
        return ['hi' => gmp_strval($hi), 'lo' => gmp_strval($lo)];
    }

    /**
     * Assemble a signed 256-bit integer from four limbs and return as a base-10 string.
     *
     * Per XDR Int256Parts (verified against XdrInt256Parts.php encode path):
     *   - hiHi: signed int64 (most significant)
     *   - hiLo: unsigned uint64
     *   - loHi: unsigned uint64
     *   - loLo: unsigned uint64 (least significant)
     *
     * All limb strings may be supplied in either unsigned form ("18446744073709551615")
     * or in PHP's signed two's-complement wrap form ("-1") — the internal uint64HalfToGmp
     * helper handles both representations for lossless assembly.
     *
     * @param string $hiHi Signed int64 as base-10 string (range [-2^63, 2^63-1]).
     * @param string $hiLo Unsigned uint64 as base-10 string. May be a negative base-10
     *     string when the uint64 value exceeds PHP_INT_MAX and is stored as a signed PHP int
     *     (the negative form is reinterpreted as the unsigned bit pattern via two's-complement).
     * @param string $loHi Unsigned uint64 as base-10 string (same note as hiLo).
     * @param string $loLo Unsigned uint64 as base-10 string (same note as hiLo).
     * @return string Base-10 decimal string of the assembled 256-bit signed integer.
     * @throws InvalidArgumentException If any limb string is invalid.
     */
    public static function int256PartsToString(
        string $hiHi,
        string $hiLo,
        string $loHi,
        string $loLo
    ): string {
        self::validateInt64String($hiHi);
        self::validateInt64OrUint64HalfString($hiLo);
        self::validateInt64OrUint64HalfString($loHi);
        self::validateInt64OrUint64HalfString($loLo);
        // All four limbs are treated as raw 64-bit values. hiHi and hiLo are signed int64s,
        // so negative values must be converted to their unsigned uint64 bit-patterns before
        // assembly; otherwise -1 * 2^192 would produce the wrong signed result.
        $hiHiGmp = self::uint64HalfToGmp($hiHi);
        $hiLoGmp = self::uint64HalfToGmp($hiLo);
        $loHiGmp = self::uint64HalfToGmp($loHi);
        $loLoGmp = self::uint64HalfToGmp($loLo);
        // Assemble as unsigned 256-bit, then reinterpret as signed.
        $unsigned = gmp_add(
            gmp_add(
                gmp_mul($hiHiGmp, gmp_pow(2, 192)),
                gmp_mul($hiLoGmp, gmp_pow(2, 128))
            ),
            gmp_add(
                gmp_mul($loHiGmp, gmp_pow(2, 64)),
                $loLoGmp
            )
        );
        // Two's-complement reinterpretation: values >= 2^255 are negative.
        $half256 = gmp_pow(2, 255);
        if (gmp_cmp($unsigned, $half256) >= 0) {
            $unsigned = gmp_sub($unsigned, gmp_pow(2, 256));
        }
        return gmp_strval($unsigned);
    }

    /**
     * Decompose a signed 256-bit integer string into four limbs.
     *
     * Per XDR Int256Parts (verified against XdrInt256Parts.php decode path):
     * only hiHi is signed int64; hiLo, loHi, loLo are unsigned uint64.
     * Returns an associative array with:
     *   - 'hiHi': signed int64 base-10 string (range [-2^63, 2^63-1])
     *   - 'hiLo': unsigned uint64 base-10 string (range [0, 2^64-1])
     *   - 'loHi': unsigned uint64 base-10 string (range [0, 2^64-1])
     *   - 'loLo': unsigned uint64 base-10 string (range [0, 2^64-1])
     *
     * @param string $value Base-10 decimal string of a signed 256-bit integer.
     * @return array{hiHi: string, hiLo: string, loHi: string, loLo: string}
     * @throws InvalidArgumentException If $value is not a valid int256 string.
     */
    public static function stringToInt256Parts(string $value): array
    {
        $gmp = self::parseAndValidateInt256($value);
        if (gmp_cmp($gmp, 0) < 0) {
            $gmp = gmp_add($gmp, gmp_pow(2, 256));
        }
        [$hiHi, $hiLo, $loHi, $loLo] = self::splitTo4x64($gmp);
        return [
            // hiHi is signed int64 per IDL; convert the unsigned GMP limb to its signed form.
            'hiHi' => self::unsignedGmpToSignedInt64String($hiHi),
            // hiLo, loHi, loLo are unsigned uint64 per IDL; emit the unsigned decimal string.
            'hiLo' => gmp_strval($hiLo),
            'loHi' => gmp_strval($loHi),
            'loLo' => gmp_strval($loLo),
        ];
    }

    /**
     * Assemble an unsigned 256-bit integer from four uint64 limbs and return as a base-10 string.
     *
     * Per XDR UInt256Parts all four limbs are unsigned uint64 values.
     *
     * @param string $hiHi Unsigned uint64 as base-10 string (most significant limb). May be a
     *     negative base-10 string when the uint64 value exceeds PHP_INT_MAX and is stored as a
     *     signed PHP int (the negative form is reinterpreted as the unsigned bit pattern via
     *     two's-complement).
     * @param string $hiLo Unsigned uint64 as base-10 string (same note as hiHi).
     * @param string $loHi Unsigned uint64 as base-10 string (same note as hiHi).
     * @param string $loLo Unsigned uint64 as base-10 string (least significant limb; same note as hiHi).
     * @return string Base-10 decimal string of the assembled 256-bit unsigned integer.
     * @throws InvalidArgumentException If any limb string is invalid.
     */
    public static function uint256PartsToString(
        string $hiHi,
        string $hiLo,
        string $loHi,
        string $loLo
    ): string {
        self::validateInt64OrUint64HalfString($hiHi);
        self::validateInt64OrUint64HalfString($hiLo);
        self::validateInt64OrUint64HalfString($loHi);
        self::validateInt64OrUint64HalfString($loLo);
        $hiHiGmp = self::uint64HalfToGmp($hiHi);
        $hiLoGmp = self::uint64HalfToGmp($hiLo);
        $loHiGmp = self::uint64HalfToGmp($loHi);
        $loLoGmp = self::uint64HalfToGmp($loLo);
        $result = gmp_add(
            gmp_add(
                gmp_mul($hiHiGmp, gmp_pow(2, 192)),
                gmp_mul($hiLoGmp, gmp_pow(2, 128))
            ),
            gmp_add(
                gmp_mul($loHiGmp, gmp_pow(2, 64)),
                $loLoGmp
            )
        );
        return gmp_strval($result);
    }

    /**
     * Decompose an unsigned 256-bit integer string into four uint64 limbs.
     *
     * Returns an associative array with keys 'hiHi', 'hiLo', 'loHi', 'loLo',
     * all as base-10 strings representing unsigned uint64 values in [0, 2^64-1].
     *
     * @param string $value Base-10 decimal string of an unsigned 256-bit integer.
     * @return array{hiHi: string, hiLo: string, loHi: string, loLo: string}
     * @throws InvalidArgumentException If $value is not a valid uint256 string.
     */
    public static function stringToUint256Parts(string $value): array
    {
        $gmp = self::parseAndValidateUint256($value);
        [$hiHi, $hiLo, $loHi, $loLo] = self::splitTo4x64($gmp);
        return [
            'hiHi' => gmp_strval($hiHi),
            'hiLo' => gmp_strval($hiLo),
            'loHi' => gmp_strval($loHi),
            'loLo' => gmp_strval($loLo),
        ];
    }

    /**
     * Canonical JSON normalisation for structural JSON comparison.
     *
     * The algorithm:
     *   1. Decode with assoc=false (stdClass mode) so the empty-object / empty-array
     *      distinction is preserved: json_decode('{}') -> stdClass, json_decode('[]') -> [].
     *   2. Recurse via ksortRecursive: sort object property names lexicographically;
     *      preserve list order for indexed arrays.
     *   3. Re-encode with JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE so that
     *      forward slashes and non-ASCII codepoints are not double-escaped.
     *
     * The result is a deterministic, whitespace-free JSON string. Two JSON values that
     * are semantically equal (same structure, same key order after sorting, same values)
     * will produce byte-identical canonical forms.
     *
     * Note: this method does not impose an input-size limit. Callers are responsible for
     * bounding $json length before invoking this method; an HTTP layer or file reader
     * should apply a size limit appropriate to its threat model.
     *
     * @param string $json Any valid JSON string.
     * @return string Canonical (sorted-keys, compact) JSON string.
     * @throws JsonException On malformed input JSON.
     */
    public static function canonicalJson(string $json): string
    {
        $decoded = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
        $sorted = self::ksortRecursive($decoded, 512);
        return json_encode($sorted, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Recursively sort object property names for canonical JSON normalisation.
     *
     * Walk rules:
     *   - stdClass (object): cast to array, ksort by string key, recurse into values,
     *     cast back to object. This preserves the {} vs [] distinction in re-encoding.
     *   - Indexed array (list-shape): preserve element order; recurse into elements.
     *   - Associative array: ksort and recurse (defensive; should not occur in stdClass-mode
     *     decode but covered for callers using this method directly on mixed structures).
     *   - Scalar / null: return as-is.
     *
     * The $depth parameter bounds recursion depth, matching json_decode's own default of 512.
     * Callers constructing deeply-nested PHP arrays by hand and passing them directly here
     * (rather than via canonicalJson) should pass an appropriate depth bound. canonicalJson
     * starts the recursion at depth=512 which aligns with json_decode's own limit.
     *
     * @param mixed $value Any decoded JSON value.
     * @param int   $depth Remaining recursion depth. Throws when it reaches zero.
     * @return mixed The same structure with all object property keys sorted recursively.
     * @throws InvalidArgumentException When $depth is exhausted (nesting too deep).
     */
    public static function ksortRecursive(mixed $value, int $depth = 512): mixed
    {
        if ($depth < 0) {
            throw new InvalidArgumentException(
                'ksortRecursive: maximum recursion depth exceeded'
            );
        }
        if (is_object($value)) {
            $arr = (array) $value;
            ksort($arr, SORT_STRING);
            $sorted = [];
            foreach ($arr as $k => $v) {
                $sorted[$k] = self::ksortRecursive($v, $depth - 1);
            }
            return (object) $sorted;
        }
        if (is_array($value)) {
            // array_is_list() requires PHP 8.1+; use an 8.0-compatible equivalent so the
            // helper runs on any PHP version the composer.json constraint allows (>=8.0).
            $isList = $value === [] || array_keys($value) === range(0, count($value) - 1);
            if ($isList) {
                // Indexed array: preserve order, recurse into elements.
                $result = [];
                foreach ($value as $v) {
                    $result[] = self::ksortRecursive($v, $depth - 1);
                }
                return $result;
            }
            // Associative array: sort keys and recurse (defensive path).
            ksort($value, SORT_STRING);
            $sorted = [];
            foreach ($value as $k => $v) {
                $sorted[$k] = self::ksortRecursive($v, $depth - 1);
            }
            return $sorted;
        }
        return $value;
    }

    /**
     * Return a safe, bounded preview of user-supplied input for use in exception messages.
     *
     * Embedding unbounded user input in exception messages can cause log-amplification
     * denial-of-service when the message is logged. This helper caps the preview at $max
     * bytes and appends "..." when the string is truncated, keeping messages informative
     * without being exploitable.
     *
     * @param string $s   The user-supplied string.
     * @param int    $max Maximum characters before truncation (must be > 3).
     * @return string A safe, bounded preview.
     */
    public static function safePreview(string $s, int $max = 80): string
    {
        // Replace ASCII control characters (0x00-0x1F, 0x7F) with their hex
        // escape form. Without this, attacker-controlled input echoed through
        // exception messages can inject ANSI escape sequences when those
        // messages are rendered to a terminal (log injection / amplification).
        $sanitised = preg_replace_callback(
            '/[\x00-\x1F\x7F]/',
            static function (array $m): string {
                return '\\x' . strtoupper(bin2hex($m[0]));
            },
            $s
        );
        if ($sanitised === null) {
            // preg_replace_callback returns null only on PCRE engine failure
            // (out-of-memory, recursion limit). Fall back to a fixed marker
            // rather than echoing the raw input so the safety contract holds.
            return '<unprintable>';
        }
        if (strlen($sanitised) <= $max) {
            return $sanitised;
        }
        return substr($sanitised, 0, $max - 3) . '...';
    }

    /**
     * Wrap an unsigned uint64 base-10 string into the matching PHP signed-int
     * representation used by the Parts struct fields.
     *
     * The 128-bit / 256-bit Parts wrappers store every limb as a PHP signed
     * int (XDR's hyper / unsigned hyper map onto the same 64-bit native slot
     * on 64-bit systems). Values in [2^63, 2^64-1] therefore land in PHP as
     * negative integers via two's-complement subtraction. This helper
     * performs that conversion once, with full input validation, so the
     * generated parts-decoder bodies do not each carry their own inline
     * closure.
     *
     * Accepted input: a non-empty string of decimal digits representing an
     * unsigned integer in [0, 2^64-1]. Out-of-range values, leading signs,
     * empty strings, and non-digit characters are rejected.
     *
     * @param string $decimal Unsigned base-10 string in [0, 2^64-1].
     * @return int PHP signed int (may be negative for values above PHP_INT_MAX).
     * @throws InvalidArgumentException On any validation failure.
     */
    public static function wrapUnsignedToSignedInt(string $decimal): int
    {
        if ($decimal === '') {
            throw new InvalidArgumentException(
                'wrapUnsignedToSignedInt: input must not be empty'
            );
        }
        if (!ctype_digit($decimal)) {
            throw new InvalidArgumentException(
                'wrapUnsignedToSignedInt: input must be unsigned decimal digits; got "'
                . self::safePreview($decimal) . '"'
            );
        }
        $gmp = gmp_init($decimal, 10);
        $upperBound = self::twoToThe64();
        if (gmp_cmp($gmp, $upperBound) >= 0) {
            throw new InvalidArgumentException(
                'wrapUnsignedToSignedInt: input out of range [0, 2^64-1]; length=' . strlen($decimal)
            );
        }
        $signedHalf = self::twoToThe63();
        if (gmp_cmp($gmp, $signedHalf) >= 0) {
            $gmp = gmp_sub($gmp, $upperBound);
        }
        return gmp_intval($gmp);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Cached GMP value for 2^63, used as the boundary between signed-positive
     * and signed-negative representations of uint64 values.
     */
    private static ?\GMP $twoToThe63 = null;

    /**
     * Cached GMP value for 2^64, used to subtract from uint64 values that
     * exceed PHP_INT_MAX to produce the signed-int representation.
     */
    private static ?\GMP $twoToThe64 = null;

    private static function twoToThe63(): \GMP
    {
        if (self::$twoToThe63 === null) {
            self::$twoToThe63 = gmp_pow(2, 63);
        }
        return self::$twoToThe63;
    }

    private static function twoToThe64(): \GMP
    {
        if (self::$twoToThe64 === null) {
            self::$twoToThe64 = gmp_pow(2, 64);
        }
        return self::$twoToThe64;
    }

    /**
     * Validate that a string represents a valid signed int64 in base-10.
     *
     * Accepts an optional leading minus, then one or more decimal digits.
     * Leading zeros are accepted. No whitespace, no scientific notation,
     * no "+" prefix, no decimal points.
     *
     * @throws InvalidArgumentException On format violation.
     */
    private static function validateInt64String(string $s): void
    {
        if ($s === '') {
            throw new InvalidArgumentException('SEP-51 int64 string must not be empty');
        }
        $digits = $s;
        if ($s[0] === '-') {
            $digits = substr($s, 1);
            if ($digits === '') {
                throw new InvalidArgumentException(
                    'SEP-51 int64 string must have digits after minus sign; got "-"'
                );
            }
        }
        if (!ctype_digit($digits)) {
            throw new InvalidArgumentException(
                'SEP-51 int64 string must contain only decimal digits (with optional leading minus); '
                . 'got "' . self::safePreview($s) . '"'
            );
        }
    }

    /**
     * Validate that a string represents a valid unsigned integer (no minus sign).
     *
     * Used for uint64 values and for the lo/loHi/loLo limbs of signed 256-bit types
     * (which are uint64-ranged even though stored as PHP signed ints).
     *
     * @throws InvalidArgumentException On format violation.
     */
    private static function validateUint64String(string $s): void
    {
        if ($s === '') {
            throw new InvalidArgumentException('SEP-51 uint64 string must not be empty');
        }
        if (!ctype_digit($s)) {
            throw new InvalidArgumentException(
                'SEP-51 uint64 string must contain only decimal digits (no sign); got "'
                . self::safePreview($s) . '"'
            );
        }
    }

    /**
     * Validate a limb string that may represent either a signed int64 or an unsigned uint64 half.
     *
     * The lo/loHi/loLo limbs of Int128Parts / Int256Parts are declared as int64 in XDR IDL
     * but carry unsigned uint64 semantics — their value in memory may be negative (as a PHP
     * signed int) when the unsigned uint64 value exceeds PHP_INT_MAX. We accept both positive
     * and negative decimal representations and rely on uint64HalfToGmp() to reconstruct the
     * correct unsigned GMP value.
     *
     * @throws InvalidArgumentException On format violation.
     */
    private static function validateInt64OrUint64HalfString(string $s): void
    {
        // We accept an optional leading minus (for PHP's two's-complement representation
        // of large uint64 values), then only decimal digits.
        self::validateInt64String($s);
    }

    /**
     * Convert a string representing a uint64 value stored as a PHP signed int (possibly negative)
     * to an unsigned GMP integer in [0, 2^64-1].
     *
     * PHP stores uint64 limbs as signed ints; values above PHP_INT_MAX wrap to negative.
     * This helper reverses that wrapping so GMP arithmetic works on the true unsigned value.
     */
    private static function uint64HalfToGmp(string $s): \GMP
    {
        $gmp = gmp_init($s, 10);
        if (gmp_cmp($gmp, 0) < 0) {
            // Negative PHP int: the true unsigned value is gmp + 2^64.
            $gmp = gmp_add($gmp, gmp_pow(2, 64));
        }
        return $gmp;
    }

    /**
     * Convert an unsigned GMP value in [0, 2^64-1] to a signed int64 base-10 string.
     *
     * Values in [0, 2^63-1] are returned as-is. Values in [2^63, 2^64-1] are converted to
     * their two's-complement signed form (i.e. value - 2^64).
     */
    private static function unsignedGmpToSignedInt64String(\GMP $gmp): string
    {
        $bound = gmp_pow(2, 63);
        if (gmp_cmp($gmp, $bound) >= 0) {
            return gmp_strval(gmp_sub($gmp, gmp_pow(2, 64)));
        }
        return gmp_strval($gmp);
    }

    /**
     * Parse and range-validate a signed 128-bit integer string.
     *
     * @throws InvalidArgumentException If the string is not a valid int128.
     */
    private static function parseAndValidateInt128(string $value): \GMP
    {
        self::validateInt64String($value);
        $gmp = gmp_init($value, 10);
        $min = gmp_neg(gmp_pow(2, 127));
        $max = gmp_sub(gmp_pow(2, 127), 1);
        if (gmp_cmp($gmp, $min) < 0 || gmp_cmp($gmp, $max) > 0) {
            throw new InvalidArgumentException(
                'int128 string of length ' . strlen($value) . ' is out of the range [-2^127, 2^127-1]'
            );
        }
        return $gmp;
    }

    /**
     * Parse and range-validate an unsigned 128-bit integer string.
     *
     * @throws InvalidArgumentException If the string is not a valid uint128.
     */
    private static function parseAndValidateUint128(string $value): \GMP
    {
        self::validateUint64String($value);
        $gmp = gmp_init($value, 10);
        $max = gmp_sub(gmp_pow(2, 128), 1);
        if (gmp_cmp($gmp, 0) < 0 || gmp_cmp($gmp, $max) > 0) {
            throw new InvalidArgumentException(
                'uint128 string of length ' . strlen($value) . ' is out of the range [0, 2^128-1]'
            );
        }
        return $gmp;
    }

    /**
     * Parse and range-validate a signed 256-bit integer string.
     *
     * @throws InvalidArgumentException If the string is not a valid int256.
     */
    private static function parseAndValidateInt256(string $value): \GMP
    {
        self::validateInt64String($value);
        $gmp = gmp_init($value, 10);
        $min = gmp_neg(gmp_pow(2, 255));
        $max = gmp_sub(gmp_pow(2, 255), 1);
        if (gmp_cmp($gmp, $min) < 0 || gmp_cmp($gmp, $max) > 0) {
            throw new InvalidArgumentException(
                'int256 string of length ' . strlen($value) . ' is out of the range [-2^255, 2^255-1]'
            );
        }
        return $gmp;
    }

    /**
     * Parse and range-validate an unsigned 256-bit integer string.
     *
     * @throws InvalidArgumentException If the string is not a valid uint256.
     */
    private static function parseAndValidateUint256(string $value): \GMP
    {
        self::validateUint64String($value);
        $gmp = gmp_init($value, 10);
        $max = gmp_sub(gmp_pow(2, 256), 1);
        if (gmp_cmp($gmp, 0) < 0 || gmp_cmp($gmp, $max) > 0) {
            throw new InvalidArgumentException(
                'uint256 string of length ' . strlen($value) . ' is out of the range [0, 2^256-1]'
            );
        }
        return $gmp;
    }

    /**
     * Split a GMP unsigned integer into four 64-bit limbs [hiHi, hiLo, loHi, loLo].
     *
     * The input must already be non-negative (callers apply two's-complement expansion
     * for signed types before calling this).
     *
     * @return \GMP[] Four-element array [hiHi, hiLo, loHi, loLo], each a GMP in [0, 2^64-1].
     */
    private static function splitTo4x64(\GMP $value): array
    {
        $mask64 = gmp_sub(gmp_pow(2, 64), 1);
        $loLo = gmp_and($value, $mask64);
        $loHi = gmp_and(gmp_div_q($value, gmp_pow(2, 64)), $mask64);
        $hiLo = gmp_and(gmp_div_q($value, gmp_pow(2, 128)), $mask64);
        $hiHi = gmp_div_q($value, gmp_pow(2, 192));
        return [$hiHi, $hiLo, $loHi, $loLo];
    }
}
