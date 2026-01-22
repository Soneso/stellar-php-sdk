<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Xdr;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrSCSpecType;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeDef;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeBytesN;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeMap;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeOption;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeResult;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeTuple;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeUDT;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeVec;
use Soneso\StellarSDK\Xdr\XdrSCSpecEntry;
use Soneso\StellarSDK\Xdr\XdrSCSpecEntryKind;
use Soneso\StellarSDK\Xdr\XdrSCSpecFunctionV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecFunctionInputV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTStructV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTStructFieldV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionCaseV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionCaseV0Kind;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionCaseVoidV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionCaseTupleV0;

class XdrSCSpecTest extends TestCase
{
    /**
     * Test XdrSCSpecType encode/decode round-trip
     */
    public function testXdrSCSpecTypeRoundTrip(): void
    {
        $types = [
            XdrSCSpecType::VAL(),
            XdrSCSpecType::BOOL(),
            XdrSCSpecType::VOID(),
            XdrSCSpecType::ERROR(),
            XdrSCSpecType::U32(),
            XdrSCSpecType::I32(),
            XdrSCSpecType::U64(),
            XdrSCSpecType::I64(),
            XdrSCSpecType::TIMEPOINT(),
            XdrSCSpecType::DURATION(),
            XdrSCSpecType::U128(),
            XdrSCSpecType::I128(),
            XdrSCSpecType::U256(),
            XdrSCSpecType::I256(),
            XdrSCSpecType::BYTES(),
            XdrSCSpecType::STRING(),
            XdrSCSpecType::SYMBOL(),
            XdrSCSpecType::ADDRESS(),
            XdrSCSpecType::MUXED_ADDRESS(),
            XdrSCSpecType::OPTION(),
            XdrSCSpecType::RESULT(),
            XdrSCSpecType::VEC(),
            XdrSCSpecType::MAP(),
            XdrSCSpecType::TUPLE(),
            XdrSCSpecType::BYTES_N(),
            XdrSCSpecType::UDT(),
        ];

        foreach ($types as $original) {
            $encoded = $original->encode();
            $this->assertNotEmpty($encoded);

            $decoded = XdrSCSpecType::decode(new XdrBuffer($encoded));
            $this->assertEquals($original->getValue(), $decoded->getValue());

            $reEncoded = $decoded->encode();
            $this->assertEquals($encoded, $reEncoded);
        }
    }

    /**
     * Test XdrSCSpecTypeDef with simple types
     */
    public function testXdrSCSpecTypeDefSimpleTypesRoundTrip(): void
    {
        $simpleTypes = [
            XdrSCSpecTypeDef::BOOL(),
            XdrSCSpecTypeDef::VOID(),
            XdrSCSpecTypeDef::STATUS(),
            XdrSCSpecTypeDef::U32(),
            XdrSCSpecTypeDef::I32(),
            XdrSCSpecTypeDef::U64(),
            XdrSCSpecTypeDef::I64(),
            XdrSCSpecTypeDef::TIMEPOINT(),
            XdrSCSpecTypeDef::DURATION(),
            XdrSCSpecTypeDef::U128(),
            XdrSCSpecTypeDef::I128(),
            XdrSCSpecTypeDef::U256(),
            XdrSCSpecTypeDef::I256(),
            XdrSCSpecTypeDef::BYTES(),
            XdrSCSpecTypeDef::STRING(),
            XdrSCSpecTypeDef::SYMBOL(),
            XdrSCSpecTypeDef::ADDRESS(),
            XdrSCSpecTypeDef::MUXED_ADDRESS(),
        ];

        foreach ($simpleTypes as $original) {
            $encoded = $original->encode();
            $this->assertNotEmpty($encoded);

            $decoded = XdrSCSpecTypeDef::decode(new XdrBuffer($encoded));
            $this->assertEquals($original->getType()->getValue(), $decoded->getType()->getValue());

            $reEncoded = $decoded->encode();
            $this->assertEquals($encoded, $reEncoded);
        }
    }

