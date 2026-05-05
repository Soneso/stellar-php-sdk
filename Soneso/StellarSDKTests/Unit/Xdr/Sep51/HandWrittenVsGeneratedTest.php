<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr\Sep51;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Xdr\XdrDataValue;
use Soneso\StellarSDK\Xdr\XdrJsonHelper;

/**
 * SEP-51 hand-written vs. generator-emitted parity for Category C types.
 *
 * Category C in the SEP-51 plan is the closed set of types whose SEP-51
 * methods are hand-authored on the wrapper class rather than emitted onto
 * a generator-produced *Base.php file. Today the set is exactly one type:
 * XdrDataValue (the optional opaque-variable wrapper used by ManageData).
 *
 * Because the generator never produces a parallel scratch class for this
 * type, the parity check works in the opposite direction: every wire form
 * the hand-written XdrDataValue::toJson can emit must match what the
 * generator's render_scalar_typedef_sep51_methods code path would emit for
 * the bare typedef DataValue<64>:
 *
 *   - non-null inner value -> hex string of the bytes
 *   - null inner value     -> JSON null (hand-written wrapper extension)
 *
 * The fixture set covers the boundary cases the generator treats specially
 * (empty-string -> "" not "0", hex case, max-length 64 bytes), plus one
 * round-trip end-to-end via fromJson to confirm the inverse path uses the
 * same hex codec the generator exposes (XdrJsonHelper::hexToBytes).
 */
class HandWrittenVsGeneratedTest extends TestCase
{
    /**
     * The hand-written XdrDataValue::toJson must produce the same shape the
     * scratch-generator emits for `typedef opaque DataValue<64>`. These
     * fixtures cover the documented boundary cases:
     *
     *   - null inner value (wrapper extension; generator path is "no-op
     *     here, but the contract holds").
     *   - empty bytes (the spec contract says "" not "0" not "00").
     *   - single byte (smallest non-empty payload).
     *   - mid-length payload with mixed byte values.
     *   - 64-byte payload (the typedef's upper length bound).
     *
     * @return iterable<string, array{0: ?string, 1: string}>
     */
    public static function provideRepresentativeFixtures(): iterable
    {
        yield 'null inner value' => [null, 'null'];
        yield 'empty bytes' => ['', '""'];
        yield 'single byte 0x00' => ["\x00", '"00"'];
        yield 'single byte 0xff' => ["\xff", '"ff"'];
        yield 'three bytes ABC' => ['ABC', '"414243"'];
        yield '8 bytes mixed' => [
            "\x01\x02\x03\x04\xfe\xfd\xfc\xfb",
            '"01020304fefdfcfb"',
        ];
        yield '64 bytes max length' => [
            str_repeat("\xab", 64),
            '"' . str_repeat('ab', 64) . '"',
        ];
    }

    /**
     * Subgate (a): direct shape match.
     *
     * @dataProvider provideRepresentativeFixtures
     */
    public function testHandWrittenToJsonShapeMatchesGeneratorContract(
        ?string $innerValue,
        string $expectedJson
    ): void {
        $instance = new XdrDataValue($innerValue);
        $this->assertSame(
            $expectedJson,
            $instance->toJson(),
            'XdrDataValue::toJson shape diverged from the scratch-generator'
            . ' opaque-variable typedef contract.'
        );
    }

    /**
     * Subgate (b): toJsonValue agrees with XdrJsonHelper::bytesToHex used
     * directly. The hand-written code path is structurally identical to the
     * generator's because both call into the same helper for the byte->hex
     * step; the assertion pins that contract.
     *
     * @dataProvider provideRepresentativeFixtures
     */
    public function testHandWrittenDelegatesToBytesToHex(
        ?string $innerValue,
        string $expectedJson
    ): void {
        $instance = new XdrDataValue($innerValue);
        $expectedRaw = $innerValue === null
            ? null
            : XdrJsonHelper::bytesToHex($innerValue);
        $this->assertSame(
            $expectedRaw,
            $instance->toJsonValue(),
            'XdrDataValue::toJsonValue must delegate to XdrJsonHelper::bytesToHex'
            . ' just as the scratch-generator opaque-variable emission does.'
        );
    }

    /**
     * Subgate (c): fromJson round-trip uses the same hex codec the generator
     * exposes (hexToBytes). For each fixture, fromJson(<emit>) must
     * reconstitute the original inner value.
     *
     * @dataProvider provideRepresentativeFixtures
     */
    public function testHandWrittenFromJsonRoundTripsThroughHexToBytes(
        ?string $innerValue,
        string $expectedJson
    ): void {
        $rt = XdrDataValue::fromJson($expectedJson);
        $this->assertSame(
            $innerValue,
            $rt->getValue(),
            'XdrDataValue::fromJson must round-trip the inner value through'
            . ' XdrJsonHelper::hexToBytes byte-identically.'
        );
    }

    /**
     * Subgate (d): the JSON null-arm contract — null inner -> JSON null.
     * The scratch-generator does not emit this case (its native typedef has
     * no null state), so the hand-written extension must be visibly explicit
     * rather than fall through to ''.
     */
    public function testHandWrittenNullArmEmitsJsonNullNotEmptyString(): void
    {
        $instance = new XdrDataValue(null);
        $this->assertSame('null', $instance->toJson());
        $this->assertNull($instance->toJsonValue());

        // And a raw "" must NOT be confused with null on the input side.
        $rtNull = XdrDataValue::fromJson('null');
        $this->assertNull($rtNull->getValue());
        $rtEmpty = XdrDataValue::fromJson('""');
        $this->assertSame('', $rtEmpty->getValue());
        $this->assertNotSame($rtNull->getValue(), $rtEmpty->getValue());
    }

    /**
     * Subgate (e): negative-input parity. The hand-written path rejects
     * non-string non-null inputs with the same exception type the
     * generator-emitted opaque-typedef methods raise.
     */
    public function testHandWrittenRejectsNonStringNonNullInput(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrDataValue::fromJsonValue(123);
    }

    public function testHandWrittenRejectsArrayInput(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        XdrDataValue::fromJsonValue(['not', 'a', 'hex', 'string']);
    }
}
