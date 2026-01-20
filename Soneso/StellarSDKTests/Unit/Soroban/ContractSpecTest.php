<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Soroban;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\Contract\ContractSpec;
use Soneso\StellarSDK\Soroban\Contract\NativeUnionVal;
use Soneso\StellarSDK\Xdr\XdrInt128Parts;
use Soneso\StellarSDK\Xdr\XdrInt256Parts;
use Soneso\StellarSDK\Xdr\XdrSCMapEntry;
use Soneso\StellarSDK\Xdr\XdrSCSpecEntry;
use Soneso\StellarSDK\Xdr\XdrSCSpecEntryKind;
use Soneso\StellarSDK\Xdr\XdrSCSpecEventV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecEventDataFormat;
use Soneso\StellarSDK\Xdr\XdrSCSpecFunctionInputV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecFunctionV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecType;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeDef;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeOption;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeVec;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeTuple;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeMap;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeUDT;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTEnumCaseV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTEnumV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTErrorEnumV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTStructFieldV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTStructV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionCaseV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionCaseV0Kind;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionCaseTupleV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionCaseVoidV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionV0;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValType;
use Soneso\StellarSDK\Xdr\XdrUInt128Parts;
use Soneso\StellarSDK\Xdr\XdrUInt256Parts;

class ContractSpecTest extends TestCase
{
    private const TEST_ACCOUNT_ID = "GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H";
    private const TEST_CONTRACT_ID = "CA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUWDA";

    public function setUp(): void
    {
        error_reporting(E_ALL);
    }

    public function testFuncsReturnsAllFunctions(): void
    {
        $func1 = $this->createFunction('hello', [], new XdrSCSpecTypeDef(XdrSCSpecType::STRING()));
        $func2 = $this->createFunction('world', [], new XdrSCSpecTypeDef(XdrSCSpecType::U32()));
        $struct = $this->createStruct('TestStruct', []);

        $entries = [
            $this->createFunctionEntry($func1),
            $this->createStructEntry($struct),
            $this->createFunctionEntry($func2),
        ];

        $spec = new ContractSpec($entries);
        $funcs = $spec->funcs();

        $this->assertCount(2, $funcs);
        $this->assertEquals('hello', $funcs[0]->name);
        $this->assertEquals('world', $funcs[1]->name);
    }

    public function testGetFuncReturnsCorrectFunction(): void
    {
        $func1 = $this->createFunction('transfer', [], new XdrSCSpecTypeDef(XdrSCSpecType::VOID()));
        $func2 = $this->createFunction('balance', [], new XdrSCSpecTypeDef(XdrSCSpecType::I128()));

        $entries = [
            $this->createFunctionEntry($func1),
            $this->createFunctionEntry($func2),
        ];

        $spec = new ContractSpec($entries);

        $found = $spec->getFunc('balance');
        $this->assertNotNull($found);
        $this->assertEquals('balance', $found->name);

        $notFound = $spec->getFunc('nonexistent');
        $this->assertNull($notFound);
    }

    public function testUdtStructsReturnsAllStructs(): void
    {
        $struct1 = $this->createStruct('Point', []);
        $struct2 = $this->createStruct('Vector', []);
        $func = $this->createFunction('test', [], new XdrSCSpecTypeDef(XdrSCSpecType::VOID()));

        $entries = [
            $this->createStructEntry($struct1),
            $this->createFunctionEntry($func),
            $this->createStructEntry($struct2),
        ];

        $spec = new ContractSpec($entries);
        $structs = $spec->udtStructs();

        $this->assertCount(2, $structs);
        $this->assertEquals('Point', $structs[0]->name);
        $this->assertEquals('Vector', $structs[1]->name);
    }

    public function testUdtUnionsReturnsAllUnions(): void
    {
        $union1 = $this->createUnion('Result', []);
        $union2 = $this->createUnion('Option', []);

        $entries = [
            $this->createUnionEntry($union1),
            $this->createUnionEntry($union2),
        ];

        $spec = new ContractSpec($entries);
        $unions = $spec->udtUnions();

        $this->assertCount(2, $unions);
        $this->assertEquals('Result', $unions[0]->name);
        $this->assertEquals('Option', $unions[1]->name);
    }

