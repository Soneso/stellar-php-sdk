<?php  declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Soroban\Contract\ContractSpec;
use Soneso\StellarSDK\Soroban\SorobanContractParser;
use Soneso\StellarSDK\Xdr\XdrSCSpecEntryKind;
use Soneso\StellarSDK\Xdr\XdrSCSpecFunctionV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecType;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeDef;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTEnumV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTErrorEnumV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTStructV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionCaseV0Kind;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecEventV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecEventParamLocationV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecEventDataFormat;

class SorobanParserTest extends TestCase
{

    const CONTRACT_PATH = __DIR__ . '/wasm/soroban_token_contract.wasm';

    public function setUp(): void
    {
        // Turn on error reporting
        error_reporting(E_ALL);
    }

    public function testTokenContractParsing(): void
    {
        $contractCode = file_get_contents(self::CONTRACT_PATH, false);
        $contractInfo = SorobanContractParser::parseContractByteCode($contractCode);
        $this->assertCount(25, $contractInfo->specEntries);
        $this->assertCount(2, $contractInfo->metaEntries);

        print("--------------------------------" . PHP_EOL);
        print("Env Meta:" . PHP_EOL);
        print(PHP_EOL);
        print("Interface version: {$contractInfo->envInterfaceVersion}". PHP_EOL);
        print("--------------------------------" . PHP_EOL);
        print("Contract Meta:" . PHP_EOL);
        print(PHP_EOL);
        foreach ($contractInfo->metaEntries as  $key => $value) {
            print("{$key}: {$value}" . PHP_EOL);
        }
        print("--------------------------------" . PHP_EOL);

        print("Contract Spec:" . PHP_EOL);
        print(PHP_EOL);
        $index = 0;
        foreach ($contractInfo->specEntries as $entry) {
            switch ($entry->type->value) {
                case XdrSCSpecEntryKind::SC_SPEC_ENTRY_FUNCTION_V0:
                    $this->printFunction($entry->functionV0);
                    break;
                case XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_STRUCT_V0:
                    $this->printUdtStruct($entry->udtStructV0);
                    break;
                case XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_UNION_V0:
                    $this->printUdtUnion($entry->udtUnionV0);
                    break;
                case XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_ENUM_V0:
                    $this->printUdtEnum($entry->udtEnumV0);
                    break;
                case XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_ERROR_ENUM_V0:
                    $this->printUdtErrorEnum($entry->udtErrorEnumV0);
                    break;
                case XdrSCSpecEntryKind::SC_SPEC_ENTRY_EVENT_V0:
                    $this->printEvent($entry->eventV0);
                    break;
                default:
                    print('specEntry [' . $index . '] -> kind(' . $entry->type->value .'): ' . 'unknown ' . PHP_EOL);
                    break;
            }
            print(PHP_EOL);
            $index++;
        }
        print("--------------------------------");
    }

