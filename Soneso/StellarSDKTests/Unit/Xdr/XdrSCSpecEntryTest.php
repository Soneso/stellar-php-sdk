<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrSCSpecEntry;
use Soneso\StellarSDK\Xdr\XdrSCSpecEntryKind;
use Soneso\StellarSDK\Xdr\XdrSCSpecEventV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecFunctionInputV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecFunctionV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeDef;
use Soneso\StellarSDK\Xdr\XdrSCSpecType;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTEnumCaseV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTEnumV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTErrorEnumCaseV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTErrorEnumV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTStructFieldV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTStructV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionCaseV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionCaseVoidV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecEventDataFormat;

/**
 * Unit tests for XdrSCSpecEntry
 *
 * Tests encoding, decoding, factory methods, and getters/setters
 * for all SC spec entry types.
 */
class XdrSCSpecEntryTest extends TestCase
{
    // Factory Method Tests

    public function testForFunctionV0(): void
    {
        $function = $this->createFunction();
        $entry = XdrSCSpecEntry::forFunctionV0($function);

        $this->assertEquals(XdrSCSpecEntryKind::SC_SPEC_ENTRY_FUNCTION_V0, $entry->getType()->value);
        $this->assertNotNull($entry->getFunctionV0());
        $this->assertEquals("test_function", $entry->getFunctionV0()->getName());
    }

    public function testForUDTStructV0(): void
    {
        $struct = $this->createStruct();
        $entry = XdrSCSpecEntry::forUDTStructV0($struct);

        $this->assertEquals(XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_STRUCT_V0, $entry->getType()->value);
        $this->assertNotNull($entry->getUdtStructV0());
        $this->assertEquals("TestStruct", $entry->getUdtStructV0()->name);
    }

    public function testForUDTUnionV0(): void
    {
        $union = $this->createUnion();
        $entry = XdrSCSpecEntry::forUDTUnionV0($union);

        $this->assertEquals(XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_UNION_V0, $entry->getType()->value);
        $this->assertNotNull($entry->getUdtUnionV0());
        $this->assertEquals("TestUnion", $entry->getUdtUnionV0()->name);
    }

    public function testForUDTEnumV0(): void
    {
        $enum = $this->createEnum();
        $entry = XdrSCSpecEntry::forUDTEnumV0($enum);

        $this->assertEquals(XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_ENUM_V0, $entry->getType()->value);
        $this->assertNotNull($entry->getUdtEnumV0());
        $this->assertEquals("TestEnum", $entry->getUdtEnumV0()->name);
    }

    public function testForUDTErrorEnumV0(): void
    {
        $errorEnum = $this->createErrorEnum();
        $entry = XdrSCSpecEntry::forUDTErrorEnumV0($errorEnum);

        $this->assertEquals(XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_ERROR_ENUM_V0, $entry->getType()->value);
        $this->assertNotNull($entry->getUdtErrorEnumV0());
        $this->assertEquals("TestErrorEnum", $entry->getUdtErrorEnumV0()->name);
    }

    public function testForEventV0(): void
    {
        $event = $this->createEvent();
        $entry = XdrSCSpecEntry::forEventV0($event);

        $this->assertEquals(XdrSCSpecEntryKind::SC_SPEC_ENTRY_EVENT_V0, $entry->getType()->value);
        $this->assertNotNull($entry->getEventV0());
        $this->assertEquals("TestEvent", $entry->getEventV0()->name);
    }

    // Encode/Decode Round Trip Tests

