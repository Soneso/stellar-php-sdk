<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr\Sep51;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Xdr\XdrContractCodeEntryExtV1;
use Soneso\StellarSDK\Xdr\XdrContractExecutable;
use Soneso\StellarSDK\Xdr\XdrContractExecutableBase;
use Soneso\StellarSDK\Xdr\XdrContractExecutableExternalRef;
use Soneso\StellarSDK\Xdr\XdrContractExecutableType;
use Soneso\StellarSDK\Xdr\XdrDataValue;
use Soneso\StellarSDK\Xdr\XdrJsonHelper;
use Soneso\StellarSDK\Xdr\XdrSCAddress;

/**
 * SEP-51 parity tests for hand-written wrappers against their generated
 * counterparts: XdrDataValue, XdrContractExecutable vs
 * XdrContractExecutableBase, and XdrContractCodeEntryExtV1.
 *
 * XdrContractCodeEntryExtV1 is hand-authored because the IDL declares it as
 * an anonymous inline struct inside a union arm, which the generator does not
 * name. Its fromJsonValue must nonetheless enforce the same struct-object key
 * closure the generator emits.
 *
 * XdrDataValue is hand-authored rather than generator-produced (it is the
 * optional opaque-variable wrapper used by ManageData). Its SEP-51 wire form
 * must match what the generator emits for a bare opaque typedef
 * (typedef opaque DataValue<64>):
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
     * generator emits for `typedef opaque DataValue<64>`. These fixtures
     * cover the documented boundary cases:
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
            'XdrDataValue::toJson shape diverged from the opaque-variable'
            . ' typedef contract.'
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
            . ' just as the generator opaque-variable emission does.'
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
     * The generator does not emit this case (its native typedef has no null
     * state), so the hand-written extension must be visibly explicit rather
     * than fall through to ''.
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

    /**
     * The hand-written XdrContractExecutable wrapper shadows the generated
     * base encode()/decode(), so the base EXTERNAL_REF arms must be pinned
     * directly against the wrapper: identical bytes on encode, identical
     * structure on decode.
     */
    public function testContractExecutableExternalRefWrapperMatchesBase(): void
    {
        $owner = XdrSCAddress::forContractId(str_repeat('ab', 32));
        $tag = 'audit-v1';

        $wrapper = XdrContractExecutable::forExternalRef($owner, $tag);

        $base = new XdrContractExecutableBase(
            XdrContractExecutableType::CONTRACT_EXECUTABLE_EXTERNAL_REF()
        );
        $base->setExternalRef(new XdrContractExecutableExternalRef($owner, $tag));

        $this->assertSame(
            $wrapper->encode(),
            $base->encode(),
            'Base EXTERNAL_REF encoding diverged from the hand-written wrapper.'
        );

        $decoded = XdrContractExecutableBase::fromBase64Xdr($base->toBase64Xdr());
        $this->assertSame($base->encode(), $decoded->encode());
        $this->assertSame(
            XdrContractExecutableType::CONTRACT_EXECUTABLE_EXTERNAL_REF,
            $decoded->getType()->getValue()
        );
        $this->assertNotNull($decoded->getExternalRef());
        $this->assertSame($tag, $decoded->getExternalRef()->getTag());
        $this->assertSame($owner->encode(), $decoded->getExternalRef()->getExecutableOwner()->encode());
    }

    /**
     * The EXTERNAL_REF JSON arm is inherited from the base by the wrapper:
     * toJsonValue must emit the single-key {"external_ref": {...}} object and
     * fromJsonValue must rebuild the same bytes from it.
     */
    public function testContractExecutableExternalRefJsonArmRoundTrips(): void
    {
        $owner = XdrSCAddress::forContractId(str_repeat('cd', 32));
        $base = new XdrContractExecutableBase(
            XdrContractExecutableType::CONTRACT_EXECUTABLE_EXTERNAL_REF()
        );
        $base->setExternalRef(new XdrContractExecutableExternalRef($owner, 'json-tag'));

        $jsonValue = $base->toJsonValue();
        $this->assertIsArray($jsonValue);
        $this->assertSame(['external_ref'], array_keys($jsonValue));
        $this->assertSame('json-tag', $jsonValue['external_ref']['tag']);

        $restored = XdrContractExecutableBase::fromJsonValue($jsonValue);
        $this->assertSame($base->encode(), $restored->encode());
        $this->assertNotNull($restored->getExternalRef());
        $this->assertSame('json-tag', $restored->getExternalRef()->getTag());

        // The hand-written wrapper inherits the same JSON path.
        $wrapperRestored = XdrContractExecutable::fromJsonValue($jsonValue);
        $this->assertSame($base->encode(), $wrapperRestored->encode());
    }

    /**
     * The wire form of XdrContractCodeEntryExtV1: an `ext` extension point and
     * the `cost_inputs` object.
     *
     * @return array<string, mixed>
     */
    private static function contractCodeEntryExtV1JsonValue(): array
    {
        return [
            'ext' => 'v0',
            'cost_inputs' => [
                'ext' => 'v0',
                'n_instructions' => 1,
                'n_functions' => 2,
                'n_globals' => 3,
                'n_table_entries' => 4,
                'n_types' => 5,
                'n_data_segments' => 6,
                'n_elem_segments' => 7,
                'n_imports' => 8,
                'n_exports' => 9,
                'n_data_segment_bytes' => 10,
            ],
        ];
    }

    public function testContractCodeEntryExtV1AcceptsExactlyItsDeclaredKeys(): void
    {
        $jsonValue = self::contractCodeEntryExtV1JsonValue();
        $decoded = XdrContractCodeEntryExtV1::fromJsonValue($jsonValue);
        $this->assertSame($jsonValue, $decoded->toJsonValue());
    }

    public function testContractCodeEntryExtV1AcceptsSchemaBesideItsDeclaredKeys(): void
    {
        $jsonValue = self::contractCodeEntryExtV1JsonValue();
        $decoded = XdrContractCodeEntryExtV1::fromJsonValue(
            ['$schema' => 'https://schema'] + $jsonValue
        );
        $this->assertSame($jsonValue, $decoded->toJsonValue());
    }

    public function testContractCodeEntryExtV1RejectsAnUnknownKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Unknown field in JSON input for XdrContractCodeEntryExtV1: 'bogus'"
        );
        XdrContractCodeEntryExtV1::fromJsonValue(
            self::contractCodeEntryExtV1JsonValue() + ['bogus' => 1]
        );
    }
}