    /**
     * Test validation of token contract using new SorobanContractInfo properties.
     * This test validates the contract structure by checking counts and specific entries
     * of functions, UDT types, and events using the new categorized properties.
     */
    public function testTokenContractValidation(): void
    {
        // Load and parse the token contract
        $contractCode = file_get_contents(self::CONTRACT_PATH, false);
        $contractInfo = SorobanContractParser::parseContractByteCode($contractCode);

        // Validate environment interface version
        $this->assertGreaterThan(0, $contractInfo->envInterfaceVersion,
            'Environment interface version should be greater than 0');

        // Validate meta entries
        $this->assertCount(2, $contractInfo->metaEntries,
            'Contract should have exactly 2 meta entries');
        $this->assertArrayHasKey('rsver', $contractInfo->metaEntries,
            'Meta entries should contain rsver key');
        $this->assertArrayHasKey('rssdkver', $contractInfo->metaEntries,
            'Meta entries should contain rssdkver key');

        // Validate total spec entries count
        $this->assertCount(25, $contractInfo->specEntries,
            'Contract should have exactly 25 spec entries');

        // Validate functions count and specific function names
        $this->assertCount(13, $contractInfo->funcs,
            'Contract should have exactly 13 functions');

        $functionNames = array_map(fn($func) => $func->name, $contractInfo->funcs);

        // Validate critical token functions exist
        $this->assertContains('__constructor', $functionNames,
            'Contract should have __constructor function');
        $this->assertContains('mint', $functionNames,
            'Contract should have mint function');
        $this->assertContains('burn', $functionNames,
            'Contract should have burn function');
        $this->assertContains('transfer', $functionNames,
            'Contract should have transfer function');
        $this->assertContains('transfer_from', $functionNames,
            'Contract should have transfer_from function');
        $this->assertContains('balance', $functionNames,
            'Contract should have balance function');
        $this->assertContains('approve', $functionNames,
            'Contract should have approve function');
        $this->assertContains('allowance', $functionNames,
            'Contract should have allowance function');
        $this->assertContains('decimals', $functionNames,
            'Contract should have decimals function');
        $this->assertContains('name', $functionNames,
            'Contract should have name function');
        $this->assertContains('symbol', $functionNames,
            'Contract should have symbol function');
        $this->assertContains('set_admin', $functionNames,
            'Contract should have set_admin function');
        $this->assertContains('burn_from', $functionNames,
            'Contract should have burn_from function');

        // Validate UDT structs count and specific struct names
        $this->assertCount(3, $contractInfo->udtStructs,
            'Contract should have exactly 3 UDT structs');

        $structNames = array_map(fn($struct) => $struct->name, $contractInfo->udtStructs);
        $this->assertContains('AllowanceDataKey', $structNames,
            'Contract should have AllowanceDataKey struct');
        $this->assertContains('AllowanceValue', $structNames,
            'Contract should have AllowanceValue struct');
        $this->assertContains('TokenMetadata', $structNames,
            'Contract should have TokenMetadata struct');

        // Validate AllowanceDataKey struct fields
        $allowanceDataKey = null;
        foreach ($contractInfo->udtStructs as $struct) {
            if ($struct->name === 'AllowanceDataKey') {
                $allowanceDataKey = $struct;
                break;
            }
        }
        $this->assertNotNull($allowanceDataKey, 'AllowanceDataKey struct should be found');
        $this->assertCount(2, $allowanceDataKey->fields,
            'AllowanceDataKey should have 2 fields');
        $this->assertEquals('from', $allowanceDataKey->fields[0]->name,
            'First field of AllowanceDataKey should be named "from"');
        $this->assertEquals('spender', $allowanceDataKey->fields[1]->name,
            'Second field of AllowanceDataKey should be named "spender"');

        // Validate TokenMetadata struct fields
        $tokenMetadata = null;
        foreach ($contractInfo->udtStructs as $struct) {
            if ($struct->name === 'TokenMetadata') {
                $tokenMetadata = $struct;
                break;
            }
        }
        $this->assertNotNull($tokenMetadata, 'TokenMetadata struct should be found');
        $this->assertCount(3, $tokenMetadata->fields,
            'TokenMetadata should have 3 fields');
        $this->assertEquals('decimal', $tokenMetadata->fields[0]->name,
            'First field of TokenMetadata should be named "decimal"');
        $this->assertEquals('name', $tokenMetadata->fields[1]->name,
            'Second field of TokenMetadata should be named "name"');
        $this->assertEquals('symbol', $tokenMetadata->fields[2]->name,
            'Third field of TokenMetadata should be named "symbol"');

        // Validate UDT unions count and specific union names
        $this->assertCount(1, $contractInfo->udtUnions,
            'Contract should have exactly 1 UDT union');

        $unionNames = array_map(fn($union) => $union->name, $contractInfo->udtUnions);
        $this->assertContains('DataKey', $unionNames,
            'Contract should have DataKey union');

        // Validate DataKey union cases
        $dataKey = $contractInfo->udtUnions[0];
        $this->assertEquals('DataKey', $dataKey->name,
            'Union should be named DataKey');
        $this->assertCount(4, $dataKey->cases,
            'DataKey union should have 4 cases');

        // Validate UDT enums count (should be zero for this contract)
        $this->assertCount(0, $contractInfo->udtEnums,
            'Contract should have 0 UDT enums');

        // Validate UDT error enums count (should be zero for this contract)
        $this->assertCount(0, $contractInfo->udtErrorEnums,
            'Contract should have 0 UDT error enums');

        // Validate events count and specific event names
        $this->assertCount(8, $contractInfo->events,
            'Contract should have exactly 8 events');

        $eventNames = array_map(fn($event) => $event->name, $contractInfo->events);
        $this->assertContains('SetAdmin', $eventNames,
            'Contract should have SetAdmin event');
        $this->assertContains('Approve', $eventNames,
            'Contract should have Approve event');
        $this->assertContains('Transfer', $eventNames,
            'Contract should have Transfer event');
        $this->assertContains('TransferWithAmountOnly', $eventNames,
            'Contract should have TransferWithAmountOnly event');
        $this->assertContains('Burn', $eventNames,
            'Contract should have Burn event');
        $this->assertContains('Mint', $eventNames,
            'Contract should have Mint event');
        $this->assertContains('MintWithAmountOnly', $eventNames,
            'Contract should have MintWithAmountOnly event');
        $this->assertContains('Clawback', $eventNames,
            'Contract should have Clawback event');

        // Validate Transfer event structure
        $transferEvent = null;
        foreach ($contractInfo->events as $event) {
            if ($event->name === 'Transfer') {
                $transferEvent = $event;
                break;
            }
        }
        $this->assertNotNull($transferEvent, 'Transfer event should be found');
        $this->assertCount(1, $transferEvent->prefixTopics,
            'Transfer event should have 1 prefix topic');
        $this->assertEquals('transfer', $transferEvent->prefixTopics[0],
            'Transfer event prefix topic should be "transfer"');
        $this->assertCount(4, $transferEvent->params,
            'Transfer event should have 4 parameters');

        // Validate Approve event structure
        $approveEvent = null;
        foreach ($contractInfo->events as $event) {
            if ($event->name === 'Approve') {
                $approveEvent = $event;
                break;
            }
        }
        $this->assertNotNull($approveEvent, 'Approve event should be found');
        $this->assertCount(1, $approveEvent->prefixTopics,
            'Approve event should have 1 prefix topic');
        $this->assertEquals('approve', $approveEvent->prefixTopics[0],
            'Approve event prefix topic should be "approve"');
        $this->assertCount(4, $approveEvent->params,
            'Approve event should have 4 parameters');

        // Validate balance function signature
        $balanceFunc = null;
        foreach ($contractInfo->funcs as $func) {
            if ($func->name === 'balance') {
                $balanceFunc = $func;
                break;
            }
        }
        $this->assertNotNull($balanceFunc, 'balance function should be found');
        $this->assertCount(1, $balanceFunc->inputs,
            'balance function should have 1 input parameter');
        $this->assertEquals('id', $balanceFunc->inputs[0]->name,
            'balance function input should be named "id"');
        $this->assertCount(1, $balanceFunc->outputs,
            'balance function should have 1 output');

        // Validate mint function signature
        $mintFunc = null;
        foreach ($contractInfo->funcs as $func) {
            if ($func->name === 'mint') {
                $mintFunc = $func;
                break;
            }
        }
        $this->assertNotNull($mintFunc, 'mint function should be found');
        $this->assertCount(2, $mintFunc->inputs,
            'mint function should have 2 input parameters');
        $this->assertEquals('to', $mintFunc->inputs[0]->name,
            'First parameter of mint function should be named "to"');
        $this->assertEquals('amount', $mintFunc->inputs[1]->name,
            'Second parameter of mint function should be named "amount"');
        $this->assertCount(0, $mintFunc->outputs,
            'mint function should have no outputs (void return)');
    }