    public function testFunctionV0EncodeDecodeRoundTrip(): void
    {
        $function = $this->createFunction();
        $original = XdrSCSpecEntry::forFunctionV0($function);

        $encoded = $original->encode();
        $decoded = XdrSCSpecEntry::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCSpecEntryKind::SC_SPEC_ENTRY_FUNCTION_V0, $decoded->getType()->value);
        $this->assertNotNull($decoded->getFunctionV0());
        $this->assertEquals("test_function", $decoded->getFunctionV0()->getName());
    }

    public function testUDTStructV0EncodeDecodeRoundTrip(): void
    {
        $struct = $this->createStruct();
        $original = XdrSCSpecEntry::forUDTStructV0($struct);

        $encoded = $original->encode();
        $decoded = XdrSCSpecEntry::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_STRUCT_V0, $decoded->getType()->value);
        $this->assertNotNull($decoded->getUdtStructV0());
        $this->assertEquals("TestStruct", $decoded->getUdtStructV0()->name);
    }

    public function testUDTUnionV0EncodeDecodeRoundTrip(): void
    {
        $union = $this->createUnion();
        $original = XdrSCSpecEntry::forUDTUnionV0($union);

        $encoded = $original->encode();
        $decoded = XdrSCSpecEntry::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_UNION_V0, $decoded->getType()->value);
        $this->assertNotNull($decoded->getUdtUnionV0());
        $this->assertEquals("TestUnion", $decoded->getUdtUnionV0()->name);
    }

    public function testUDTEnumV0EncodeDecodeRoundTrip(): void
    {
        $enum = $this->createEnum();
        $original = XdrSCSpecEntry::forUDTEnumV0($enum);

        $encoded = $original->encode();
        $decoded = XdrSCSpecEntry::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_ENUM_V0, $decoded->getType()->value);
        $this->assertNotNull($decoded->getUdtEnumV0());
        $this->assertEquals("TestEnum", $decoded->getUdtEnumV0()->name);
    }

    public function testUDTErrorEnumV0EncodeDecodeRoundTrip(): void
    {
        $errorEnum = $this->createErrorEnum();
        $original = XdrSCSpecEntry::forUDTErrorEnumV0($errorEnum);

        $encoded = $original->encode();
        $decoded = XdrSCSpecEntry::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_ERROR_ENUM_V0, $decoded->getType()->value);
        $this->assertNotNull($decoded->getUdtErrorEnumV0());
        $this->assertEquals("TestErrorEnum", $decoded->getUdtErrorEnumV0()->name);
    }

    public function testEventV0EncodeDecodeRoundTrip(): void
    {
        $event = $this->createEvent();
        $original = XdrSCSpecEntry::forEventV0($event);

        $encoded = $original->encode();
        $decoded = XdrSCSpecEntry::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCSpecEntryKind::SC_SPEC_ENTRY_EVENT_V0, $decoded->getType()->value);
        $this->assertNotNull($decoded->getEventV0());
        $this->assertEquals("TestEvent", $decoded->getEventV0()->name);
    }

    // Base64 Round Trip Tests

    public function testToBase64XdrAndFromBase64Xdr(): void
    {
        $function = $this->createFunction();
        $original = XdrSCSpecEntry::forFunctionV0($function);

        $base64 = $original->toBase64Xdr();
        $decoded = XdrSCSpecEntry::fromBase64Xdr($base64);

        $this->assertEquals(XdrSCSpecEntryKind::SC_SPEC_ENTRY_FUNCTION_V0, $decoded->getType()->value);
        $this->assertEquals("test_function", $decoded->getFunctionV0()->getName());
    }

    // Getter/Setter Tests

    public function testSetType(): void
    {
        $entry = new XdrSCSpecEntry(XdrSCSpecEntryKind::FUNCTION_V0());
        $this->assertEquals(XdrSCSpecEntryKind::SC_SPEC_ENTRY_FUNCTION_V0, $entry->getType()->value);

        $entry->setType(XdrSCSpecEntryKind::UDT_STRUCT_V0());
        $this->assertEquals(XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_STRUCT_V0, $entry->getType()->value);
    }

    public function testSetFunctionV0(): void
    {
        $function = $this->createFunction();
        $entry = XdrSCSpecEntry::forFunctionV0($function);
        $this->assertNotNull($entry->getFunctionV0());

        $entry->setFunctionV0(null);
        $this->assertNull($entry->getFunctionV0());
    }

    public function testSetUdtStructV0(): void
    {
        $struct = $this->createStruct();
        $entry = XdrSCSpecEntry::forUDTStructV0($struct);
        $this->assertNotNull($entry->getUdtStructV0());

        $entry->setUdtStructV0(null);
        $this->assertNull($entry->getUdtStructV0());
    }

    public function testSetUdtUnionV0(): void
    {
        $union = $this->createUnion();
        $entry = XdrSCSpecEntry::forUDTUnionV0($union);
        $this->assertNotNull($entry->getUdtUnionV0());

        $entry->setUdtUnionV0(null);
        $this->assertNull($entry->getUdtUnionV0());
    }

    public function testSetUdtEnumV0(): void
    {
        $enum = $this->createEnum();
        $entry = XdrSCSpecEntry::forUDTEnumV0($enum);
        $this->assertNotNull($entry->getUdtEnumV0());

        $entry->setUdtEnumV0(null);
        $this->assertNull($entry->getUdtEnumV0());
    }

    public function testSetUdtErrorEnumV0(): void
    {
        $errorEnum = $this->createErrorEnum();
        $entry = XdrSCSpecEntry::forUDTErrorEnumV0($errorEnum);
        $this->assertNotNull($entry->getUdtErrorEnumV0());

        $entry->setUdtErrorEnumV0(null);
        $this->assertNull($entry->getUdtErrorEnumV0());
    }

    public function testSetEventV0(): void
    {
        $event = $this->createEvent();
        $entry = XdrSCSpecEntry::forEventV0($event);
        $this->assertNotNull($entry->getEventV0());

        $entry->setEventV0(null);
        $this->assertNull($entry->getEventV0());
    }

    // Helper Methods

    private function createFunction(): XdrSCSpecFunctionV0
    {
        $inputType = new XdrSCSpecTypeDef(new XdrSCSpecType(XdrSCSpecType::SC_SPEC_TYPE_BOOL));
        $input = new XdrSCSpecFunctionInputV0("doc", "input1", $inputType);
        $outputType = new XdrSCSpecTypeDef(new XdrSCSpecType(XdrSCSpecType::SC_SPEC_TYPE_BOOL));

        return new XdrSCSpecFunctionV0("Function doc", "test_function", [$input], [$outputType]);
    }

    private function createStruct(): XdrSCSpecUDTStructV0
    {
        $fieldType = new XdrSCSpecTypeDef(new XdrSCSpecType(XdrSCSpecType::SC_SPEC_TYPE_U32));
        $field = new XdrSCSpecUDTStructFieldV0("field doc", "field1", $fieldType);

        return new XdrSCSpecUDTStructV0("Struct doc", "test_lib", "TestStruct", [$field]);
    }

    private function createUnion(): XdrSCSpecUDTUnionV0
    {
        $voidCase = new XdrSCSpecUDTUnionCaseVoidV0("case doc", "Case1");
        $case = XdrSCSpecUDTUnionCaseV0::forVoidCase($voidCase);

        return new XdrSCSpecUDTUnionV0("Union doc", "test_lib", "TestUnion", [$case]);
    }

    private function createEnum(): XdrSCSpecUDTEnumV0
    {
        $case = new XdrSCSpecUDTEnumCaseV0("case doc", "VALUE1", 0);

        return new XdrSCSpecUDTEnumV0("Enum doc", "test_lib", "TestEnum", [$case]);
    }

    private function createErrorEnum(): XdrSCSpecUDTErrorEnumV0
    {
        $case = new XdrSCSpecUDTErrorEnumCaseV0("error doc", "ERROR1", 1);

        return new XdrSCSpecUDTErrorEnumV0("Error enum doc", "test_lib", "TestErrorEnum", [$case]);
    }

    private function createEvent(): XdrSCSpecEventV0
    {
        $dataFormat = new XdrSCSpecEventDataFormat(XdrSCSpecEventDataFormat::SC_SPEC_EVENT_DATA_FORMAT_SINGLE_VALUE);

        return new XdrSCSpecEventV0("Event doc", "test_lib", "TestEvent", ["topic1"], [], $dataFormat);
    }
}
