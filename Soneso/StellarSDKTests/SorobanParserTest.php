<?php  declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

use PHPUnit\Framework\TestCase;
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

class SorobanParserTest extends TestCase
{

    const CONTRACT_PATH = './wasm/soroban_token_contract.wasm';

    public function setUp(): void
    {
        // Turn on error reporting
        error_reporting(E_ALL);
    }

    public function testTokenContractParsing(): void
    {
        $contractCode = file_get_contents(self::CONTRACT_PATH, false);
        $contractInfo = SorobanContractParser::parseContractByteCode($contractCode);
        $this->assertCount(17, $contractInfo->specEntries);
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
                default:
                    print('specEntry [' . $index . '] -> kind(' . $entry->type->value .'): ' . 'unknown ' . PHP_EOL);
                    break;
            }
            print(PHP_EOL);
            $index++;
        }
        print("--------------------------------");
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