    /**
     * Test ContractSpec methods for extracting different types of spec entries.
     * This test validates that ContractSpec correctly categorizes and returns functions,
     * UDT structs, unions, enums, error enums, and events from the token contract.
     */
    public function testContractSpecMethods(): void
    {
        // Load and parse the token contract
        $contractCode = file_get_contents(self::CONTRACT_PATH, false);
        $contractInfo = SorobanContractParser::parseContractByteCode($contractCode);

        // Create a ContractSpec instance from the parsed spec entries
        $contractSpec = new ContractSpec($contractInfo->specEntries);

        // Test funcs() method - should return 13 functions
        $functions = $contractSpec->funcs();
        $this->assertCount(13, $functions,
            'ContractSpec funcs() should return exactly 13 functions');

        // Validate that all returned items are XdrSCSpecFunctionV0 instances
        foreach ($functions as $func) {
            $this->assertInstanceOf(XdrSCSpecFunctionV0::class, $func,
                'Each function should be an instance of XdrSCSpecFunctionV0');
        }

        // Validate specific function names exist
        $functionNames = array_map(fn($func) => $func->name, $functions);
        $this->assertContains('__constructor', $functionNames,
            'Functions should include __constructor');
        $this->assertContains('mint', $functionNames,
            'Functions should include mint');
        $this->assertContains('burn', $functionNames,
            'Functions should include burn');
        $this->assertContains('transfer', $functionNames,
            'Functions should include transfer');
        $this->assertContains('transfer_from', $functionNames,
            'Functions should include transfer_from');
        $this->assertContains('balance', $functionNames,
            'Functions should include balance');
        $this->assertContains('approve', $functionNames,
            'Functions should include approve');
        $this->assertContains('allowance', $functionNames,
            'Functions should include allowance');
        $this->assertContains('decimals', $functionNames,
            'Functions should include decimals');
        $this->assertContains('name', $functionNames,
            'Functions should include name');
        $this->assertContains('symbol', $functionNames,
            'Functions should include symbol');
        $this->assertContains('set_admin', $functionNames,
            'Functions should include set_admin');
        $this->assertContains('burn_from', $functionNames,
            'Functions should include burn_from');

        // Test udtStructs() method - should return 3 structs
        $structs = $contractSpec->udtStructs();
        $this->assertCount(3, $structs,
            'ContractSpec udtStructs() should return exactly 3 structs');

        // Validate that all returned items are XdrSCSpecUDTStructV0 instances
        foreach ($structs as $struct) {
            $this->assertInstanceOf(XdrSCSpecUDTStructV0::class, $struct,
                'Each struct should be an instance of XdrSCSpecUDTStructV0');
        }

        // Validate specific struct names exist
        $structNames = array_map(fn($struct) => $struct->name, $structs);
        $this->assertContains('AllowanceDataKey', $structNames,
            'Structs should include AllowanceDataKey');
        $this->assertContains('AllowanceValue', $structNames,
            'Structs should include AllowanceValue');
        $this->assertContains('TokenMetadata', $structNames,
            'Structs should include TokenMetadata');

        // Validate AllowanceDataKey struct has expected fields
        $allowanceDataKey = null;
        foreach ($structs as $struct) {
            if ($struct->name === 'AllowanceDataKey') {
                $allowanceDataKey = $struct;
                break;
            }
        }
        $this->assertNotNull($allowanceDataKey, 'AllowanceDataKey struct should be found');
        $this->assertCount(2, $allowanceDataKey->fields,
            'AllowanceDataKey should have 2 fields');
        $this->assertEquals('from', $allowanceDataKey->fields[0]->name,
            'First field should be named "from"');
        $this->assertEquals('spender', $allowanceDataKey->fields[1]->name,
            'Second field should be named "spender"');

        // Test udtUnions() method - should return 1 union
        $unions = $contractSpec->udtUnions();
        $this->assertCount(1, $unions,
            'ContractSpec udtUnions() should return exactly 1 union');

        // Validate that all returned items are XdrSCSpecUDTUnionV0 instances
        foreach ($unions as $union) {
            $this->assertInstanceOf(XdrSCSpecUDTUnionV0::class, $union,
                'Each union should be an instance of XdrSCSpecUDTUnionV0');
        }

        // Validate specific union names exist
        $unionNames = array_map(fn($union) => $union->name, $unions);
        $this->assertContains('DataKey', $unionNames,
            'Unions should include DataKey');

        // Validate DataKey union has expected cases
        $dataKey = $unions[0];
        $this->assertEquals('DataKey', $dataKey->name,
            'Union should be named DataKey');
        $this->assertCount(4, $dataKey->cases,
            'DataKey union should have 4 cases');

        // Test udtEnums() method - should return 0 enums
        $enums = $contractSpec->udtEnums();
        $this->assertCount(0, $enums,
            'ContractSpec udtEnums() should return 0 enums for this contract');

        // Validate that all returned items are XdrSCSpecUDTEnumV0 instances (even if empty)
        foreach ($enums as $enum) {
            $this->assertInstanceOf(XdrSCSpecUDTEnumV0::class, $enum,
                'Each enum should be an instance of XdrSCSpecUDTEnumV0');
        }

        // Test udtErrorEnums() method - should return 0 error enums
        $errorEnums = $contractSpec->udtErrorEnums();
        $this->assertCount(0, $errorEnums,
            'ContractSpec udtErrorEnums() should return 0 error enums for this contract');

        // Validate that all returned items are XdrSCSpecUDTErrorEnumV0 instances (even if empty)
        foreach ($errorEnums as $errorEnum) {
            $this->assertInstanceOf(XdrSCSpecUDTErrorEnumV0::class, $errorEnum,
                'Each error enum should be an instance of XdrSCSpecUDTErrorEnumV0');
        }

        // Test events() method - should return 8 events
        $events = $contractSpec->events();
        $this->assertCount(8, $events,
            'ContractSpec events() should return exactly 8 events');

        // Validate that all returned items are XdrSCSpecEventV0 instances
        foreach ($events as $event) {
            $this->assertInstanceOf(XdrSCSpecEventV0::class, $event,
                'Each event should be an instance of XdrSCSpecEventV0');
        }

        // Validate specific event names exist
        $eventNames = array_map(fn($event) => $event->name, $events);
        $this->assertContains('SetAdmin', $eventNames,
            'Events should include SetAdmin');
        $this->assertContains('Approve', $eventNames,
            'Events should include Approve');
        $this->assertContains('Transfer', $eventNames,
            'Events should include Transfer');
        $this->assertContains('TransferWithAmountOnly', $eventNames,
            'Events should include TransferWithAmountOnly');
        $this->assertContains('Burn', $eventNames,
            'Events should include Burn');
        $this->assertContains('Mint', $eventNames,
            'Events should include Mint');
        $this->assertContains('MintWithAmountOnly', $eventNames,
            'Events should include MintWithAmountOnly');
        $this->assertContains('Clawback', $eventNames,
            'Events should include Clawback');

        // Validate Transfer event structure from ContractSpec
        $transferEvent = null;
        foreach ($events as $event) {
            if ($event->name === 'Transfer') {
                $transferEvent = $event;
                break;
            }
        }
        $this->assertNotNull($transferEvent, 'Transfer event should be found');
        $this->assertCount(1, $transferEvent->prefixTopics,
            'Transfer event should have 1 prefix topic');
        $this->assertEquals('transfer', $transferEvent->prefixTopics[0],
            'Transfer event prefix topic should be "transfer"');
        $this->assertCount(4, $transferEvent->params,
            'Transfer event should have 4 parameters');

        // Validate that ContractSpec can find specific functions by name using getFunc()
        $balanceFunc = $contractSpec->getFunc('balance');
        $this->assertNotNull($balanceFunc, 'ContractSpec getFunc() should find balance function');
        $this->assertInstanceOf(XdrSCSpecFunctionV0::class, $balanceFunc,
            'getFunc() should return XdrSCSpecFunctionV0 instance');
        $this->assertEquals('balance', $balanceFunc->name,
            'Found function should have correct name');
        $this->assertCount(1, $balanceFunc->inputs,
            'balance function should have 1 input parameter');

        // Validate that getFunc() returns null for non-existent function
        $nonExistentFunc = $contractSpec->getFunc('non_existent_function');
        $this->assertNull($nonExistentFunc,
            'ContractSpec getFunc() should return null for non-existent function');

        // Validate that findEntry() can locate entries by name
        $mintEntry = $contractSpec->findEntry('mint');
        $this->assertNotNull($mintEntry, 'ContractSpec findEntry() should find mint entry');
        $this->assertEquals(XdrSCSpecEntryKind::SC_SPEC_ENTRY_FUNCTION_V0, $mintEntry->type->value,
            'mint entry should be a function type');

        $dataKeyEntry = $contractSpec->findEntry('DataKey');
        $this->assertNotNull($dataKeyEntry, 'ContractSpec findEntry() should find DataKey entry');
        $this->assertEquals(XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_UNION_V0, $dataKeyEntry->type->value,
            'DataKey entry should be a union type');

        $transferEventEntry = $contractSpec->findEntry('Transfer');
        $this->assertNotNull($transferEventEntry, 'ContractSpec findEntry() should find Transfer entry');
        $this->assertEquals(XdrSCSpecEntryKind::SC_SPEC_ENTRY_EVENT_V0, $transferEventEntry->type->value,
            'Transfer entry should be an event type');

        // Validate that findEntry() returns null for non-existent entry
        $nonExistentEntry = $contractSpec->findEntry('NonExistentEntry');
        $this->assertNull($nonExistentEntry,
            'ContractSpec findEntry() should return null for non-existent entry');
    }

