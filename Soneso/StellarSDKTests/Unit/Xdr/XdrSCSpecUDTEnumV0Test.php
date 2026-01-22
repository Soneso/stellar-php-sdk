<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTEnumCaseV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTEnumV0;

/**
 * Unit tests for XdrSCSpecUDTEnumV0 and XdrSCSpecUDTEnumCaseV0
 *
 * Tests constructor, encode/decode, and getters/setters for
 * Soroban contract specification enum types.
 */
class XdrSCSpecUDTEnumV0Test extends TestCase
{
    // XdrSCSpecUDTEnumV0 Tests

    public function testConstructor(): void
    {
        $case1 = new XdrSCSpecUDTEnumCaseV0("First case", "VALUE_ONE", 0);
        $case2 = new XdrSCSpecUDTEnumCaseV0("Second case", "VALUE_TWO", 1);

        $enum = new XdrSCSpecUDTEnumV0(
            "Test enum documentation",
            "test_lib",
            "TestEnum",
            [$case1, $case2]
        );

        $this->assertEquals("Test enum documentation", $enum->doc);
        $this->assertEquals("test_lib", $enum->lib);
        $this->assertEquals("TestEnum", $enum->name);
        $this->assertCount(2, $enum->cases);
    }

    public function testEncodeDecodeRoundTrip(): void
    {
        $case1 = new XdrSCSpecUDTEnumCaseV0("Case A doc", "CASE_A", 0);
        $case2 = new XdrSCSpecUDTEnumCaseV0("Case B doc", "CASE_B", 1);
        $case3 = new XdrSCSpecUDTEnumCaseV0("Case C doc", "CASE_C", 2);

        $original = new XdrSCSpecUDTEnumV0(
            "Enum documentation",
            "my_lib",
            "MyEnum",
            [$case1, $case2, $case3]
        );

        $encoded = $original->encode();
        $decoded = XdrSCSpecUDTEnumV0::decode(new XdrBuffer($encoded));

        $this->assertEquals($original->doc, $decoded->doc);
        $this->assertEquals($original->lib, $decoded->lib);
        $this->assertEquals($original->name, $decoded->name);
        $this->assertCount(3, $decoded->cases);
        $this->assertEquals("CASE_A", $decoded->cases[0]->name);
        $this->assertEquals("CASE_B", $decoded->cases[1]->name);
        $this->assertEquals("CASE_C", $decoded->cases[2]->name);
    }

    public function testEncodeDecodeEmptyCases(): void
    {
        $original = new XdrSCSpecUDTEnumV0(
            "Empty enum",
            "lib",
            "EmptyEnum",
            []
        );

        $encoded = $original->encode();
        $decoded = XdrSCSpecUDTEnumV0::decode(new XdrBuffer($encoded));

        $this->assertEquals("Empty enum", $decoded->doc);
        $this->assertEquals("EmptyEnum", $decoded->name);
        $this->assertEmpty($decoded->cases);
    }

    public function testGetDoc(): void
    {
        $enum = new XdrSCSpecUDTEnumV0("doc", "lib", "name", []);
        $this->assertEquals("doc", $enum->getDoc());
    }

    public function testSetDoc(): void
    {
        $enum = new XdrSCSpecUDTEnumV0("old doc", "lib", "name", []);
        $enum->setDoc("new doc");
        $this->assertEquals("new doc", $enum->getDoc());
    }

    public function testGetLib(): void
    {
        $enum = new XdrSCSpecUDTEnumV0("doc", "my_lib", "name", []);
        $this->assertEquals("my_lib", $enum->getLib());
    }

    public function testSetLib(): void
    {
        $enum = new XdrSCSpecUDTEnumV0("doc", "old_lib", "name", []);
        $enum->setLib("new_lib");
        $this->assertEquals("new_lib", $enum->getLib());
    }

    public function testGetName(): void
    {
        $enum = new XdrSCSpecUDTEnumV0("doc", "lib", "MyEnumName", []);
        $this->assertEquals("MyEnumName", $enum->getName());
    }