    public function testUdtEnumsReturnsAllEnums(): void
    {
        $enum1 = $this->createEnum('Status', [
            new XdrSCSpecUDTEnumCaseV0('', 'Active', 0),
            new XdrSCSpecUDTEnumCaseV0('', 'Inactive', 1),
        ]);
        $enum2 = $this->createEnum('Color', [
            new XdrSCSpecUDTEnumCaseV0('', 'Red', 0),
        ]);

        $entries = [
            $this->createEnumEntry($enum1),
            $this->createEnumEntry($enum2),
        ];

        $spec = new ContractSpec($entries);
        $enums = $spec->udtEnums();

        $this->assertCount(2, $enums);
        $this->assertEquals('Status', $enums[0]->name);
        $this->assertEquals('Color', $enums[1]->name);
    }

    public function testUdtErrorEnumsReturnsAllErrorEnums(): void
    {
        $errorEnum = $this->createErrorEnum('MyError', []);

        $entries = [
            $this->createErrorEnumEntry($errorEnum),
        ];

        $spec = new ContractSpec($entries);
        $errorEnums = $spec->udtErrorEnums();

        $this->assertCount(1, $errorEnums);
        $this->assertEquals('MyError', $errorEnums[0]->name);
    }

    public function testEventsReturnsAllEvents(): void
    {
        $event1 = $this->createEvent('Transfer', []);
        $event2 = $this->createEvent('Approval', []);

        $entries = [
            $this->createEventEntry($event1),
            $this->createEventEntry($event2),
        ];

        $spec = new ContractSpec($entries);
        $events = $spec->events();

        $this->assertCount(2, $events);
        $this->assertEquals('Transfer', $events[0]->name);
        $this->assertEquals('Approval', $events[1]->name);
    }

    public function testFuncArgsToXdrSCValuesConvertsArguments(): void
    {
        $inputs = [
            new XdrSCSpecFunctionInputV0('', 'amount', new XdrSCSpecTypeDef(XdrSCSpecType::U64())),
            new XdrSCSpecFunctionInputV0('', 'recipient', new XdrSCSpecTypeDef(XdrSCSpecType::ADDRESS())),
        ];
        $func = $this->createFunction('transfer', $inputs, new XdrSCSpecTypeDef(XdrSCSpecType::VOID()));

        $entries = [$this->createFunctionEntry($func)];
        $spec = new ContractSpec($entries);

        $args = [
            'amount' => 1000,
            'recipient' => self::TEST_ACCOUNT_ID,
        ];

        $result = $spec->funcArgsToXdrSCValues('transfer', $args);

        $this->assertCount(2, $result);
        $this->assertEquals(XdrSCValType::SCV_U64, $result[0]->type->value);
        $this->assertEquals(1000, $result[0]->u64);
        $this->assertEquals(XdrSCValType::SCV_ADDRESS, $result[1]->type->value);
    }

    public function testFuncArgsToXdrSCValuesThrowsForMissingFunction(): void
    {
        $spec = new ContractSpec([]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Function nonexistent does not exist');

        $spec->funcArgsToXdrSCValues('nonexistent', []);
    }

    public function testFuncArgsToXdrSCValuesThrowsForMissingArgument(): void
    {
        $inputs = [
            new XdrSCSpecFunctionInputV0('', 'amount', new XdrSCSpecTypeDef(XdrSCSpecType::U64())),
        ];
        $func = $this->createFunction('test', $inputs, new XdrSCSpecTypeDef(XdrSCSpecType::VOID()));

        $entries = [$this->createFunctionEntry($func)];
        $spec = new ContractSpec($entries);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Arg not found for function input: amount');

        $spec->funcArgsToXdrSCValues('test', []);
    }

    public function testFindEntryFindsFunction(): void
    {
        $func = $this->createFunction('hello', [], new XdrSCSpecTypeDef(XdrSCSpecType::VOID()));
        $entries = [$this->createFunctionEntry($func)];
        $spec = new ContractSpec($entries);

        $found = $spec->findEntry('hello');
        $this->assertNotNull($found);
        $this->assertEquals(XdrSCSpecEntryKind::SC_SPEC_ENTRY_FUNCTION_V0, $found->type->value);
    }

    public function testFindEntryFindsStruct(): void
    {
        $struct = $this->createStruct('Point', []);
        $entries = [$this->createStructEntry($struct)];
        $spec = new ContractSpec($entries);

        $found = $spec->findEntry('Point');
        $this->assertNotNull($found);
        $this->assertEquals(XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_STRUCT_V0, $found->type->value);
    }

    public function testFindEntryReturnsNullForMissing(): void
    {
        $spec = new ContractSpec([]);
        $found = $spec->findEntry('nonexistent');
        $this->assertNull($found);
    }

    public function testNativeToXdrSCValConvertsBool(): void
    {
        $spec = new ContractSpec([]);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::BOOL());

        $trueVal = $spec->nativeToXdrSCVal(true, $typeDef);
        $this->assertEquals(XdrSCValType::SCV_BOOL, $trueVal->type->value);
        $this->assertTrue($trueVal->b);

        $falseVal = $spec->nativeToXdrSCVal(false, $typeDef);
        $this->assertEquals(XdrSCValType::SCV_BOOL, $falseVal->type->value);
        $this->assertFalse($falseVal->b);
    }

