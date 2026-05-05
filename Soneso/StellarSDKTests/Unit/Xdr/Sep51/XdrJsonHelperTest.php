<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr\Sep51;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Xdr\XdrJsonHelper;

/**
 * Unit tests for XdrJsonHelper — the SEP-51 primitive helper class.
 *
 * Every public method in XdrJsonHelper is covered with:
 *   - positive (golden-value) assertions
 *   - round-trip assertions
 *   - negative (exception) assertions for every documented rejection path
 */
class XdrJsonHelperTest extends TestCase
{
    // =========================================================================
    // escapeString / unescapeString
    // =========================================================================

    public function testEscape_emptyBytes(): void
    {
        $this->assertSame('', XdrJsonHelper::escapeString(''));
        $this->assertSame('', XdrJsonHelper::unescapeString(''));
    }

    public function testEscape_singleNul(): void
    {
        $this->assertSame('\0', XdrJsonHelper::escapeString("\x00"));
        $this->assertSame("\x00", XdrJsonHelper::unescapeString('\0'));
    }

    public function testEscape_singleTab(): void
    {
        $this->assertSame('\t', XdrJsonHelper::escapeString("\x09"));
        $this->assertSame("\x09", XdrJsonHelper::unescapeString('\t'));
    }

    public function testEscape_singleLf(): void
    {
        $this->assertSame('\n', XdrJsonHelper::escapeString("\x0A"));
        $this->assertSame("\x0A", XdrJsonHelper::unescapeString('\n'));
    }

    public function testEscape_singleCr(): void
    {
        $this->assertSame('\r', XdrJsonHelper::escapeString("\x0D"));
        $this->assertSame("\x0D", XdrJsonHelper::unescapeString('\r'));
    }

    public function testEscape_singleBackslash(): void
    {
        $this->assertSame('\\\\', XdrJsonHelper::escapeString("\x5C"));
        $this->assertSame("\x5C", XdrJsonHelper::unescapeString('\\\\'));
    }

    public function testEscape_byte0x01(): void
    {
        $this->assertSame('\x01', XdrJsonHelper::escapeString("\x01"));
        $this->assertSame("\x01", XdrJsonHelper::unescapeString('\x01'));
    }

    public function testEscape_byte0x1B(): void
    {
        $this->assertSame('\x1b', XdrJsonHelper::escapeString("\x1B"));
        $this->assertSame("\x1B", XdrJsonHelper::unescapeString('\x1b'));
    }

    public function testEscape_byte0x1F(): void
    {
        $this->assertSame('\x1f', XdrJsonHelper::escapeString("\x1F"));
        $this->assertSame("\x1F", XdrJsonHelper::unescapeString('\x1f'));
    }

    public function testEscape_space(): void
    {
        // 0x20 is the first printable character and must be emitted verbatim.
        $this->assertSame(' ', XdrJsonHelper::escapeString("\x20"));
    }

    public function testEscape_tilde(): void
    {
        // 0x7E is the last printable character and must be emitted verbatim.
        $this->assertSame('~', XdrJsonHelper::escapeString("\x7E"));
    }

    public function testEscape_byte0x7F_DEL(): void
    {
        // DEL (0x7F) is just above the printable range and must be hex-escaped.
        // This is a common off-by-one trap: the printable range ends at 0x7E, not 0x7F.
        $this->assertSame('\x7f', XdrJsonHelper::escapeString("\x7F"));
        $this->assertSame("\x7F", XdrJsonHelper::unescapeString('\x7f'));
    }

    public function testEscape_byte0x80(): void
    {
        $this->assertSame('\x80', XdrJsonHelper::escapeString("\x80"));
        $this->assertSame("\x80", XdrJsonHelper::unescapeString('\x80'));
    }

    public function testEscape_byte0xFF(): void
    {
        $this->assertSame('\xff', XdrJsonHelper::escapeString("\xFF"));
        $this->assertSame("\xFF", XdrJsonHelper::unescapeString('\xff'));
    }

    public function testEscape_specHelloC3World(): void
    {
        // Spec example from SEP-0051 §String: bytes hello + 0xC3 + world.
        $input = 'hello' . "\xC3" . 'world';
        $this->assertSame('hello\xc3world', XdrJsonHelper::escapeString($input));
        $this->assertSame($input, XdrJsonHelper::unescapeString('hello\xc3world'));
    }

    public function testEscape_embeddedNul(): void
    {
        $input = 'a' . "\x00" . 'b';
        $this->assertSame('a\0b', XdrJsonHelper::escapeString($input));
        $this->assertSame($input, XdrJsonHelper::unescapeString('a\0b'));
    }

    public function testEscape_literalBackslashXEscape(): void
    {
        // The input consists of four literal printable ASCII bytes: \, x, 4, 1.
        // escapeString must escape only the backslash; x, 4, 1 are printable and pass through.
        $input = '\\x41'; // 4 bytes: 0x5C 0x78 0x34 0x31
        $escaped = XdrJsonHelper::escapeString($input);
        // The backslash becomes \\, leaving \\x41.
        $this->assertSame('\\\\x41', $escaped);
        // Round-trip back to the original 4-byte sequence.
        $this->assertSame($input, XdrJsonHelper::unescapeString($escaped));
    }

    public function testEscape_printableRangeRoundTrip(): void
    {
        for ($b = 0x20; $b <= 0x7E; $b++) {
            $input = chr($b);
            $escaped = XdrJsonHelper::escapeString($input);
            $this->assertSame($input, XdrJsonHelper::unescapeString($escaped),
                'Round-trip failed for byte 0x' . dechex($b));
        }
    }

    public function testEscape_nonPrintableRangeRoundTrip(): void
    {
        for ($b = 0x00; $b <= 0x1F; $b++) {
            $input = chr($b);
            $escaped = XdrJsonHelper::escapeString($input);
            $this->assertSame($input, XdrJsonHelper::unescapeString($escaped),
                'Round-trip failed for byte 0x' . dechex($b));
        }
    }

    public function testEscape_highRangeRoundTrip(): void
    {
        for ($b = 0x80; $b <= 0xFF; $b++) {
            $input = chr($b);
            $escaped = XdrJsonHelper::escapeString($input);
            $this->assertSame($input, XdrJsonHelper::unescapeString($escaped),
                'Round-trip failed for byte 0x' . dechex($b));
        }
    }