    public function testSetName(): void
    {
        $enum = new XdrSCSpecUDTEnumV0("doc", "lib", "OldName", []);
        $enum->setName("NewName");
        $this->assertEquals("NewName", $enum->getName());
    }

    public function testGetCases(): void
    {
        $case = new XdrSCSpecUDTEnumCaseV0("doc", "CASE", 0);
        $enum = new XdrSCSpecUDTEnumV0("doc", "lib", "name", [$case]);

        $cases = $enum->getCases();
        $this->assertCount(1, $cases);
        $this->assertEquals("CASE", $cases[0]->name);
    }

    public function testSetCases(): void
    {
        $enum = new XdrSCSpecUDTEnumV0("doc", "lib", "name", []);
        $this->assertEmpty($enum->getCases());

        $newCase = new XdrSCSpecUDTEnumCaseV0("new doc", "NEW_CASE", 5);
        $enum->setCases([$newCase]);

        $this->assertCount(1, $enum->getCases());
        $this->assertEquals("NEW_CASE", $enum->getCases()[0]->name);
    }

    // XdrSCSpecUDTEnumCaseV0 Tests

    public function testCaseConstructor(): void
    {
        $case = new XdrSCSpecUDTEnumCaseV0("Case documentation", "MY_CASE", 42);

        $this->assertEquals("Case documentation", $case->doc);
        $this->assertEquals("MY_CASE", $case->name);
        $this->assertEquals(42, $case->value);
    }

    public function testCaseEncodeDecodeRoundTrip(): void
    {
        $original = new XdrSCSpecUDTEnumCaseV0("Test case", "TEST_VALUE", 123);

        $encoded = $original->encode();
        $decoded = XdrSCSpecUDTEnumCaseV0::decode(new XdrBuffer($encoded));

        $this->assertEquals($original->doc, $decoded->doc);
        $this->assertEquals($original->name, $decoded->name);
        $this->assertEquals($original->value, $decoded->value);
    }

    public function testCaseGetDoc(): void
    {
        $case = new XdrSCSpecUDTEnumCaseV0("case doc", "name", 0);
        $this->assertEquals("case doc", $case->getDoc());
    }

    public function testCaseSetDoc(): void
    {
        $case = new XdrSCSpecUDTEnumCaseV0("old", "name", 0);
        $case->setDoc("new");
        $this->assertEquals("new", $case->getDoc());
    }

    public function testCaseGetName(): void
    {
        $case = new XdrSCSpecUDTEnumCaseV0("doc", "CASE_NAME", 0);
        $this->assertEquals("CASE_NAME", $case->getName());
    }

    public function testCaseSetName(): void
    {
        $case = new XdrSCSpecUDTEnumCaseV0("doc", "OLD_NAME", 0);
        $case->setName("NEW_NAME");
        $this->assertEquals("NEW_NAME", $case->getName());
    }

    public function testCaseGetValue(): void
    {
        $case = new XdrSCSpecUDTEnumCaseV0("doc", "name", 99);
        $this->assertEquals(99, $case->getValue());
    }

    public function testCaseSetValue(): void
    {
        $case = new XdrSCSpecUDTEnumCaseV0("doc", "name", 0);
        $case->setValue(100);
        $this->assertEquals(100, $case->getValue());
    }

    public function testEnumWithManyCases(): void
    {
        $cases = [];
        for ($i = 0; $i < 10; $i++) {
            $cases[] = new XdrSCSpecUDTEnumCaseV0("Doc $i", "CASE_$i", $i);
        }

        $original = new XdrSCSpecUDTEnumV0("Large enum", "lib", "LargeEnum", $cases);

        $encoded = $original->encode();
        $decoded = XdrSCSpecUDTEnumV0::decode(new XdrBuffer($encoded));

        $this->assertCount(10, $decoded->cases);
        for ($i = 0; $i < 10; $i++) {
            $this->assertEquals("CASE_$i", $decoded->cases[$i]->name);
            $this->assertEquals($i, $decoded->cases[$i]->value);
        }
    }
}