    private function printFunction(XdrSCSpecFunctionV0 $function) : void {
        print("Function: {$function->name}" . PHP_EOL);
        $index = 0;
        foreach ($function->inputs as $input) {
            print("input[{$index}] name: {$input->name}" . PHP_EOL);
            print("input[{$index}] type: {$this->getSpecTypeInfo($input->type)}" . PHP_EOL);
            if (strlen($input->doc) > 0) {
                print("input[{$index}] doc: {$input->doc}" . PHP_EOL);
            }
            $index ++;
        }
        $index = 0;
        foreach ($function->outputs as $output) {
            print("output[{$index}] type: {$this->getSpecTypeInfo($output)}" . PHP_EOL);
            $index ++;
        }
        if (strlen($function->doc) > 0) {
            print("doc: {$function->doc}" . PHP_EOL);
        }
    }

    private function printUdtStruct(XdrSCSpecUDTStructV0 $udtStruct) : void {
        print("UDT Struct: {$udtStruct->name}" . PHP_EOL);
        if (strlen($udtStruct->lib )> 0) {
            print("lib : {$udtStruct->lib}" . PHP_EOL);
        }
        $index = 0;
        foreach ($udtStruct->fields as $field) {
            print("field[{$index}] name: {$field->name}" . PHP_EOL);
            print("field[{$index}] type: {$this->getSpecTypeInfo($field->type)}" . PHP_EOL);
            if (strlen($field->doc) > 0) {
                print("field[{$index}] doc: {$field->doc}" . PHP_EOL);
            }
            $index ++;
        }
        if (strlen($udtStruct->doc) > 0) {
            print("doc : {$udtStruct->doc})" . PHP_EOL);
        }
    }