    public function testUnescape_malformedHexZZ_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::unescapeString('\xZZ');
    }

    public function testUnescape_unknownEscape_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::unescapeString('\q');
    }

    public function testUnescape_truncatedHex_throws(): void
    {
        // \x followed by only one hex digit (not two) is malformed.
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::unescapeString('\x4');
    }

    public function testUnescape_trailingBackslash_throws(): void
    {
        // A lone trailing backslash with no following character is malformed.
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::unescapeString('abc\\');
    }

    // =========================================================================
    // bytesToHex / hexToBytes
    // =========================================================================

    public function testHex_emptyBytes(): void
    {
        // Empty bytes must produce empty string, NOT "0".
        $this->assertSame('', XdrJsonHelper::bytesToHex(''));
        $this->assertSame('', XdrJsonHelper::hexToBytes(''));
    }

    public function testHex_singleByte00(): void
    {
        $this->assertSame('00', XdrJsonHelper::bytesToHex("\x00"));
        $this->assertSame("\x00", XdrJsonHelper::hexToBytes('00'));
    }

    public function testHex_singleByteFF(): void
    {
        // Must produce lowercase hex.
        $this->assertSame('ff', XdrJsonHelper::bytesToHex("\xFF"));
        $this->assertSame("\xFF", XdrJsonHelper::hexToBytes('ff'));
    }

    public function testHex_multiByteRoundTrip(): void
    {
        // Use a deterministic 32-byte sequence (not random) for reproducibility.
        $bytes = '';
        for ($i = 0; $i < 32; $i++) {
            $bytes .= chr($i * 8 % 256);
        }
        $hex = XdrJsonHelper::bytesToHex($bytes);
        $this->assertSame($bytes, XdrJsonHelper::hexToBytes($hex));
    }

    public function testHex_oddLengthThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::hexToBytes('1');
    }

    public function testHex_nonHexThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::hexToBytes('zz');
    }

    public function testHex_uppercaseRejected(): void
    {
        // Documented divergence from py-stellar-base: PHP strictly enforces lowercase.
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::hexToBytes('FF');
    }

    // =========================================================================
    // canonicalJson / ksortRecursive
    // =========================================================================

    public function testCanonical_emptyObject(): void
    {
        $this->assertSame('{}', XdrJsonHelper::canonicalJson('{}'));
    }

    public function testCanonical_emptyArray(): void
    {
        // The {} vs [] distinction must be preserved through normalisation.
        $this->assertSame('[]', XdrJsonHelper::canonicalJson('[]'));
    }

    public function testCanonical_keyOrderSorted(): void
    {
        $this->assertSame('{"a":2,"b":1}', XdrJsonHelper::canonicalJson('{"b":1,"a":2}'));
    }

    public function testCanonical_listOfObjects_sortedWithinElement(): void
    {
        $input = '[{"b":1,"a":2},{"d":4,"c":3}]';
        $this->assertSame('[{"a":2,"b":1},{"c":3,"d":4}]', XdrJsonHelper::canonicalJson($input));
    }

    public function testCanonical_deeplyNested(): void
    {
        $input = '{"z":{"y":{"x":1,"w":2}},"a":[3,2,1]}';
        $result = XdrJsonHelper::canonicalJson($input);
        $decoded = json_decode($result, true);
        // Keys 'a' and 'z' must be sorted.
        $this->assertSame(['a', 'z'], array_keys($decoded));
        // Inner object keys must also be sorted.
        $this->assertSame(['y'], array_keys($decoded['z']));
        $this->assertSame(['w', 'x'], array_keys($decoded['z']['y']));
        // Array elements must preserve order.
        $this->assertSame([3, 2, 1], $decoded['a']);
    }

    public function testCanonical_nestedEmpties(): void
    {
        // Both {} and [] must survive the round-trip with their type intact.
        $input = '{"a":{},"b":[]}';
        $result = XdrJsonHelper::canonicalJson($input);
        $this->assertSame('{"a":{},"b":[]}', $result);
    }

    public function testCanonical_unicodePreserved(): void
    {
        // JSON_UNESCAPED_UNICODE ensures multi-byte characters are not re-escaped.
        $input = '{"a":"héllo"}';
        $result = XdrJsonHelper::canonicalJson($input);
        $this->assertStringContainsString('héllo', $result);
    }

    public function testCanonical_slashesPreserved(): void
    {
        // JSON_UNESCAPED_SLASHES ensures forward slashes are not \/-escaped.
        $input = '{"a":"/path"}';
        $result = XdrJsonHelper::canonicalJson($input);
        $this->assertSame('{"a":"/path"}', $result);
    }

    public function testCanonical_malformedJsonThrows(): void
    {
        $this->expectException(\JsonException::class);
        XdrJsonHelper::canonicalJson('{invalid');
    }

    public function testKsortRecursive_associativeArray(): void
    {
        // The defensive associative-array branch is exercisable by calling
        // ksortRecursive directly with an associative (non-list) PHP array.
        $input = ['b' => 2, 'a' => 1];
        $result = XdrJsonHelper::ksortRecursive($input);
        $this->assertIsArray($result);
        $this->assertSame(['a', 'b'], array_keys($result));
        $this->assertSame(1, $result['a']);
        $this->assertSame(2, $result['b']);
    }

    public function testKsortRecursive_associativeArrayNested(): void
    {
        // Nested associative array: keys must be sorted at every level.
        $input = ['z' => ['y' => 9, 'x' => 8], 'a' => 1];
        $result = XdrJsonHelper::ksortRecursive($input);
        $this->assertSame(['a', 'z'], array_keys($result));
        $this->assertSame(['x', 'y'], array_keys($result['z']));
    }

    // =========================================================================
    // int64ToString / stringToInt64
    // =========================================================================

    public function testInt64_zero(): void
    {
        $this->assertSame('0', XdrJsonHelper::int64ToString(0));
        $this->assertSame(0, XdrJsonHelper::stringToInt64('0'));
    }

    public function testInt64_one(): void
    {
        $this->assertSame('1', XdrJsonHelper::int64ToString(1));
        $this->assertSame(1, XdrJsonHelper::stringToInt64('1'));
    }

    public function testInt64_minusOne(): void
    {
        $this->assertSame('-1', XdrJsonHelper::int64ToString(-1));
        $this->assertSame(-1, XdrJsonHelper::stringToInt64('-1'));
    }

    public function testInt64_max(): void
    {
        $max = 9223372036854775807; // PHP_INT_MAX = 2^63 - 1
        $this->assertSame('9223372036854775807', XdrJsonHelper::int64ToString($max));
        $this->assertSame($max, XdrJsonHelper::stringToInt64('9223372036854775807'));
    }

    public function testInt64_min(): void
    {
        $min = PHP_INT_MIN; // -2^63 = -9223372036854775808
        $this->assertSame('-9223372036854775808', XdrJsonHelper::int64ToString($min));
        $this->assertSame($min, XdrJsonHelper::stringToInt64('-9223372036854775808'));
    }

    public function testInt64_overflowThrows(): void
    {
        // One above INT64_MAX.
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToInt64('9223372036854775808');
    }

    public function testInt64_acceptsBothIntAndString(): void
    {
        $this->assertSame(
            XdrJsonHelper::stringToInt64(42),
            XdrJsonHelper::stringToInt64('42')
        );
    }

    public function testInt64_rejectsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToInt64('');
    }

    public function testInt64_rejectsBareMinus(): void
    {
        // A lone "-" with no digits following must be rejected.
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToInt64('-');
    }

    public function testInt64_rejectsWhitespace(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToInt64(' 1 ');
    }

    public function testInt64_rejectsScientific(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToInt64('1e10');
    }

    public function testInt64_rejectsDecimal(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToInt64('1.0');
    }

    public function testInt64_rejectsHex(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToInt64('0x10');
    }

    public function testInt64_rejectsLeadingPlus(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToInt64('+1');
    }

    public function testInt64_rejectsInternalWhitespace(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToInt64('1 0');
    }

    public function testInt64_rejectsNonNumeric(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToInt64('abc');
    }

    public function testInt64_intvalNotUsed_returnsZeroOnInvalidIsForbidden(): void
    {
        // If intval() were used, intval("abc") would silently return 0.
        // The method must throw instead.
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToInt64('abc');
    }

    public function testInt64_leadingZeroAccepted(): void
    {
        // Leading zeros are allowed per spec (py also accepts them).
        $this->assertSame(1, XdrJsonHelper::stringToInt64('01'));
        $this->assertSame(0, XdrJsonHelper::stringToInt64('00'));
    }

    public function testInt64_negativeZeroAccepted(): void
    {
        // "-0" is a valid decimal representation of 0.
        $this->assertSame(0, XdrJsonHelper::stringToInt64('-0'));
    }

    // =========================================================================
    // uint64ToString / stringToUint64
    // =========================================================================

    public function testUint64_zero(): void
    {
        $this->assertSame('0', XdrJsonHelper::uint64ToString(0));
        $this->assertSame(0, XdrJsonHelper::stringToUint64('0'));
    }

    public function testUint64_one(): void
    {
        $this->assertSame('1', XdrJsonHelper::uint64ToString(1));
        $this->assertSame(1, XdrJsonHelper::stringToUint64('1'));
    }

    public function testUint64_phpIntMax(): void
    {
        // PHP_INT_MAX (2^63-1) is expressible as a positive PHP int.
        $this->assertSame('9223372036854775807', XdrJsonHelper::uint64ToString(PHP_INT_MAX));
        $this->assertSame(PHP_INT_MAX, XdrJsonHelper::stringToUint64('9223372036854775807'));
    }

    public function testUint64_max(): void
    {
        // UINT64_MAX = 2^64-1 = 18446744073709551615.
        // This exceeds PHP_INT_MAX so must be supplied as a string.
        // stringToUint64 returns it as a PHP signed int (negative two's complement).
        $result = XdrJsonHelper::stringToUint64('18446744073709551615');
        $this->assertSame(-1, $result);
    }

    public function testUint64_justAbovePhpIntMax(): void
    {
        // 9223372036854775808 = PHP_INT_MAX + 1 = 2^63.
        // As a PHP signed int this is PHP_INT_MIN (-2^63).
        $result = XdrJsonHelper::stringToUint64('9223372036854775808');
        $this->assertSame(PHP_INT_MIN, $result);
    }

    public function testUint64_overflowThrows(): void
    {
        // One above UINT64_MAX.
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToUint64('18446744073709551616');
    }

    public function testUint64_negativeIntThrows(): void
    {
        // A negative PHP int cannot represent a valid uint64 via the int path.
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToUint64(-1);
    }

    public function testUint64_negativeStringThrows(): void
    {
        // A negative string cannot represent a valid uint64.
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToUint64('-1');
    }

    public function testUint64ToString_negativeThrows(): void
    {
        // uint64ToString with a negative PHP int must throw.
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::uint64ToString(-1);
    }

    public function testUint64_acceptsIntInput(): void
    {
        $this->assertSame(
            XdrJsonHelper::stringToUint64(42),
            XdrJsonHelper::stringToUint64('42')
        );
    }

    public function testUint64_rejectsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToUint64('');
    }

    public function testUint64_rejectsScientific(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToUint64('1e10');
    }

    public function testUint64_rejectsDecimal(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToUint64('1.0');
    }

    public function testUint64_rejectsHex(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToUint64('0x10');
    }

    public function testUint64_rejectsLeadingPlus(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToUint64('+1');
    }

    public function testUint64_rejectsNonNumeric(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToUint64('abc');
    }

    // =========================================================================
    // int128PartsToString / stringToInt128Parts
    // =========================================================================

    public function testInt128_zero(): void
    {
        $this->assertSame('0', XdrJsonHelper::int128PartsToString('0', '0'));
        $parts = XdrJsonHelper::stringToInt128Parts('0');
        $this->assertSame('0', $parts['hi']);
        $this->assertSame('0', $parts['lo']);
    }

    public function testInt128_one(): void
    {
        $this->assertSame('1', XdrJsonHelper::int128PartsToString('0', '1'));
        $parts = XdrJsonHelper::stringToInt128Parts('1');
        $this->assertSame('0', $parts['hi']);
        $this->assertSame('1', $parts['lo']);
    }

    public function testInt128_minusOne(): void
    {
        // In two's complement 128-bit: -1 = hi=-1, lo=UINT64_MAX=18446744073709551615.
        // UINT64_MAX as a PHP int is -1 (two's-complement).
        $this->assertSame('-1', XdrJsonHelper::int128PartsToString('-1', '18446744073709551615'));
    }

    public function testInt128_minusOne_roundTrip(): void
    {
        $parts = XdrJsonHelper::stringToInt128Parts('-1');
        $this->assertSame('-1', $parts['hi']);
        $this->assertSame('18446744073709551615', $parts['lo']);
        $this->assertSame('-1', XdrJsonHelper::int128PartsToString($parts['hi'], $parts['lo']));
    }

    public function testInt128_max(): void
    {
        // INT128_MAX = 2^127 - 1 = 170141183460469231731687303715884105727.
        $int128Max = '170141183460469231731687303715884105727';
        $this->assertSame($int128Max, XdrJsonHelper::int128PartsToString('9223372036854775807', '18446744073709551615'));
    }

    public function testInt128_max_roundTrip(): void
    {
        $int128Max = '170141183460469231731687303715884105727';
        $parts = XdrJsonHelper::stringToInt128Parts($int128Max);
        $this->assertSame('9223372036854775807', $parts['hi']); // INT64_MAX
        $this->assertSame('18446744073709551615', $parts['lo']); // UINT64_MAX
        $this->assertSame($int128Max, XdrJsonHelper::int128PartsToString($parts['hi'], $parts['lo']));
    }

    public function testInt128_min(): void
    {
        // INT128_MIN = -2^127 = -170141183460469231731687303715884105728.
        $int128Min = '-170141183460469231731687303715884105728';
        $this->assertSame($int128Min, XdrJsonHelper::int128PartsToString('-9223372036854775808', '0'));
    }

    public function testInt128_min_roundTrip(): void
    {
        $int128Min = '-170141183460469231731687303715884105728';
        $parts = XdrJsonHelper::stringToInt128Parts($int128Min);
        $this->assertSame('-9223372036854775808', $parts['hi']); // INT64_MIN
        $this->assertSame('0', $parts['lo']);
        $this->assertSame($int128Min, XdrJsonHelper::int128PartsToString($parts['hi'], $parts['lo']));
    }

    public function testInt128_hiOnly(): void
    {
        // hi=42, lo=0 -> value = 42 * 2^64.
        $hiOnly = gmp_strval(gmp_mul(gmp_init('42'), gmp_pow(2, 64)));
        $this->assertSame($hiOnly, XdrJsonHelper::int128PartsToString('42', '0'));
        $parts = XdrJsonHelper::stringToInt128Parts($hiOnly);
        $this->assertSame('42', $parts['hi']);
        $this->assertSame('0', $parts['lo']);
    }

    public function testInt128_loOnly(): void
    {
        // hi=0, lo=12345 -> value = 12345.
        $this->assertSame('12345', XdrJsonHelper::int128PartsToString('0', '12345'));
        $parts = XdrJsonHelper::stringToInt128Parts('12345');
        $this->assertSame('0', $parts['hi']);
        $this->assertSame('12345', $parts['lo']);
    }

    public function testInt128_mixedSign_positiveHiPositiveLo(): void
    {
        $result = XdrJsonHelper::int128PartsToString('1', '1');
        $expected = gmp_strval(gmp_add(gmp_pow(2, 64), gmp_init('1')));
        $this->assertSame($expected, $result);
    }

    public function testInt128_mixedSign_negativeHiPositiveLo(): void
    {
        // hi=-2, lo=0 -> -2 * 2^64.
        $result = XdrJsonHelper::int128PartsToString('-2', '0');
        $expected = gmp_strval(gmp_mul(gmp_init('-2'), gmp_pow(2, 64)));
        $this->assertSame($expected, $result);
    }

    public function testInt128_roundTripBattery(): void
    {
        $values = [
            '0',
            '1',
            '-1',
            '12345678901234567890',
            '-12345678901234567890',
            '170141183460469231731687303715884105727',  // INT128_MAX
            '-170141183460469231731687303715884105728', // INT128_MIN
        ];
        foreach ($values as $v) {
            $parts = XdrJsonHelper::stringToInt128Parts($v);
            $roundTripped = XdrJsonHelper::int128PartsToString($parts['hi'], $parts['lo']);
            $this->assertSame($v, $roundTripped, 'int128 round-trip failed for ' . $v);
        }
    }

    public function testInt128_rejectsNonNumeric(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToInt128Parts('abc');
    }

    public function testInt128_rejectsDecimal(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToInt128Parts('1.5');
    }

    public function testInt128_rejectsScientific(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToInt128Parts('1e10');
    }

    public function testInt128_overflowThrows(): void
    {
        // One above INT128_MAX.
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToInt128Parts('170141183460469231731687303715884105728');
    }

    public function testInt128_underflowThrows(): void
    {
        // One below INT128_MIN.
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToInt128Parts('-170141183460469231731687303715884105729');
    }

    // =========================================================================
    // uint128PartsToString / stringToUint128Parts
    // =========================================================================

    public function testUint128_zero(): void
    {
        $this->assertSame('0', XdrJsonHelper::uint128PartsToString('0', '0'));
        $parts = XdrJsonHelper::stringToUint128Parts('0');
        $this->assertSame('0', $parts['hi']);
        $this->assertSame('0', $parts['lo']);
    }

    public function testUint128_one(): void
    {
        $this->assertSame('1', XdrJsonHelper::uint128PartsToString('0', '1'));
        $parts = XdrJsonHelper::stringToUint128Parts('1');
        $this->assertSame('0', $parts['hi']);
        $this->assertSame('1', $parts['lo']);
    }

    public function testUint128_max(): void
    {
        // UINT128_MAX = 2^128 - 1 = 340282366920938463463374607431768211455.
        $uint128Max = '340282366920938463463374607431768211455';
        $this->assertSame($uint128Max, XdrJsonHelper::uint128PartsToString(
            '18446744073709551615',
            '18446744073709551615'
        ));
        $parts = XdrJsonHelper::stringToUint128Parts($uint128Max);
        $this->assertSame('18446744073709551615', $parts['hi']);
        $this->assertSame('18446744073709551615', $parts['lo']);
    }

    public function testUint128_roundTripBattery(): void
    {
        $values = [
            '0',
            '1',
            '18446744073709551615',
            '18446744073709551616',
            '340282366920938463463374607431768211455', // UINT128_MAX
        ];
        foreach ($values as $v) {
            $parts = XdrJsonHelper::stringToUint128Parts($v);
            $roundTripped = XdrJsonHelper::uint128PartsToString($parts['hi'], $parts['lo']);
            $this->assertSame($v, $roundTripped, 'uint128 round-trip failed for ' . $v);
        }
    }

    public function testUint128_rejectsNonNumeric(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToUint128Parts('abc');
    }

    public function testUint128_rejectsDecimal(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToUint128Parts('1.5');
    }

    public function testUint128_rejectsScientific(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToUint128Parts('1e10');
    }

    public function testUint128_rejectsNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToUint128Parts('-1');
    }

    public function testUint128_overflowThrows(): void
    {
        // One above UINT128_MAX.
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToUint128Parts('340282366920938463463374607431768211456');
    }

    // =========================================================================
    // int256PartsToString / stringToInt256Parts
    // =========================================================================

    public function testInt256_zero(): void
    {
        $this->assertSame('0', XdrJsonHelper::int256PartsToString('0', '0', '0', '0'));
        $parts = XdrJsonHelper::stringToInt256Parts('0');
        $this->assertSame('0', $parts['hiHi']);
        $this->assertSame('0', $parts['hiLo']);
        $this->assertSame('0', $parts['loHi']);
        $this->assertSame('0', $parts['loLo']);
    }

    public function testInt256_one(): void
    {
        $this->assertSame('1', XdrJsonHelper::int256PartsToString('0', '0', '0', '1'));
        $parts = XdrJsonHelper::stringToInt256Parts('1');
        $this->assertSame('0', $parts['hiHi']);
        $this->assertSame('0', $parts['hiLo']);
        $this->assertSame('0', $parts['loHi']);
        $this->assertSame('1', $parts['loLo']);
    }

    public function testInt256_minusOne(): void
    {
        // -1 in signed 256-bit two's complement has all bits set.
        // Per XDR Int256Parts IDL (XdrInt256Parts.php): only hi_hi is signed int64;
        // hi_lo, lo_hi, lo_lo are unsigned uint64.
        // The int256PartsToString input side accepts both signed-wrap ("-1") and
        // unsigned ("18446744073709551615") for the uint64 limbs; both map to all-ones bits.
        $uint64Max = '18446744073709551615';
        // hiHi = -1 (signed int64, all-ones); hiLo/loHi/loLo = UINT64_MAX (unsigned uint64, all-ones).
        $result = XdrJsonHelper::int256PartsToString('-1', $uint64Max, $uint64Max, $uint64Max);
        $this->assertSame('-1', $result);
        // Also verify that the signed-wrap form ('-1') for hiLo is accepted as input.
        $result2 = XdrJsonHelper::int256PartsToString('-1', '-1', $uint64Max, $uint64Max);
        $this->assertSame('-1', $result2);
    }

    public function testInt256_minusOne_roundTrip(): void
    {
        // stringToInt256Parts output must use IDL-canonical forms:
        // hiHi: signed int64 ('-1'); hiLo, loHi, loLo: unsigned uint64 ('18446744073709551615').
        // hi_lo / lo_hi / lo_lo are uint64 per IDL; only hi_hi is signed.
        // Pass the unsigned form for the uint64 limbs into int256PartsToString for round-trip.
        $parts = XdrJsonHelper::stringToInt256Parts('-1');
        $this->assertSame('-1', $parts['hiHi']);                     // only signed limb
        $this->assertSame('18446744073709551615', $parts['hiLo']);    // unsigned uint64 per IDL
        $this->assertSame('18446744073709551615', $parts['loHi']);    // unsigned uint64 per IDL
        $this->assertSame('18446744073709551615', $parts['loLo']);    // unsigned uint64 per IDL
        $this->assertSame('-1', XdrJsonHelper::int256PartsToString(
            $parts['hiHi'], $parts['hiLo'], $parts['loHi'], $parts['loLo']
        ));
    }

    public function testInt256_max(): void
    {
        // INT256_MAX = 2^255 - 1.
        // Bit pattern: top bit 0, rest all 1.
        // hiHi (signed int64):   0x7FFFFFFFFFFFFFFF = 9223372036854775807 = INT64_MAX
        // hiLo (unsigned uint64): 0xFFFFFFFFFFFFFFFF = 18446744073709551615 = UINT64_MAX
        // loHi (unsigned uint64): 0xFFFFFFFFFFFFFFFF = 18446744073709551615
        // loLo (unsigned uint64): 0xFFFFFFFFFFFFFFFF = 18446744073709551615
        // The input side of int256PartsToString accepts both the unsigned string
        // ("18446744073709551615") and the signed-wrap form ("-1") for the uint64 limbs;
        // both map to the same all-ones bit pattern via uint64HalfToGmp.
        $int256Max = gmp_strval(gmp_sub(gmp_pow(2, 255), 1));
        $result = XdrJsonHelper::int256PartsToString(
            '9223372036854775807', // hiHi: signed INT64_MAX
            '18446744073709551615', // hiLo: unsigned UINT64_MAX
            '18446744073709551615',
            '18446744073709551615'
        );
        $this->assertSame($int256Max, $result);
        // Also verify the signed-wrap form '-1' is accepted for hiLo.
        $result2 = XdrJsonHelper::int256PartsToString(
            '9223372036854775807',
            '-1', // hiLo: signed-wrap form of UINT64_MAX
            '18446744073709551615',
            '18446744073709551615'
        );
        $this->assertSame($int256Max, $result2);
    }

    public function testInt256_max_roundTrip(): void
    {
        $int256Max = gmp_strval(gmp_sub(gmp_pow(2, 255), 1));
        $parts = XdrJsonHelper::stringToInt256Parts($int256Max);
        // hiHi = INT64_MAX (signed int64); hiLo/loHi/loLo = UINT64_MAX (unsigned uint64 per IDL).
        // INT256_MAX bit pattern: top bit 0 (positive), remaining 255 bits all 1.
        // hiHi (signed int64): 0x7FFFFFFFFFFFFFFF = 9223372036854775807
        // hiLo (unsigned uint64): 0xFFFFFFFFFFFFFFFF = 18446744073709551615
        // loHi (unsigned uint64): 0xFFFFFFFFFFFFFFFF = 18446744073709551615
        // loLo (unsigned uint64): 0xFFFFFFFFFFFFFFFF = 18446744073709551615
        $this->assertSame('9223372036854775807', $parts['hiHi']);
        $this->assertSame('18446744073709551615', $parts['hiLo']);    // unsigned uint64 per IDL
        $this->assertSame('18446744073709551615', $parts['loHi']);
        $this->assertSame('18446744073709551615', $parts['loLo']);
        $roundTripped = XdrJsonHelper::int256PartsToString(
            $parts['hiHi'], $parts['hiLo'], $parts['loHi'], $parts['loLo']
        );
        $this->assertSame($int256Max, $roundTripped);
    }

    public function testInt256_min(): void
    {
        // INT256_MIN = -2^255.
        $int256Min = gmp_strval(gmp_neg(gmp_pow(2, 255)));
        $result = XdrJsonHelper::int256PartsToString('-9223372036854775808', '0', '0', '0');
        $this->assertSame($int256Min, $result);
    }

    public function testInt256_min_roundTrip(): void
    {
        $int256Min = gmp_strval(gmp_neg(gmp_pow(2, 255)));
        $parts = XdrJsonHelper::stringToInt256Parts($int256Min);
        $this->assertSame('-9223372036854775808', $parts['hiHi']);
        $this->assertSame('0', $parts['hiLo']);
        $this->assertSame('0', $parts['loHi']);
        $this->assertSame('0', $parts['loLo']);
        $roundTripped = XdrJsonHelper::int256PartsToString(
            $parts['hiHi'], $parts['hiLo'], $parts['loHi'], $parts['loLo']
        );
        $this->assertSame($int256Min, $roundTripped);
    }

    public function testInt256_hiHiOnly(): void
    {
        // Only hiHi set: value = hiHi * 2^192.
        $value = gmp_strval(gmp_mul(gmp_init('7'), gmp_pow(2, 192)));
        $parts = XdrJsonHelper::stringToInt256Parts($value);
        $this->assertSame('7', $parts['hiHi']);
        $this->assertSame('0', $parts['hiLo']);
        $this->assertSame('0', $parts['loHi']);
        $this->assertSame('0', $parts['loLo']);
    }

    public function testInt256_hiLoOnly(): void
    {
        // Only hiLo set: value = hiLo * 2^128.
        $value = gmp_strval(gmp_mul(gmp_init('5'), gmp_pow(2, 128)));
        $parts = XdrJsonHelper::stringToInt256Parts($value);
        $this->assertSame('0', $parts['hiHi']);
        $this->assertSame('5', $parts['hiLo']);
        $this->assertSame('0', $parts['loHi']);
        $this->assertSame('0', $parts['loLo']);
    }

    public function testInt256_loHiOnly(): void
    {
        // Only loHi set: value = loHi * 2^64.
        $value = gmp_strval(gmp_mul(gmp_init('3'), gmp_pow(2, 64)));
        $parts = XdrJsonHelper::stringToInt256Parts($value);
        $this->assertSame('0', $parts['hiHi']);
        $this->assertSame('0', $parts['hiLo']);
        $this->assertSame('3', $parts['loHi']);
        $this->assertSame('0', $parts['loLo']);
    }

    public function testInt256_loLoOnly(): void
    {
        // Only loLo set: value = loLo.
        $this->assertSame('99', XdrJsonHelper::int256PartsToString('0', '0', '0', '99'));
        $parts = XdrJsonHelper::stringToInt256Parts('99');
        $this->assertSame('0', $parts['hiHi']);
        $this->assertSame('0', $parts['hiLo']);
        $this->assertSame('0', $parts['loHi']);
        $this->assertSame('99', $parts['loLo']);
    }

    public function testInt256_roundTripBattery(): void
    {
        $int256Max = gmp_strval(gmp_sub(gmp_pow(2, 255), 1));
        $int256Min = gmp_strval(gmp_neg(gmp_pow(2, 255)));
        $values = [
            '0', '1', '-1',
            $int256Max,
            $int256Min,
            '123456789012345678901234567890',
            '-123456789012345678901234567890',
        ];
        foreach ($values as $v) {
            $parts = XdrJsonHelper::stringToInt256Parts($v);
            $roundTripped = XdrJsonHelper::int256PartsToString(
                $parts['hiHi'], $parts['hiLo'], $parts['loHi'], $parts['loLo']
            );
            $this->assertSame($v, $roundTripped, 'int256 round-trip failed for ' . $v);
        }
    }

    public function testInt256_rejectsNonNumeric(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToInt256Parts('abc');
    }

    public function testInt256_rejectsDecimal(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToInt256Parts('1.5');
    }

    public function testInt256_rejectsScientific(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToInt256Parts('1e100');
    }

    public function testInt256_overflowThrows(): void
    {
        $tooBig = gmp_strval(gmp_pow(2, 255)); // INT256_MAX + 1
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToInt256Parts($tooBig);
    }

    public function testInt256_underflowThrows(): void
    {
        $tooSmall = gmp_strval(gmp_neg(gmp_add(gmp_pow(2, 255), 1))); // INT256_MIN - 1
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToInt256Parts($tooSmall);
    }

    // =========================================================================
    // uint256PartsToString / stringToUint256Parts
    // =========================================================================

    public function testUint256_zero(): void
    {
        $this->assertSame('0', XdrJsonHelper::uint256PartsToString('0', '0', '0', '0'));
        $parts = XdrJsonHelper::stringToUint256Parts('0');
        $this->assertSame('0', $parts['hiHi']);
        $this->assertSame('0', $parts['hiLo']);
        $this->assertSame('0', $parts['loHi']);
        $this->assertSame('0', $parts['loLo']);
    }

    public function testUint256_one(): void
    {
        $this->assertSame('1', XdrJsonHelper::uint256PartsToString('0', '0', '0', '1'));
        $parts = XdrJsonHelper::stringToUint256Parts('1');
        $this->assertSame('1', $parts['loLo']);
    }

    public function testUint256_max(): void
    {
        // UINT256_MAX = 2^256 - 1.
        $uint256Max = gmp_strval(gmp_sub(gmp_pow(2, 256), 1));
        $uint64MaxStr = '18446744073709551615';
        $result = XdrJsonHelper::uint256PartsToString(
            $uint64MaxStr, $uint64MaxStr, $uint64MaxStr, $uint64MaxStr
        );
        $this->assertSame($uint256Max, $result);
    }

    public function testUint256_max_roundTrip(): void
    {
        $uint256Max = gmp_strval(gmp_sub(gmp_pow(2, 256), 1));
        $parts = XdrJsonHelper::stringToUint256Parts($uint256Max);
        $uint64MaxStr = '18446744073709551615';
        $this->assertSame($uint64MaxStr, $parts['hiHi']);
        $this->assertSame($uint64MaxStr, $parts['hiLo']);
        $this->assertSame($uint64MaxStr, $parts['loHi']);
        $this->assertSame($uint64MaxStr, $parts['loLo']);
        $roundTripped = XdrJsonHelper::uint256PartsToString(
            $parts['hiHi'], $parts['hiLo'], $parts['loHi'], $parts['loLo']
        );
        $this->assertSame($uint256Max, $roundTripped);
    }

    public function testUint256_hiHiOnly(): void
    {
        $value = gmp_strval(gmp_mul(gmp_init('9'), gmp_pow(2, 192)));
        $parts = XdrJsonHelper::stringToUint256Parts($value);
        $this->assertSame('9', $parts['hiHi']);
        $this->assertSame('0', $parts['hiLo']);
        $this->assertSame('0', $parts['loHi']);
        $this->assertSame('0', $parts['loLo']);
    }

    public function testUint256_hiLoOnly(): void
    {
        $value = gmp_strval(gmp_mul(gmp_init('8'), gmp_pow(2, 128)));
        $parts = XdrJsonHelper::stringToUint256Parts($value);
        $this->assertSame('0', $parts['hiHi']);
        $this->assertSame('8', $parts['hiLo']);
        $this->assertSame('0', $parts['loHi']);
        $this->assertSame('0', $parts['loLo']);
    }

    public function testUint256_loHiOnly(): void
    {
        $value = gmp_strval(gmp_mul(gmp_init('4'), gmp_pow(2, 64)));
        $parts = XdrJsonHelper::stringToUint256Parts($value);
        $this->assertSame('0', $parts['hiHi']);
        $this->assertSame('0', $parts['hiLo']);
        $this->assertSame('4', $parts['loHi']);
        $this->assertSame('0', $parts['loLo']);
    }

    public function testUint256_loLoOnly(): void
    {
        $this->assertSame('77', XdrJsonHelper::uint256PartsToString('0', '0', '0', '77'));
        $parts = XdrJsonHelper::stringToUint256Parts('77');
        $this->assertSame('77', $parts['loLo']);
    }

    public function testUint256_mixedValue(): void
    {
        $value = '100000000000000000000000000000000000000';
        $parts = XdrJsonHelper::stringToUint256Parts($value);
        $roundTripped = XdrJsonHelper::uint256PartsToString(
            $parts['hiHi'], $parts['hiLo'], $parts['loHi'], $parts['loLo']
        );
        $this->assertSame($value, $roundTripped);
    }

    public function testUint256_roundTripBattery(): void
    {
        $uint256Max = gmp_strval(gmp_sub(gmp_pow(2, 256), 1));
        $values = [
            '0', '1',
            '18446744073709551615',
            '340282366920938463463374607431768211455', // UINT128_MAX
            $uint256Max,
            '9999999999999999999999999999999999999',
        ];
        foreach ($values as $v) {
            $parts = XdrJsonHelper::stringToUint256Parts($v);
            $roundTripped = XdrJsonHelper::uint256PartsToString(
                $parts['hiHi'], $parts['hiLo'], $parts['loHi'], $parts['loLo']
            );
            $this->assertSame($v, $roundTripped, 'uint256 round-trip failed for ' . $v);
        }
    }

    public function testUint256_rejectsNonNumeric(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToUint256Parts('abc');
    }

    public function testUint256_rejectsDecimal(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToUint256Parts('1.5');
    }

    public function testUint256_rejectsScientific(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToUint256Parts('1e100');
    }

    public function testUint256_rejectsNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToUint256Parts('-1');
    }

    public function testUint256_overflowThrows(): void
    {
        $tooBig = gmp_strval(gmp_pow(2, 256)); // UINT256_MAX + 1
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToUint256Parts($tooBig);
    }

    // =========================================================================
    // Additional negative cases
    // =========================================================================

    /**
     * unescapeString must reject uppercase hex digits in \xNN escapes.
     *
     * SEP-51 specifies lowercase hex only. Accepting both cases would create a
     * de-canonicalisation surface (\xff and \xFF mapping to the same byte from
     * two distinct ASCII inputs).
     */
    public function testUnescape_uppercaseHexRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::unescapeString('\xFF');
    }

    public function testUnescape_uppercaseMixedCaseRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::unescapeString('\xAa');
    }

    public function testUnescape_uppercaseBothDigitsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::unescapeString('\xAB');
    }

    /**
     * hexToBytes must reject a hex string with a trailing newline.
     *
     * PHP PCRE treats $ as matching before a trailing \n in default mode, so the
     * previous regex /^[0-9a-f]+$/ accepted "deadbeef\n". The fix uses \A and \z
     * anchors which are not subject to that behaviour.
     */
    public function testHex_trailingNewlineRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::hexToBytes("ab\n");
    }

    /**
     * canonicalJson must throw \JsonException on input exceeding json_decode depth.
     *
     * json_decode enforces a default depth of 512. Inputs nested deeper than that
     * must surface as \JsonException, not silently return null or produce wrong output.
     */
    public function testCanonical_excessDepthThrows(): void
    {
        // Build a JSON string nested 600 levels deep — well beyond json_decode's 512 limit.
        $json = str_repeat('{"a":', 600) . '"x"' . str_repeat('}', 600);
        $this->expectException(\JsonException::class);
        XdrJsonHelper::canonicalJson($json);
    }

    /**
     * canonicalJson inherits json_decode's last-value-wins behaviour on duplicate keys.
     *
     * This pins the behaviour at the spec-vs-impl boundary. json_decode in PHP treats
     * duplicate keys by retaining the last value. Cross-SDK tests that encounter this
     * path should be aware of this documented difference.
     */
    public function testCanonical_duplicateKeyLastWins(): void
    {
        // json_decode (and therefore canonicalJson) retains the last value for duplicate keys.
        // This is the inherited PHP json_decode behaviour; SEP-51 does not address duplicate keys.
        $result = XdrJsonHelper::canonicalJson('{"a":1,"a":2}');
        $decoded = json_decode($result, true);
        $this->assertSame(2, $decoded['a'], 'Duplicate key should retain last value (json_decode behaviour)');
    }

    /**
     * escapeString/unescapeString must handle embedded NUL bytes correctly.
     *
     * NUL (0x00) must be escaped as \0 and must round-trip back to the original byte.
     */
    public function testEscape_embeddedNulInPostEscape(): void
    {
        // A string with embedded NUL bytes must escape them as \0.
        $input = "before\x00after\x00end";
        $escaped = XdrJsonHelper::escapeString($input);
        $this->assertStringContainsString('\0', $escaped);
        $this->assertSame($input, XdrJsonHelper::unescapeString($escaped));
    }

    /**
     * stringToInt128Parts must reject hex notation and non-numeric strings.
     *
     * The validator fires before gmp_init; hex notation ("0x10") must be rejected
     * because "0" passes ctype_digit but the "x10" suffix fails. "abc" must also throw.
     * Same applies to int256/uint variants.
     */
    public function testGmp_typeConfusionAttempt_int128(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToInt128Parts('0x10');
    }

    public function testGmp_typeConfusionAttempt_int128_nonNumeric(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToInt128Parts('abc');
    }

    public function testGmp_typeConfusionAttempt_int256(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToInt256Parts('0x10');
    }

    public function testGmp_typeConfusionAttempt_uint128(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToUint128Parts('0x10');
    }

    public function testGmp_typeConfusionAttempt_uint256(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrJsonHelper::stringToUint256Parts('0x10');
    }

    /**
     * Exception messages from stringToInt64 must be bounded in size.
     *
     * Embedding unbounded user input in exception messages enables log-amplification DoS.
     * The message length must not grow proportionally with the input string length.
     */
    public function testException_messageBoundedSize(): void
    {
        // A 100,000-character numeric string that is valid decimal format but exceeds int64 range.
        $huge = str_repeat('9', 100000);
        try {
            XdrJsonHelper::stringToInt64($huge);
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (\InvalidArgumentException $e) {
            // The message must be short — not proportional to the 100,000-char input.
            // Allow up to 200 characters for the complete error message.
            $this->assertLessThan(
                200,
                strlen($e->getMessage()),
                'Exception message grew proportionally with input (log-amplification risk)'
            );
        }
    }

    /**
     * Format-failure path: a long non-numeric input must trigger the safePreview
     * truncation branch (stringToInt64 -> validateInt64String format error).
     *
     * Range-failure inputs (all-digits) bypass the format path and use length-only
     * messages; only format failures route through safePreview, so this test is
     * required to exercise the truncation suffix.
     */
    public function testException_safePreviewTruncatesLongFormatError(): void
    {
        $longNonNumeric = str_repeat('a', 200);
        try {
            XdrJsonHelper::stringToInt64($longNonNumeric);
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (\InvalidArgumentException $e) {
            $message = $e->getMessage();
            // Truncated preview suffix appended.
            $this->assertStringContainsString('...', $message);
            // Bounded total length.
            $this->assertLessThan(200, strlen($message));
        }
    }

    /**
     * Public-API contract: safePreview must be callable from outside the class.
     *
     * Generator-emitted enum classes route their fromJsonValue default arm
     * through XdrJsonHelper::safePreview rather than carrying a per-class
     * preview helper. The visibility on safePreview must therefore be public.
     * One short-input round-trip and one long-input truncation assertion are
     * sufficient to lock in the public contract; broader behavioural coverage
     * lives in the dedicated truncation test above.
     */
    public function testSafePreview_isPubliclyCallableForGeneratorEmittedCode(): void
    {
        // Short input: returned verbatim.
        $this->assertSame('hello', XdrJsonHelper::safePreview('hello'));
        // Long input: truncated to 80 chars total, with the documented "..." suffix.
        $long = str_repeat('a', 200);
        $preview = XdrJsonHelper::safePreview($long);
        $this->assertSame(80, strlen($preview));
        $this->assertStringEndsWith('...', $preview);
    }

    /**
     * Control characters (0x00-0x1F, 0x7F) must be escaped to their hex form.
     *
     * Echoing attacker-controlled bytes verbatim in exception messages opens
     * an injection vector when those messages are written to a log viewer
     * that interprets ANSI escape sequences or other terminal control codes.
     * safePreview is the single chokepoint for unsanitised user input flowing
     * into exception text, so it owns this defense.
     */
    public function testSafePreview_escapesControlCharacters(): void
    {
        // ANSI escape (0x1B), tab (0x09), newline (0x0A), carriage return (0x0D),
        // null (0x00), DEL (0x7F): all replaced with backslash-x hex escapes.
        $input = "ok\x1B[31mERR\x09tab\x0Anl\x0Dcr\x00null\x7Fdel";
        $preview = XdrJsonHelper::safePreview($input);
        $this->assertStringNotContainsString("\x1B", $preview);
        $this->assertStringNotContainsString("\x09", $preview);
        $this->assertStringNotContainsString("\x0A", $preview);
        $this->assertStringNotContainsString("\x0D", $preview);
        $this->assertStringNotContainsString("\x00", $preview);
        $this->assertStringNotContainsString("\x7F", $preview);
        $this->assertStringContainsString('\\x1B', $preview);
        $this->assertStringContainsString('\\x09', $preview);
        $this->assertStringContainsString('\\x0A', $preview);
        $this->assertStringContainsString('\\x00', $preview);
        $this->assertStringContainsString('\\x7F', $preview);
    }

    /**
     * Printable ASCII and high-bit bytes (UTF-8) pass through unchanged.
     *
     * The control-char filter must not affect text that callers are expected
     * to render literally (struct field names, arm keys, error labels).
     */
    public function testSafePreview_preservesPrintableAndHighBitBytes(): void
    {
        $printable = ' !"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcd';
        $this->assertSame($printable, XdrJsonHelper::safePreview($printable));
        // UTF-8 multi-byte sequences (high-bit bytes) must remain intact.
        $utf8 = "caf\xC3\xA9 — 中文";
        $this->assertSame($utf8, XdrJsonHelper::safePreview($utf8));
    }

    /**
     * ksortRecursive must throw when the depth bound is exceeded.
     *
     * Public callers constructing deeply-nested arrays by hand and passing them
     * to ksortRecursive directly (not via canonicalJson) can exhaust the stack
     * without a guard. The $depth parameter must enforce the limit.
     */
    public function testKsortRecursive_excessDepthThrows(): void
    {
        // Build a 10-level-deep object and call ksortRecursive with depth=3.
        // Any depth < the nesting level must trigger the guard.
        $value = null;
        for ($i = 0; $i < 10; $i++) {
            $obj = new \stdClass();
            $obj->a = $value;
            $value = $obj;
        }
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/maximum recursion depth/');
        XdrJsonHelper::ksortRecursive($value, 3);
    }
}