    /**
     * Test XdrSCSpecTypeBytesN encode/decode round-trip
     */
    public function testXdrSCSpecTypeByteNRoundTrip(): void
    {
        $testValues = [0, 1, 32, 64, 128, 256, 1024, 65535];

        foreach ($testValues as $n) {
            $original = new XdrSCSpecTypeBytesN($n);

            $encoded = $original->encode();
            $this->assertNotEmpty($encoded);

            $decoded = XdrSCSpecTypeBytesN::decode(new XdrBuffer($encoded));
            $this->assertEquals($original->getN(), $decoded->getN());

            $reEncoded = $decoded->encode();
            $this->assertEquals($encoded, $reEncoded);
        }
    }

    /**
     * Test XdrSCSpecTypeDef with BytesN
     */
    public function testXdrSCSpecTypeDefWithBytesNRoundTrip(): void
    {
        $bytesN = new XdrSCSpecTypeBytesN(32);
        $original = XdrSCSpecTypeDef::forBytesN($bytesN);

        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        $decoded = XdrSCSpecTypeDef::decode(new XdrBuffer($encoded));
        $this->assertEquals(XdrSCSpecType::SC_SPEC_TYPE_BYTES_N, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getBytesN());
        $this->assertEquals(32, $decoded->getBytesN()->getN());

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrSCSpecTypeOption encode/decode round-trip
     */
    public function testXdrSCSpecTypeOptionRoundTrip(): void
    {
        $valueType = XdrSCSpecTypeDef::U64();
        $original = new XdrSCSpecTypeOption($valueType);

        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        $decoded = XdrSCSpecTypeOption::decode(new XdrBuffer($encoded));
        $this->assertEquals(
            $original->getValueType()->getType()->getValue(),
            $decoded->getValueType()->getType()->getValue()
        );

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrSCSpecTypeDef with Option
     */
    public function testXdrSCSpecTypeDefWithOptionRoundTrip(): void
    {
        $option = new XdrSCSpecTypeOption(XdrSCSpecTypeDef::STRING());
        $original = XdrSCSpecTypeDef::forOption($option);

        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        $decoded = XdrSCSpecTypeDef::decode(new XdrBuffer($encoded));
        $this->assertEquals(XdrSCSpecType::SC_SPEC_TYPE_OPTION, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getOption());
        $this->assertEquals(
            XdrSCSpecType::SC_SPEC_TYPE_STRING,
            $decoded->getOption()->getValueType()->getType()->getValue()
        );

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrSCSpecTypeVec encode/decode round-trip
     */
    public function testXdrSCSpecTypeVecRoundTrip(): void
    {
        $elementType = XdrSCSpecTypeDef::I32();
        $original = new XdrSCSpecTypeVec($elementType);

        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        $decoded = XdrSCSpecTypeVec::decode(new XdrBuffer($encoded));
        $this->assertEquals(
            $original->getElementType()->getType()->getValue(),
            $decoded->getElementType()->getType()->getValue()
        );

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrSCSpecTypeDef with Vec
     */
    public function testXdrSCSpecTypeDefWithVecRoundTrip(): void
    {
        $vec = new XdrSCSpecTypeVec(XdrSCSpecTypeDef::BYTES());
        $original = XdrSCSpecTypeDef::forVec($vec);

        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        $decoded = XdrSCSpecTypeDef::decode(new XdrBuffer($encoded));
        $this->assertEquals(XdrSCSpecType::SC_SPEC_TYPE_VEC, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getVec());
        $this->assertEquals(
            XdrSCSpecType::SC_SPEC_TYPE_BYTES,
            $decoded->getVec()->getElementType()->getType()->getValue()
        );

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrSCSpecTypeMap encode/decode round-trip
     */
    public function testXdrSCSpecTypeMapRoundTrip(): void
    {
        $keyType = XdrSCSpecTypeDef::STRING();
        $valueType = XdrSCSpecTypeDef::U64();
        $original = new XdrSCSpecTypeMap($keyType, $valueType);

        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        $decoded = XdrSCSpecTypeMap::decode(new XdrBuffer($encoded));
        $this->assertEquals(
            $original->getKeyType()->getType()->getValue(),
            $decoded->getKeyType()->getType()->getValue()
        );
        $this->assertEquals(
            $original->getValueType()->getType()->getValue(),
            $decoded->getValueType()->getType()->getValue()
        );

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrSCSpecTypeDef with Map
     */
    public function testXdrSCSpecTypeDefWithMapRoundTrip(): void
    {
        $map = new XdrSCSpecTypeMap(XdrSCSpecTypeDef::SYMBOL(), XdrSCSpecTypeDef::ADDRESS());
        $original = XdrSCSpecTypeDef::forMap($map);

        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        $decoded = XdrSCSpecTypeDef::decode(new XdrBuffer($encoded));
        $this->assertEquals(XdrSCSpecType::SC_SPEC_TYPE_MAP, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getMap());
        $this->assertEquals(
            XdrSCSpecType::SC_SPEC_TYPE_SYMBOL,
            $decoded->getMap()->getKeyType()->getType()->getValue()
        );
        $this->assertEquals(
            XdrSCSpecType::SC_SPEC_TYPE_ADDRESS,
            $decoded->getMap()->getValueType()->getType()->getValue()
        );

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrSCSpecTypeResult encode/decode round-trip
     */
    public function testXdrSCSpecTypeResultRoundTrip(): void
    {
        $okType = XdrSCSpecTypeDef::U32();
        $errorType = XdrSCSpecTypeDef::STRING();
        $original = new XdrSCSpecTypeResult($okType, $errorType);

        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        $decoded = XdrSCSpecTypeResult::decode(new XdrBuffer($encoded));
        $this->assertEquals(
            $original->getOkType()->getType()->getValue(),
            $decoded->getOkType()->getType()->getValue()
        );
        $this->assertEquals(
            $original->getErrorType()->getType()->getValue(),
            $decoded->getErrorType()->getType()->getValue()
        );

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrSCSpecTypeDef with Result
     */
    public function testXdrSCSpecTypeDefWithResultRoundTrip(): void
    {
        $result = new XdrSCSpecTypeResult(XdrSCSpecTypeDef::BOOL(), XdrSCSpecTypeDef::VOID());
        $original = XdrSCSpecTypeDef::forResult($result);

        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        $decoded = XdrSCSpecTypeDef::decode(new XdrBuffer($encoded));
        $this->assertEquals(XdrSCSpecType::SC_SPEC_TYPE_RESULT, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getResult());
        $this->assertEquals(
            XdrSCSpecType::SC_SPEC_TYPE_BOOL,
            $decoded->getResult()->getOkType()->getType()->getValue()
        );
        $this->assertEquals(
            XdrSCSpecType::SC_SPEC_TYPE_VOID,
            $decoded->getResult()->getErrorType()->getType()->getValue()
        );

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrSCSpecTypeTuple encode/decode round-trip with empty tuple
     */
    public function testXdrSCSpecTypeTupleEmptyRoundTrip(): void
    {
        $original = new XdrSCSpecTypeTuple([]);

        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        $decoded = XdrSCSpecTypeTuple::decode(new XdrBuffer($encoded));
        $this->assertIsArray($decoded->valueTypes);
        $this->assertEmpty($decoded->valueTypes);

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrSCSpecTypeTuple encode/decode round-trip with multiple types
     */
    public function testXdrSCSpecTypeTupleMultipleTypesRoundTrip(): void
    {
        $valueTypes = [
            XdrSCSpecTypeDef::U32(),
            XdrSCSpecTypeDef::STRING(),
            XdrSCSpecTypeDef::BOOL(),
        ];
        $original = new XdrSCSpecTypeTuple($valueTypes);

        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        $decoded = XdrSCSpecTypeTuple::decode(new XdrBuffer($encoded));
        $this->assertCount(3, $decoded->valueTypes);
        $this->assertEquals(
            XdrSCSpecType::SC_SPEC_TYPE_U32,
            $decoded->valueTypes[0]->getType()->getValue()
        );
        $this->assertEquals(
            XdrSCSpecType::SC_SPEC_TYPE_STRING,
            $decoded->valueTypes[1]->getType()->getValue()
        );
        $this->assertEquals(
            XdrSCSpecType::SC_SPEC_TYPE_BOOL,
            $decoded->valueTypes[2]->getType()->getValue()
        );

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrSCSpecTypeDef with Tuple
     */
    public function testXdrSCSpecTypeDefWithTupleRoundTrip(): void
    {
        $tuple = new XdrSCSpecTypeTuple([
            XdrSCSpecTypeDef::I64(),
            XdrSCSpecTypeDef::ADDRESS(),
        ]);
        $original = XdrSCSpecTypeDef::forTuple($tuple);

        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        $decoded = XdrSCSpecTypeDef::decode(new XdrBuffer($encoded));
        $this->assertEquals(XdrSCSpecType::SC_SPEC_TYPE_TUPLE, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getTuple());
        $this->assertCount(2, $decoded->getTuple()->valueTypes);

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrSCSpecTypeUDT encode/decode round-trip
     */
    public function testXdrSCSpecTypeUDTRoundTrip(): void
    {
        $testNames = ["MyStruct", "CustomType", "", "VeryLongUserDefinedTypeNameForTesting"];

        foreach ($testNames as $name) {
            $original = new XdrSCSpecTypeUDT($name);

            $encoded = $original->encode();
            $this->assertNotEmpty($encoded);

            $decoded = XdrSCSpecTypeUDT::decode(new XdrBuffer($encoded));
            $this->assertEquals($original->getName(), $decoded->getName());

            $reEncoded = $decoded->encode();
            $this->assertEquals($encoded, $reEncoded);
        }
    }

    /**
     * Test XdrSCSpecTypeDef with UDT
     */
    public function testXdrSCSpecTypeDefWithUDTRoundTrip(): void
    {
        $udt = new XdrSCSpecTypeUDT("CustomStruct");
        $original = XdrSCSpecTypeDef::forUDT($udt);

        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        $decoded = XdrSCSpecTypeDef::decode(new XdrBuffer($encoded));
        $this->assertEquals(XdrSCSpecType::SC_SPEC_TYPE_UDT, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getUdt());
        $this->assertEquals("CustomStruct", $decoded->getUdt()->getName());

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrSCSpecFunctionInputV0 encode/decode round-trip
     */
    public function testXdrSCSpecFunctionInputV0RoundTrip(): void
    {
        $doc = "Input parameter documentation";
        $name = "amount";
        $type = XdrSCSpecTypeDef::U64();
        $original = new XdrSCSpecFunctionInputV0($doc, $name, $type);

        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        $decoded = XdrSCSpecFunctionInputV0::decode(new XdrBuffer($encoded));
        $this->assertEquals($original->getDoc(), $decoded->getDoc());
        $this->assertEquals($original->getName(), $decoded->getName());
        $this->assertEquals(
            $original->getType()->getType()->getValue(),
            $decoded->getType()->getType()->getValue()
        );

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrSCSpecFunctionInputV0 with empty documentation
     */
    public function testXdrSCSpecFunctionInputV0EmptyDocRoundTrip(): void
    {
        $original = new XdrSCSpecFunctionInputV0("", "param", XdrSCSpecTypeDef::BOOL());

        $encoded = $original->encode();
        $decoded = XdrSCSpecFunctionInputV0::decode(new XdrBuffer($encoded));

        $this->assertEquals("", $decoded->getDoc());
        $this->assertEquals("param", $decoded->getName());

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrSCSpecFunctionV0 encode/decode round-trip
     */
    public function testXdrSCSpecFunctionV0RoundTrip(): void
    {
        $doc = "Transfer tokens from one account to another";
        $name = "transfer";
        $inputs = [
            new XdrSCSpecFunctionInputV0("Sender address", "from", XdrSCSpecTypeDef::ADDRESS()),
            new XdrSCSpecFunctionInputV0("Recipient address", "to", XdrSCSpecTypeDef::ADDRESS()),
            new XdrSCSpecFunctionInputV0("Transfer amount", "amount", XdrSCSpecTypeDef::U64()),
        ];
        $outputs = [XdrSCSpecTypeDef::BOOL()];

        $original = new XdrSCSpecFunctionV0($doc, $name, $inputs, $outputs);

        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        $decoded = XdrSCSpecFunctionV0::decode(new XdrBuffer($encoded));
        $this->assertEquals($original->getDoc(), $decoded->getDoc());
        $this->assertEquals($original->getName(), $decoded->getName());
        $this->assertCount(3, $decoded->getInputs());
        $this->assertEquals("from", $decoded->getInputs()[0]->getName());
        $this->assertEquals("to", $decoded->getInputs()[1]->getName());
        $this->assertEquals("amount", $decoded->getInputs()[2]->getName());
        $this->assertCount(1, $decoded->getOutputs());

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrSCSpecFunctionV0 with no inputs and outputs
     */
    public function testXdrSCSpecFunctionV0EmptyInputsOutputsRoundTrip(): void
    {
        $original = new XdrSCSpecFunctionV0("", "initialize", [], []);

        $encoded = $original->encode();
        $decoded = XdrSCSpecFunctionV0::decode(new XdrBuffer($encoded));

        $this->assertEquals("initialize", $decoded->getName());
        $this->assertEmpty($decoded->getInputs());
        $this->assertEmpty($decoded->getOutputs());

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrSCSpecUDTStructFieldV0 encode/decode round-trip
     */
    public function testXdrSCSpecUDTStructFieldV0RoundTrip(): void
    {
        $doc = "User balance";
        $name = "balance";
        $type = XdrSCSpecTypeDef::I128();
        $original = new XdrSCSpecUDTStructFieldV0($doc, $name, $type);

        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        $decoded = XdrSCSpecUDTStructFieldV0::decode(new XdrBuffer($encoded));
        $this->assertEquals($original->getDoc(), $decoded->getDoc());
        $this->assertEquals($original->getName(), $decoded->getName());
        $this->assertEquals(
            $original->getType()->getType()->getValue(),
            $decoded->getType()->getType()->getValue()
        );

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrSCSpecUDTStructV0 encode/decode round-trip
     */
    public function testXdrSCSpecUDTStructV0RoundTrip(): void
    {
        $doc = "User account struct";
        $lib = "mylib";
        $name = "Account";
        $fields = [
            new XdrSCSpecUDTStructFieldV0("Account owner", "owner", XdrSCSpecTypeDef::ADDRESS()),
            new XdrSCSpecUDTStructFieldV0("Account balance", "balance", XdrSCSpecTypeDef::I128()),
            new XdrSCSpecUDTStructFieldV0("Is active", "active", XdrSCSpecTypeDef::BOOL()),
        ];

        $original = new XdrSCSpecUDTStructV0($doc, $lib, $name, $fields);

        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        $decoded = XdrSCSpecUDTStructV0::decode(new XdrBuffer($encoded));
        $this->assertEquals($original->getDoc(), $decoded->getDoc());
        $this->assertEquals($original->getLib(), $decoded->getLib());
        $this->assertEquals($original->getName(), $decoded->getName());
        $this->assertCount(3, $decoded->getFields());
        $this->assertEquals("owner", $decoded->getFields()[0]->getName());
        $this->assertEquals("balance", $decoded->getFields()[1]->getName());
        $this->assertEquals("active", $decoded->getFields()[2]->getName());

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrSCSpecUDTStructV0 with empty fields
     */
    public function testXdrSCSpecUDTStructV0EmptyFieldsRoundTrip(): void
    {
        $original = new XdrSCSpecUDTStructV0("Empty struct", "", "EmptyStruct", []);

        $encoded = $original->encode();
        $decoded = XdrSCSpecUDTStructV0::decode(new XdrBuffer($encoded));

        $this->assertEquals("EmptyStruct", $decoded->getName());
        $this->assertEmpty($decoded->getFields());

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrSCSpecUDTUnionV0 encode/decode round-trip
     */
    public function testXdrSCSpecUDTUnionV0RoundTrip(): void
    {
        $doc = "Result union type";
        $lib = "contractlib";
        $name = "Result";

        $voidCase = new XdrSCSpecUDTUnionCaseVoidV0("Success case", "Ok");
        $case1 = XdrSCSpecUDTUnionCaseV0::forVoidCase($voidCase);

        $tupleTypes = [XdrSCSpecTypeDef::STRING()];
        $tupleCase = new XdrSCSpecUDTUnionCaseTupleV0("Error case", "Err", $tupleTypes);
        $case2 = XdrSCSpecUDTUnionCaseV0::forTupleCase($tupleCase);

        $cases = [$case1, $case2];

        $original = new XdrSCSpecUDTUnionV0($doc, $lib, $name, $cases);

        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        $decoded = XdrSCSpecUDTUnionV0::decode(new XdrBuffer($encoded));
        $this->assertEquals($original->getDoc(), $decoded->getDoc());
        $this->assertEquals($original->getLib(), $decoded->getLib());
        $this->assertEquals($original->getName(), $decoded->getName());
        $this->assertCount(2, $decoded->getCases());

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrSCSpecEntry with FunctionV0
     */
    public function testXdrSCSpecEntryFunctionV0RoundTrip(): void
    {
        $function = new XdrSCSpecFunctionV0(
            "Get balance",
            "get_balance",
            [new XdrSCSpecFunctionInputV0("Account", "account", XdrSCSpecTypeDef::ADDRESS())],
            [XdrSCSpecTypeDef::I128()]
        );

        $original = XdrSCSpecEntry::forFunctionV0($function);

        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        $decoded = XdrSCSpecEntry::decode(new XdrBuffer($encoded));
        $this->assertEquals(
            XdrSCSpecEntryKind::SC_SPEC_ENTRY_FUNCTION_V0,
            $decoded->getType()->value
        );
        $this->assertNotNull($decoded->getFunctionV0());
        $this->assertEquals("get_balance", $decoded->getFunctionV0()->getName());

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrSCSpecEntry with UDTStructV0
     */
    public function testXdrSCSpecEntryUDTStructV0RoundTrip(): void
    {
        $struct = new XdrSCSpecUDTStructV0(
            "Token info",
            "tokenlib",
            "TokenInfo",
            [
                new XdrSCSpecUDTStructFieldV0("Name", "name", XdrSCSpecTypeDef::STRING()),
                new XdrSCSpecUDTStructFieldV0("Supply", "supply", XdrSCSpecTypeDef::I128()),
            ]
        );

        $original = XdrSCSpecEntry::forUDTStructV0($struct);

        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        $decoded = XdrSCSpecEntry::decode(new XdrBuffer($encoded));
        $this->assertEquals(
            XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_STRUCT_V0,
            $decoded->getType()->value
        );
        $this->assertNotNull($decoded->getUdtStructV0());
        $this->assertEquals("TokenInfo", $decoded->getUdtStructV0()->getName());
        $this->assertCount(2, $decoded->getUdtStructV0()->getFields());

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrSCSpecEntry with UDTUnionV0
     */
    public function testXdrSCSpecEntryUDTUnionV0RoundTrip(): void
    {
        $voidCase = new XdrSCSpecUDTUnionCaseVoidV0("None", "None");
        $case1 = XdrSCSpecUDTUnionCaseV0::forVoidCase($voidCase);

        $union = new XdrSCSpecUDTUnionV0("Option type", "std", "Option", [$case1]);

        $original = XdrSCSpecEntry::forUDTUnionV0($union);

        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        $decoded = XdrSCSpecEntry::decode(new XdrBuffer($encoded));
        $this->assertEquals(
            XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_UNION_V0,
            $decoded->getType()->value
        );
        $this->assertNotNull($decoded->getUdtUnionV0());
        $this->assertEquals("Option", $decoded->getUdtUnionV0()->getName());

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test XdrSCSpecEntry base64 conversion
     */
    public function testXdrSCSpecEntryBase64Conversion(): void
    {
        $function = new XdrSCSpecFunctionV0(
            "Test function",
            "test",
            [],
            [XdrSCSpecTypeDef::VOID()]
        );

        $original = XdrSCSpecEntry::forFunctionV0($function);

        $base64 = $original->toBase64Xdr();
        $this->assertNotEmpty($base64);

        $decoded = XdrSCSpecEntry::fromBase64Xdr($base64);
        $this->assertEquals($original->getType()->value, $decoded->getType()->value);
        $this->assertEquals("test", $decoded->getFunctionV0()->getName());

        $reEncoded = $decoded->toBase64Xdr();
        $this->assertEquals($base64, $reEncoded);
    }

    /**
     * Test nested complex type definition
     */
    public function testComplexNestedTypeDefRoundTrip(): void
    {
        // Create Vec<Option<Map<String, U64>>>
        $mapValueType = XdrSCSpecTypeDef::U64();
        $mapKeyType = XdrSCSpecTypeDef::STRING();
        $map = new XdrSCSpecTypeMap($mapKeyType, $mapValueType);
        $mapTypeDef = XdrSCSpecTypeDef::forMap($map);

        $option = new XdrSCSpecTypeOption($mapTypeDef);
        $optionTypeDef = XdrSCSpecTypeDef::forOption($option);

        $vec = new XdrSCSpecTypeVec($optionTypeDef);
        $original = XdrSCSpecTypeDef::forVec($vec);

        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        $decoded = XdrSCSpecTypeDef::decode(new XdrBuffer($encoded));

        // Verify Vec
        $this->assertEquals(XdrSCSpecType::SC_SPEC_TYPE_VEC, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getVec());

        // Verify Option inside Vec
        $vecElement = $decoded->getVec()->getElementType();
        $this->assertEquals(XdrSCSpecType::SC_SPEC_TYPE_OPTION, $vecElement->getType()->getValue());
        $this->assertNotNull($vecElement->getOption());

        // Verify Map inside Option
        $optionValue = $vecElement->getOption()->getValueType();
        $this->assertEquals(XdrSCSpecType::SC_SPEC_TYPE_MAP, $optionValue->getType()->getValue());
        $this->assertNotNull($optionValue->getMap());

        // Verify Map key and value types
        $this->assertEquals(
            XdrSCSpecType::SC_SPEC_TYPE_STRING,
            $optionValue->getMap()->getKeyType()->getType()->getValue()
        );
        $this->assertEquals(
            XdrSCSpecType::SC_SPEC_TYPE_U64,
            $optionValue->getMap()->getValueType()->getType()->getValue()
        );

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }

    /**
     * Test Result type with complex ok and error types
     */
    public function testComplexResultTypeRoundTrip(): void
    {
        // Result<Vec<U64>, String>
        $okVec = new XdrSCSpecTypeVec(XdrSCSpecTypeDef::U64());
        $okType = XdrSCSpecTypeDef::forVec($okVec);
        $errorType = XdrSCSpecTypeDef::STRING();

        $result = new XdrSCSpecTypeResult($okType, $errorType);
        $original = XdrSCSpecTypeDef::forResult($result);

        $encoded = $original->encode();
        $this->assertNotEmpty($encoded);

        $decoded = XdrSCSpecTypeDef::decode(new XdrBuffer($encoded));

        $this->assertEquals(XdrSCSpecType::SC_SPEC_TYPE_RESULT, $decoded->getType()->getValue());
        $this->assertNotNull($decoded->getResult());

        // Verify ok type is Vec<U64>
        $okTypeDef = $decoded->getResult()->getOkType();
        $this->assertEquals(XdrSCSpecType::SC_SPEC_TYPE_VEC, $okTypeDef->getType()->getValue());
        $this->assertEquals(
            XdrSCSpecType::SC_SPEC_TYPE_U64,
            $okTypeDef->getVec()->getElementType()->getType()->getValue()
        );

        // Verify error type is String
        $this->assertEquals(
            XdrSCSpecType::SC_SPEC_TYPE_STRING,
            $decoded->getResult()->getErrorType()->getType()->getValue()
        );

        $reEncoded = $decoded->encode();
        $this->assertEquals($encoded, $reEncoded);
    }
}