    private function printUdtUnion(XdrSCSpecUDTUnionV0 $udtUnion) : void {
        print("UDT Union: {$udtUnion->name}" . PHP_EOL);
        if (strlen($udtUnion->lib) > 0) {
            print("lib : {$udtUnion->lib}" . PHP_EOL);
        }
        $index = 0;
        foreach ($udtUnion->cases as $uCase) {
            switch($uCase->kind->value) {
                case XdrSCSpecUDTUnionCaseV0Kind::SC_SPEC_UDT_UNION_CASE_VOID_V0:
                    print("case[{$index}] is voidV0" . PHP_EOL);
                    print("case[{$index}] name: {$uCase->voidCase->name}" . PHP_EOL);
                    if (strlen($uCase->voidCase->doc) > 0) {
                        print("case[{$index}] doc: {$uCase->voidCase->doc}" . PHP_EOL);
                    }
                    break;
                case XdrSCSpecUDTUnionCaseV0Kind::SC_SPEC_UDT_UNION_CASE_TUPLE_V0:
                    print("case[{$index}] is tupleV0" . PHP_EOL);
                    print("case[{$index}] name: {$uCase->tupleCase->name}" . PHP_EOL);
                    $valueTypesStr = "[";
                    foreach ($uCase->tupleCase->type as $valueType) {
                        $valueTypesStr .= "{$this->getSpecTypeInfo($valueType)},";
                    }
                    $valueTypesStr .= "]";
                    print("case[{$index}] types: $valueTypesStr" . PHP_EOL);
                    if (strlen($uCase->tupleCase->doc) > 0) {
                        print("case[{$index}] doc: {$uCase->tupleCase->doc}" . PHP_EOL);
                    }
                    break;
            }
            $index ++;
        }
        if (strlen($udtUnion->doc) > 0) {
            print("doc : {$udtUnion->doc})" . PHP_EOL);
        }
    }