    public function testNativeToXdrSCValConvertsVoid(): void
    {
        $spec = new ContractSpec([]);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::VOID());

        $result = $spec->nativeToXdrSCVal(null, $typeDef);
        $this->assertEquals(XdrSCValType::SCV_VOID, $result->type->value);
    }

    public function testNativeToXdrSCValConvertsU32(): void
    {
        $spec = new ContractSpec([]);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::U32());

        $result = $spec->nativeToXdrSCVal(42, $typeDef);
        $this->assertEquals(XdrSCValType::SCV_U32, $result->type->value);
        $this->assertEquals(42, $result->u32);
    }

    public function testNativeToXdrSCValConvertsI32(): void
    {
        $spec = new ContractSpec([]);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::I32());

        $result = $spec->nativeToXdrSCVal(-42, $typeDef);
        $this->assertEquals(XdrSCValType::SCV_I32, $result->type->value);
        $this->assertEquals(-42, $result->i32);
    }

    public function testNativeToXdrSCValConvertsU64(): void
    {
        $spec = new ContractSpec([]);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::U64());

        $result = $spec->nativeToXdrSCVal(1000, $typeDef);
        $this->assertEquals(XdrSCValType::SCV_U64, $result->type->value);
        $this->assertEquals(1000, $result->u64);
    }

    public function testNativeToXdrSCValConvertsI64(): void
    {
        $spec = new ContractSpec([]);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::I64());

        $result = $spec->nativeToXdrSCVal(-1000, $typeDef);
        $this->assertEquals(XdrSCValType::SCV_I64, $result->type->value);
        $this->assertEquals(-1000, $result->i64);
    }

    public function testNativeToXdrSCValConvertsU128FromInt(): void
    {
        $spec = new ContractSpec([]);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::U128());

        $result = $spec->nativeToXdrSCVal(1000, $typeDef);
        $this->assertEquals(XdrSCValType::SCV_U128, $result->type->value);
        $this->assertNotNull($result->u128);
    }

    public function testNativeToXdrSCValConvertsI128FromInt(): void
    {
        $spec = new ContractSpec([]);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::I128());

        $result = $spec->nativeToXdrSCVal(1000, $typeDef);
        $this->assertEquals(XdrSCValType::SCV_I128, $result->type->value);
        $this->assertNotNull($result->i128);
    }

    public function testNativeToXdrSCValConvertsU256FromInt(): void
    {
        $spec = new ContractSpec([]);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::U256());

        $result = $spec->nativeToXdrSCVal(1000, $typeDef);
        $this->assertEquals(XdrSCValType::SCV_U256, $result->type->value);
        $this->assertNotNull($result->u256);
    }

    public function testNativeToXdrSCValConvertsI256FromInt(): void
    {
        $spec = new ContractSpec([]);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::I256());

        $result = $spec->nativeToXdrSCVal(1000, $typeDef);
        $this->assertEquals(XdrSCValType::SCV_I256, $result->type->value);
        $this->assertNotNull($result->i256);
    }

    public function testNativeToXdrSCValConvertsU128FromGMP(): void
    {
        $spec = new ContractSpec([]);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::U128());

        $gmp = gmp_init('1000000000000000000');
        $result = $spec->nativeToXdrSCVal($gmp, $typeDef);
        $this->assertEquals(XdrSCValType::SCV_U128, $result->type->value);
        $this->assertNotNull($result->u128);
    }

    public function testNativeToXdrSCValConvertsI128FromGMP(): void
    {
        $spec = new ContractSpec([]);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::I128());

        $gmp = gmp_init('-1000000000000000000');
        $result = $spec->nativeToXdrSCVal($gmp, $typeDef);
        $this->assertEquals(XdrSCValType::SCV_I128, $result->type->value);
        $this->assertNotNull($result->i128);
    }

    public function testNativeToXdrSCValConvertsU128FromString(): void
    {
        $spec = new ContractSpec([]);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::U128());

        $result = $spec->nativeToXdrSCVal('1000000000000000000', $typeDef);
        $this->assertEquals(XdrSCValType::SCV_U128, $result->type->value);
        $this->assertNotNull($result->u128);
    }

    public function testNativeToXdrSCValConvertsI128FromString(): void
    {
        $spec = new ContractSpec([]);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::I128());

        $result = $spec->nativeToXdrSCVal('-1000000000000000000', $typeDef);
        $this->assertEquals(XdrSCValType::SCV_I128, $result->type->value);
        $this->assertNotNull($result->i128);
    }

    public function testNativeToXdrSCValConvertsU128FromXdrUInt128Parts(): void
    {
        $spec = new ContractSpec([]);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::U128());

        $parts = new XdrUInt128Parts(0, 1000);
        $result = $spec->nativeToXdrSCVal($parts, $typeDef);
        $this->assertEquals(XdrSCValType::SCV_U128, $result->type->value);
        $this->assertNotNull($result->u128);
    }

    public function testNativeToXdrSCValConvertsI128FromXdrInt128Parts(): void
    {
        $spec = new ContractSpec([]);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::I128());

        $parts = new XdrInt128Parts(0, 1000);
        $result = $spec->nativeToXdrSCVal($parts, $typeDef);
        $this->assertEquals(XdrSCValType::SCV_I128, $result->type->value);
        $this->assertNotNull($result->i128);
    }

    public function testNativeToXdrSCValConvertsU256FromXdrUInt256Parts(): void
    {
        $spec = new ContractSpec([]);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::U256());

        $parts = new XdrUInt256Parts(0, 0, 0, 1000);
        $result = $spec->nativeToXdrSCVal($parts, $typeDef);
        $this->assertEquals(XdrSCValType::SCV_U256, $result->type->value);
        $this->assertNotNull($result->u256);
    }

    public function testNativeToXdrSCValConvertsI256FromXdrInt256Parts(): void
    {
        $spec = new ContractSpec([]);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::I256());

        $parts = new XdrInt256Parts(0, 0, 0, 1000);
        $result = $spec->nativeToXdrSCVal($parts, $typeDef);
        $this->assertEquals(XdrSCValType::SCV_I256, $result->type->value);
        $this->assertNotNull($result->i256);
    }

    public function testNativeToXdrSCValConvertsString(): void
    {
        $spec = new ContractSpec([]);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::STRING());

        $result = $spec->nativeToXdrSCVal('hello', $typeDef);
        $this->assertEquals(XdrSCValType::SCV_STRING, $result->type->value);
        $this->assertEquals('hello', $result->str);
    }

    public function testNativeToXdrSCValConvertsSymbol(): void
    {
        $spec = new ContractSpec([]);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::SYMBOL());

        $result = $spec->nativeToXdrSCVal('symbol', $typeDef);
        $this->assertEquals(XdrSCValType::SCV_SYMBOL, $result->type->value);
        $this->assertEquals('symbol', $result->sym);
    }

    public function testNativeToXdrSCValConvertsBytes(): void
    {
        $spec = new ContractSpec([]);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::BYTES());

        $result = $spec->nativeToXdrSCVal('data', $typeDef);
        $this->assertEquals(XdrSCValType::SCV_BYTES, $result->type->value);
        $this->assertEquals('data', $result->bytes->value);
    }

    public function testNativeToXdrSCValConvertsAddressFromObject(): void
    {
        $spec = new ContractSpec([]);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::ADDRESS());

        $address = Address::fromAccountId(self::TEST_ACCOUNT_ID);
        $result = $spec->nativeToXdrSCVal($address, $typeDef);
        $this->assertEquals(XdrSCValType::SCV_ADDRESS, $result->type->value);
    }

    public function testNativeToXdrSCValConvertsAddressFromAccountString(): void
    {
        $spec = new ContractSpec([]);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::ADDRESS());

        $result = $spec->nativeToXdrSCVal(self::TEST_ACCOUNT_ID, $typeDef);
        $this->assertEquals(XdrSCValType::SCV_ADDRESS, $result->type->value);
    }

    public function testNativeToXdrSCValConvertsAddressFromContractString(): void
    {
        $spec = new ContractSpec([]);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::ADDRESS());

        $result = $spec->nativeToXdrSCVal(self::TEST_CONTRACT_ID, $typeDef);
        $this->assertEquals(XdrSCValType::SCV_ADDRESS, $result->type->value);
    }

    public function testNativeToXdrSCValConvertsVec(): void
    {
        $spec = new ContractSpec([]);
        $elementType = new XdrSCSpecTypeDef(XdrSCSpecType::U32());
        $vecType = new XdrSCSpecTypeVec($elementType);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::VEC());
        $typeDef->vec = $vecType;

        $result = $spec->nativeToXdrSCVal([1, 2, 3], $typeDef);
        $this->assertEquals(XdrSCValType::SCV_VEC, $result->type->value);
        $this->assertCount(3, $result->vec);
        $this->assertEquals(1, $result->vec[0]->u32);
        $this->assertEquals(2, $result->vec[1]->u32);
        $this->assertEquals(3, $result->vec[2]->u32);
    }

    public function testNativeToXdrSCValConvertsTuple(): void
    {
        $spec = new ContractSpec([]);
        $valueTypes = [
            new XdrSCSpecTypeDef(XdrSCSpecType::U32()),
            new XdrSCSpecTypeDef(XdrSCSpecType::STRING()),
        ];
        $tupleType = new XdrSCSpecTypeTuple($valueTypes);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::TUPLE());
        $typeDef->tuple = $tupleType;

        $result = $spec->nativeToXdrSCVal([42, 'hello'], $typeDef);
        $this->assertEquals(XdrSCValType::SCV_VEC, $result->type->value);
        $this->assertCount(2, $result->vec);
        $this->assertEquals(42, $result->vec[0]->u32);
        $this->assertEquals('hello', $result->vec[1]->str);
    }

    public function testNativeToXdrSCValConvertsMap(): void
    {
        $spec = new ContractSpec([]);
        $keyType = new XdrSCSpecTypeDef(XdrSCSpecType::STRING());
        $valueType = new XdrSCSpecTypeDef(XdrSCSpecType::U32());
        $mapType = new XdrSCSpecTypeMap($keyType, $valueType);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::MAP());
        $typeDef->map = $mapType;

        $result = $spec->nativeToXdrSCVal(['key1' => 1, 'key2' => 2], $typeDef);
        $this->assertEquals(XdrSCValType::SCV_MAP, $result->type->value);
        $this->assertCount(2, $result->map);
    }

    public function testNativeToXdrSCValConvertsOption(): void
    {
        $spec = new ContractSpec([]);
        $valueType = new XdrSCSpecTypeDef(XdrSCSpecType::U32());
        $optionType = new XdrSCSpecTypeOption($valueType);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::OPTION());
        $typeDef->option = $optionType;

        $resultSome = $spec->nativeToXdrSCVal(42, $typeDef);
        $this->assertEquals(XdrSCValType::SCV_U32, $resultSome->type->value);
        $this->assertEquals(42, $resultSome->u32);

        $resultNone = $spec->nativeToXdrSCVal(null, $typeDef);
        $this->assertEquals(XdrSCValType::SCV_VOID, $resultNone->type->value);
    }

    public function testNativeToXdrSCValConvertsEnum(): void
    {
        $enum = $this->createEnum('Status', [
            new XdrSCSpecUDTEnumCaseV0('', 'Active', 0),
            new XdrSCSpecUDTEnumCaseV0('', 'Inactive', 1),
        ]);
        $entries = [$this->createEnumEntry($enum)];
        $spec = new ContractSpec($entries);

        $udtType = new XdrSCSpecTypeUDT('Status');
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::UDT());
        $typeDef->udt = $udtType;

        $result = $spec->nativeToXdrSCVal(1, $typeDef);
        $this->assertEquals(XdrSCValType::SCV_U32, $result->type->value);
        $this->assertEquals(1, $result->u32);
    }

    public function testNativeToXdrSCValConvertsStructWithNamedFields(): void
    {
        $fields = [
            new XdrSCSpecUDTStructFieldV0('', 'x', new XdrSCSpecTypeDef(XdrSCSpecType::U32())),
            new XdrSCSpecUDTStructFieldV0('', 'y', new XdrSCSpecTypeDef(XdrSCSpecType::U32())),
        ];
        $struct = $this->createStruct('Point', $fields);
        $entries = [$this->createStructEntry($struct)];
        $spec = new ContractSpec($entries);

        $udtType = new XdrSCSpecTypeUDT('Point');
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::UDT());
        $typeDef->udt = $udtType;

        $result = $spec->nativeToXdrSCVal(['x' => 10, 'y' => 20], $typeDef);
        $this->assertEquals(XdrSCValType::SCV_MAP, $result->type->value);
        $this->assertCount(2, $result->map);
    }

    public function testNativeToXdrSCValConvertsStructWithNumericFields(): void
    {
        $fields = [
            new XdrSCSpecUDTStructFieldV0('', '0', new XdrSCSpecTypeDef(XdrSCSpecType::U32())),
            new XdrSCSpecUDTStructFieldV0('', '1', new XdrSCSpecTypeDef(XdrSCSpecType::U32())),
        ];
        $struct = $this->createStruct('Tuple', $fields);
        $entries = [$this->createStructEntry($struct)];
        $spec = new ContractSpec($entries);

        $udtType = new XdrSCSpecTypeUDT('Tuple');
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::UDT());
        $typeDef->udt = $udtType;

        $result = $spec->nativeToXdrSCVal([10, 20], $typeDef);
        $this->assertEquals(XdrSCValType::SCV_VEC, $result->type->value);
        $this->assertCount(2, $result->vec);
    }

    public function testNativeToXdrSCValConvertsUnionVoidCase(): void
    {
        $cases = [
            $this->createUnionVoidCase('None'),
        ];
        $union = $this->createUnion('Option', $cases);
        $entries = [$this->createUnionEntry($union)];
        $spec = new ContractSpec($entries);

        $udtType = new XdrSCSpecTypeUDT('Option');
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::UDT());
        $typeDef->udt = $udtType;

        $nativeUnion = new NativeUnionVal('None');
        $result = $spec->nativeToXdrSCVal($nativeUnion, $typeDef);
        $this->assertEquals(XdrSCValType::SCV_VEC, $result->type->value);
        $this->assertCount(1, $result->vec);
        $this->assertEquals('None', $result->vec[0]->sym);
    }

    public function testNativeToXdrSCValConvertsUnionTupleCase(): void
    {
        $cases = [
            $this->createUnionTupleCase('Some', [new XdrSCSpecTypeDef(XdrSCSpecType::U32())]),
        ];
        $union = $this->createUnion('Option', $cases);
        $entries = [$this->createUnionEntry($union)];
        $spec = new ContractSpec($entries);

        $udtType = new XdrSCSpecTypeUDT('Option');
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::UDT());
        $typeDef->udt = $udtType;

        $nativeUnion = new NativeUnionVal('Some', [42]);
        $result = $spec->nativeToXdrSCVal($nativeUnion, $typeDef);
        $this->assertEquals(XdrSCValType::SCV_VEC, $result->type->value);
        $this->assertCount(2, $result->vec);
        $this->assertEquals('Some', $result->vec[0]->sym);
        $this->assertEquals(42, $result->vec[1]->u32);
    }

    public function testNativeToXdrSCValThrowsForNegativeU32(): void
    {
        $spec = new ContractSpec([]);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::U32());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Negative integer value provided for u32');

        $spec->nativeToXdrSCVal(-1, $typeDef);
    }

    public function testNativeToXdrSCValThrowsForNegativeU64(): void
    {
        $spec = new ContractSpec([]);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::U64());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Negative integer value provided for u64');

        $spec->nativeToXdrSCVal(-1, $typeDef);
    }

    public function testNativeToXdrSCValThrowsForInvalidBoolType(): void
    {
        $spec = new ContractSpec([]);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::U32());

        $this->expectException(InvalidArgumentException::class);

        $spec->nativeToXdrSCVal(true, $typeDef);
    }

    public function testNativeToXdrSCValThrowsForInvalidStringType(): void
    {
        $spec = new ContractSpec([]);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::U32());

        $this->expectException(InvalidArgumentException::class);

        $spec->nativeToXdrSCVal('hello', $typeDef);
    }

    public function testNativeToXdrSCValThrowsForInvalidArrayType(): void
    {
        $spec = new ContractSpec([]);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::U32());

        $this->expectException(InvalidArgumentException::class);

        $spec->nativeToXdrSCVal([1, 2, 3], $typeDef);
    }

    public function testNativeToXdrSCValThrowsForTupleSizeMismatch(): void
    {
        $spec = new ContractSpec([]);
        $valueTypes = [
            new XdrSCSpecTypeDef(XdrSCSpecType::U32()),
            new XdrSCSpecTypeDef(XdrSCSpecType::STRING()),
        ];
        $tupleType = new XdrSCSpecTypeTuple($valueTypes);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::TUPLE());
        $typeDef->tuple = $tupleType;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tuple expects 2 values, but 1 were provided');

        $spec->nativeToXdrSCVal([42], $typeDef);
    }

    public function testNativeToXdrSCValThrowsForMissingUdt(): void
    {
        $spec = new ContractSpec([]);
        $udtType = new XdrSCSpecTypeUDT('NonExistent');
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::UDT());
        $typeDef->udt = $udtType;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('entry not found for NonExistent');

        $spec->nativeToXdrSCVal(42, $typeDef);
    }

    public function testNativeToXdrSCValThrowsForInvalidEnumValue(): void
    {
        $enum = $this->createEnum('Status', [
            new XdrSCSpecUDTEnumCaseV0('', 'Active', 0),
        ]);
        $entries = [$this->createEnumEntry($enum)];
        $spec = new ContractSpec($entries);

        $udtType = new XdrSCSpecTypeUDT('Status');
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::UDT());
        $typeDef->udt = $udtType;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no such enum entry: 99 in Status');

        $spec->nativeToXdrSCVal(99, $typeDef);
    }

    public function testNativeToXdrSCValThrowsForInvalidUnionCase(): void
    {
        $cases = [
            $this->createUnionVoidCase('None'),
        ];
        $union = $this->createUnion('Option', $cases);
        $entries = [$this->createUnionEntry($union)];
        $spec = new ContractSpec($entries);

        $udtType = new XdrSCSpecTypeUDT('Option');
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::UDT());
        $typeDef->udt = $udtType;

        $nativeUnion = new NativeUnionVal('Invalid');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no such enum entry: Invalid in Option');

        $spec->nativeToXdrSCVal($nativeUnion, $typeDef);
    }

    public function testNativeToXdrSCValThrowsForNonNativeUnionValType(): void
    {
        $cases = [
            $this->createUnionVoidCase('None'),
        ];
        $union = $this->createUnion('Option', $cases);
        $entries = [$this->createUnionEntry($union)];
        $spec = new ContractSpec($entries);

        $udtType = new XdrSCSpecTypeUDT('Option');
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::UDT());
        $typeDef->udt = $udtType;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('val must be of type NativeUnionVal');

        $spec->nativeToXdrSCVal('invalid', $typeDef);
    }

    public function testNativeToXdrSCValAcceptsXdrSCValDirectly(): void
    {
        $spec = new ContractSpec([]);
        $typeDef = new XdrSCSpecTypeDef(XdrSCSpecType::U32());

        $xdrVal = XdrSCVal::forU32(42);
        $result = $spec->nativeToXdrSCVal($xdrVal, $typeDef);

        $this->assertSame($xdrVal, $result);
    }

    private function createFunction(string $name, array $inputs, XdrSCSpecTypeDef $output): XdrSCSpecFunctionV0
    {
        return new XdrSCSpecFunctionV0('', $name, $inputs, [$output]);
    }

    private function createStruct(string $name, array $fields): XdrSCSpecUDTStructV0
    {
        return new XdrSCSpecUDTStructV0('', '', $name, $fields);
    }

    private function createUnion(string $name, array $cases): XdrSCSpecUDTUnionV0
    {
        return new XdrSCSpecUDTUnionV0('', '', $name, $cases);
    }

    private function createEnum(string $name, array $cases): XdrSCSpecUDTEnumV0
    {
        return new XdrSCSpecUDTEnumV0('', '', $name, $cases);
    }

    private function createErrorEnum(string $name, array $cases): XdrSCSpecUDTErrorEnumV0
    {
        return new XdrSCSpecUDTErrorEnumV0('', '', $name, $cases);
    }

    private function createEvent(string $name, array $params): XdrSCSpecEventV0
    {
        return new XdrSCSpecEventV0('', '', $name, [], $params, new XdrSCSpecEventDataFormat(0));
    }

    private function createFunctionEntry(XdrSCSpecFunctionV0 $func): XdrSCSpecEntry
    {
        $entry = new XdrSCSpecEntry(new XdrSCSpecEntryKind(XdrSCSpecEntryKind::SC_SPEC_ENTRY_FUNCTION_V0));
        $entry->functionV0 = $func;
        return $entry;
    }

    private function createStructEntry(XdrSCSpecUDTStructV0 $struct): XdrSCSpecEntry
    {
        $entry = new XdrSCSpecEntry(new XdrSCSpecEntryKind(XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_STRUCT_V0));
        $entry->udtStructV0 = $struct;
        return $entry;
    }

    private function createUnionEntry(XdrSCSpecUDTUnionV0 $union): XdrSCSpecEntry
    {
        $entry = new XdrSCSpecEntry(new XdrSCSpecEntryKind(XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_UNION_V0));
        $entry->udtUnionV0 = $union;
        return $entry;
    }

    private function createEnumEntry(XdrSCSpecUDTEnumV0 $enum): XdrSCSpecEntry
    {
        $entry = new XdrSCSpecEntry(new XdrSCSpecEntryKind(XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_ENUM_V0));
        $entry->udtEnumV0 = $enum;
        return $entry;
    }

    private function createErrorEnumEntry(XdrSCSpecUDTErrorEnumV0 $errorEnum): XdrSCSpecEntry
    {
        $entry = new XdrSCSpecEntry(new XdrSCSpecEntryKind(XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_ERROR_ENUM_V0));
        $entry->udtErrorEnumV0 = $errorEnum;
        return $entry;
    }

    private function createEventEntry(XdrSCSpecEventV0 $event): XdrSCSpecEntry
    {
        $entry = new XdrSCSpecEntry(new XdrSCSpecEntryKind(XdrSCSpecEntryKind::SC_SPEC_ENTRY_EVENT_V0));
        $entry->eventV0 = $event;
        return $entry;
    }

    private function createUnionVoidCase(string $name): XdrSCSpecUDTUnionCaseV0
    {
        $voidCase = new XdrSCSpecUDTUnionCaseVoidV0('', $name);
        $case = new XdrSCSpecUDTUnionCaseV0(new XdrSCSpecUDTUnionCaseV0Kind(XdrSCSpecUDTUnionCaseV0Kind::SC_SPEC_UDT_UNION_CASE_VOID_V0));
        $case->voidCase = $voidCase;
        return $case;
    }

    private function createUnionTupleCase(string $name, array $types): XdrSCSpecUDTUnionCaseV0
    {
        $tupleCase = new XdrSCSpecUDTUnionCaseTupleV0('', $name, $types);
        $case = new XdrSCSpecUDTUnionCaseV0(new XdrSCSpecUDTUnionCaseV0Kind(XdrSCSpecUDTUnionCaseV0Kind::SC_SPEC_UDT_UNION_CASE_TUPLE_V0));
        $case->tupleCase = $tupleCase;
        return $case;
    }
}