    private function printUdtEnum(XdrSCSpecUDTEnumV0 $udtEnum) : void {
        print("UDT Enum : {$udtEnum->name}" . PHP_EOL);
        if (strlen($udtEnum->lib) > 0) {
            print("lib : {$udtEnum->lib}" . PHP_EOL);
        }
        $index = 0;
        foreach ($udtEnum->cases as $uCase) {
            print("case[{$index}] name: {$uCase->name}" . PHP_EOL);
            print("case[{$index}] value: {$uCase->value}" . PHP_EOL);
            if (strlen($uCase->doc) > 0) {
                print("case[{$index}] doc: {$uCase->doc}" . PHP_EOL);
            }
            $index ++;
        }
        if (strlen($udtEnum->doc) > 0) {
            print("doc : {$udtEnum->doc}" . PHP_EOL);
        }
    }

    private function printUdtErrorEnum(XdrSCSpecUDTErrorEnumV0 $udtErrorEnum) : void {
        print("UDT Error Enum : {$udtErrorEnum->name}" . PHP_EOL);
        if (strlen($udtErrorEnum->lib) > 0) {
            print("lib : {$udtErrorEnum->lib}" . PHP_EOL);
        }
        $index = 0;
        foreach ($udtErrorEnum->cases as $uCase) {
            print("case[{$index}] name: {$uCase->name}" . PHP_EOL);
            print("case[{$index}] value: {$uCase->value}" . PHP_EOL);
            if (strlen($uCase->doc) > 0) {
                print("case[{$index}] doc: {$uCase->doc}" . PHP_EOL);
            }
            $index ++;
        }
        if (strlen($udtErrorEnum->doc) > 0) {
            print("doc : {$udtErrorEnum->doc}" . PHP_EOL);
        }
    }

    /**
     * Prints event specification information from Soroban contract metadata.
     *
     * Events are a mechanism for Soroban smart contracts to emit structured data during execution.
     * This method displays all details about an event including its name, library, prefix topics,
     * parameters with their types and locations, data format, and documentation.
     *
     * @param XdrSCSpecEventV0 $event The event specification to print
     * @return void
     */
    private function printEvent(XdrSCSpecEventV0 $event) : void {
        print("Event: {$event->name}" . PHP_EOL);
        print("lib: {$event->lib}" . PHP_EOL);

        $index = 0;
        foreach ($event->prefixTopics as $prefixTopic) {
            print("prefixTopic[{$index}] name: {$prefixTopic}" . PHP_EOL);
            $index ++;
        }

        $index = 0;
        foreach ($event->params as $param) {
            print("param[{$index}] name: {$param->name}" . PHP_EOL);
            if (strlen($param->doc) > 0) {
                print("param[{$index}] doc : {$param->doc}" . PHP_EOL);
            }
            print("param[{$index}] type: {$this->getSpecTypeInfo($param->type)}" . PHP_EOL);

            if ($param->location->value == XdrSCSpecEventParamLocationV0::SC_SPEC_EVENT_PARAM_LOCATION_DATA) {
                print("param[{$index}] location: data" . PHP_EOL);
            } else if ($param->location->value == XdrSCSpecEventParamLocationV0::SC_SPEC_EVENT_PARAM_LOCATION_TOPIC_LIST) {
                print("param[{$index}] location: topic list" . PHP_EOL);
            } else {
                print("param[{$index}] location: unknown" . PHP_EOL);
            }
            $index ++;
        }

        if ($event->dataFormat->value == XdrSCSpecEventDataFormat::SC_SPEC_EVENT_DATA_FORMAT_SINGLE_VALUE) {
            print("data format: single value" . PHP_EOL);
        } else if ($event->dataFormat->value == XdrSCSpecEventDataFormat::SC_SPEC_EVENT_DATA_FORMAT_MAP) {
            print("data format: map" . PHP_EOL);
        } else if ($event->dataFormat->value == XdrSCSpecEventDataFormat::SC_SPEC_EVENT_DATA_FORMAT_VEC) {
            print("data format: vec" . PHP_EOL);
        } else {
            print("data format: unknown" . PHP_EOL);
        }

        if (strlen($event->doc) > 0) {
            print("doc : {$event->doc}" . PHP_EOL);
        }
    }

    /**
     * Test SorobanContractInfo supportedSeps parsing (SEP-47).
     * SEP-47 defines how contracts expose which SEPs they support via meta entries.
     */
    public function testSupportedSepsParsing(): void
    {
        // Test with multiple SEPs (SEP-47 format: comma-separated numbers)
        $metaWithMultipleSeps = [
            'sep' => '1,10,24',
            'other' => 'value'
        ];
        $info1 = new \Soneso\StellarSDK\Soroban\SorobanContractInfo(1, [], $metaWithMultipleSeps);
        $this->assertEquals(['1', '10', '24'], $info1->supportedSeps);

        // Test with single SEP
        $metaWithSingleSep = ['sep' => '47'];
        $info2 = new \Soneso\StellarSDK\Soroban\SorobanContractInfo(1, [], $metaWithSingleSep);
        $this->assertEquals(['47'], $info2->supportedSeps);

        // Test with no SEP meta entry
        $metaWithoutSep = ['other' => 'value'];
        $info3 = new \Soneso\StellarSDK\Soroban\SorobanContractInfo(1, [], $metaWithoutSep);
        $this->assertEmpty($info3->supportedSeps);

        // Test with empty SEP value
        $metaWithEmptySep = ['sep' => ''];
        $info4 = new \Soneso\StellarSDK\Soroban\SorobanContractInfo(1, [], $metaWithEmptySep);
        $this->assertEmpty($info4->supportedSeps);

        // Test with SEPs containing extra spaces
        $metaWithSpaces = ['sep' => '  1  ,  2  ,  3  '];
        $info5 = new \Soneso\StellarSDK\Soroban\SorobanContractInfo(1, [], $metaWithSpaces);
        $this->assertEquals(['1', '2', '3'], $info5->supportedSeps);

        // Test with trailing/leading commas
        $metaWithCommas = ['sep' => ',41,40,'];
        $info6 = new \Soneso\StellarSDK\Soroban\SorobanContractInfo(1, [], $metaWithCommas);
        $this->assertEquals(['41', '40'], $info6->supportedSeps);

        // Test with duplicate SEPs (should be deduplicated)
        $metaWithDuplicates = ['sep' => '1,10,1,24,10'];
        $info7 = new \Soneso\StellarSDK\Soroban\SorobanContractInfo(1, [], $metaWithDuplicates);
        $this->assertEquals(['1', '10', '24'], $info7->supportedSeps);

        // Test with real-world example from SEP-47 spec
        $metaRealWorld = ['sep' => '41,40'];
        $info8 = new \Soneso\StellarSDK\Soroban\SorobanContractInfo(1, [], $metaRealWorld);
        $this->assertEquals(['41', '40'], $info8->supportedSeps);
    }

    private function getSpecTypeInfo(XdrSCSpecTypeDef $specType) : string {
        switch ($specType->type->value) {
            case XdrSCSpecType::SC_SPEC_TYPE_VAL:
                return "val";
            case XdrSCSpecType::SC_SPEC_TYPE_BOOL:
                return "bool";
            case XdrSCSpecType::SC_SPEC_TYPE_VOID:
                return "void";
            case XdrSCSpecType::SC_SPEC_TYPE_ERROR:
                return "error";
            case XdrSCSpecType::SC_SPEC_TYPE_U32:
                return "u32";
            case XdrSCSpecType::SC_SPEC_TYPE_I32:
                return "i32";
            case XdrSCSpecType::SC_SPEC_TYPE_U64:
                return "u64";
            case XdrSCSpecType::SC_SPEC_TYPE_I64:
                return "i64";
            case XdrSCSpecType::SC_SPEC_TYPE_TIMEPOINT:
                return "timepoint";
            case XdrSCSpecType::SC_SPEC_TYPE_DURATION:
                return "duration";
            case XdrSCSpecType::SC_SPEC_TYPE_U128:
                return "u128";
            case XdrSCSpecType::SC_SPEC_TYPE_I128:
                return "i128";
            case XdrSCSpecType::SC_SPEC_TYPE_U256:
                return "u256";
            case XdrSCSpecType::SC_SPEC_TYPE_I256:
                return "i256";
            case XdrSCSpecType::SC_SPEC_TYPE_BYTES:
                return "bytes";
            case XdrSCSpecType::SC_SPEC_TYPE_STRING:
                return "string";
            case XdrSCSpecType::SC_SPEC_TYPE_SYMBOL:
                return "symbol";
            case XdrSCSpecType::SC_SPEC_TYPE_ADDRESS:
                return "address";
            case XdrSCSpecType::SC_SPEC_TYPE_OPTION:
                $valueType = $this->getSpecTypeInfo($specType->option->valueType);
                return "option (value type: {$valueType})";
            case XdrSCSpecType::SC_SPEC_TYPE_RESULT:
                $okType = $this->getSpecTypeInfo($specType->result->okType);
                $errorType = $this->getSpecTypeInfo($specType->result->errorType);
                return "result (ok type: {$okType} , error type: {$errorType})";
            case XdrSCSpecType::SC_SPEC_TYPE_VEC:
                $elementType = $this->getSpecTypeInfo($specType->vec->elementType);
                return "option (element type: {$elementType})";
            case XdrSCSpecType::SC_SPEC_TYPE_MAP:
                $keyType = $this->getSpecTypeInfo($specType->map->keyType);
                $valueType = $this->getSpecTypeInfo($specType->map->valueType);
                return "map (key type: {$keyType} , value type: {$valueType})";
            case XdrSCSpecType::SC_SPEC_TYPE_TUPLE:
                $valueTypesStr = "[";
                foreach ($specType->tuple->valueTypes as $valueType) {
                    $valueTypesStr .= "{$this->getSpecTypeInfo($valueType)},";
                }
                $valueTypesStr .= "]";
                return "tuple (value types: {$valueTypesStr})";
            case XdrSCSpecType::SC_SPEC_TYPE_BYTES_N:
                return "bytesN (n: {$specType->bytesN->n})";
            case XdrSCSpecType::SC_SPEC_TYPE_UDT:
                return "udt (name: {$specType->udt->name})";
            default:
                return 'unknown';
        }
    }
}