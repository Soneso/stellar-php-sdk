<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Contract;

use InvalidArgumentException;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Xdr\XdrInt128Parts;
use Soneso\StellarSDK\Xdr\XdrInt256Parts;
use Soneso\StellarSDK\Xdr\XdrSCMapEntry;
use Soneso\StellarSDK\Xdr\XdrSCSpecEntry;
use Soneso\StellarSDK\Xdr\XdrSCSpecEntryKind;
use Soneso\StellarSDK\Xdr\XdrSCSpecFunctionV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecType;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeDef;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTEnumV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTStructV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionCaseV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionV0;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrUInt128Parts;
use Soneso\StellarSDK\Xdr\XdrUInt256Parts;

class ContractSpec
{
    /**
     * @var array<XdrSCSpecEntry>
     */
    public array $entries;

    /**
     * @param array<XdrSCSpecEntry> $entries
     */
    public function __construct(array $entries)
    {
        $this->entries = $entries;
    }

    /**
     * Gets the XDR functions from the spec.
     * @return array<XdrSCSpecFunctionV0>
     */
    public function funcs() : array {
        /**
         * @var array<XdrSCSpecFunctionV0> $result
         */
        $result = array();
        foreach ($this->entries as $entry) {
            if($entry->type->value == XdrSCSpecEntryKind::SC_SPEC_ENTRY_FUNCTION_V0 && $entry->functionV0 !== null) {
                $result[] = $entry->functionV0;
            }
        }
        return $result;
    }

    /**
     * Gets the XDR function spec for the given function name if available.
     * @param string $name name of the function
     * @return XdrSCSpecFunctionV0|null the function spec
     */
    public function getFunc(string $name) : ?XdrSCSpecFunctionV0 {
        foreach ($this->entries as $entry) {
            if ($entry->type->value == XdrSCSpecEntryKind::SC_SPEC_ENTRY_FUNCTION_V0 && $entry->functionV0 !== null) {
                if ($entry->functionV0->name === $name) {
                    return $entry->functionV0;
                }
            }
        }
        return null;
    }

    /**
     * Converts native arguments to XdrSCVal values for calling a contract function.
     * @param string $name name of the function
     * @param array<string, mixed> $args the arguments e.g. ["arg1 name" => "value1, "arg2 name", 1234]
     * @return array<XdrSCVal> the converted arguments for calling the contract function (ordered by position)
     */
    public function funcArgsToXdrSCValues(string $name, array $args): array {
        $func = $this->getFunc($name);
        if ($func === null) {
            throw new InvalidArgumentException("Function $name does not exist.");
        }
        /**
         * @var array<XdrSCVal> $result
         */
        $result = array();
        foreach ($func->inputs as $input) {
            $nativeArg = null;
            foreach ($args as $argKey => $argValue) {
                if ($argKey === $input->name) {
                    $nativeArg = $argValue;
                }
            }
            if ($nativeArg === null) {
                throw new InvalidArgumentException("Arg not found for function input: $input->name");
            }
            $result[] = $this->nativeToXdrSCVal($nativeArg, $input->type);
        }
        return $result;
    }

    /**
     * Finds the XDR spec entry for the given name.
     *
     * @param string $name the name to find
     * @return XdrSCSpecEntry|null the entry
     */
    public function findEntry(string $name) : ?XdrSCSpecEntry {
        foreach ($this->entries as $entry) {
            if ($entry->functionV0 !== null) {
                if ($entry->functionV0->name === $name) {
                    return $entry;
                }
            } else if  ($entry->udtStructV0 !== null) {
                if ($entry->udtStructV0->name === $name) {
                    return $entry;
                }
            } else if  ($entry->udtUnionV0 !== null) {
                if ($entry->udtUnionV0->name === $name) {
                    return $entry;
                }
            } else if  ($entry->udtEnumV0 !== null) {
                if ($entry->udtEnumV0->name === $name) {
                    return $entry;
                }
            } else if  ($entry->udtErrorEnumV0 !== null) {
                if ($entry->udtErrorEnumV0->name === $name) {
                    return $entry;
                }
            }
        }
        return null;
    }

    /**
     * Converts a native PHP value to an XdrSCVal based on the given type.
     *
     * @param mixed $val native PHP value.
     * @param XdrSCSpecTypeDef $ty the expected type.
     * @return XdrSCVal the converted XdrSCVal.
     */
    public function nativeToXdrSCVal(mixed $val, XdrSCSpecTypeDef $ty) : XdrSCVal {

        $type = $ty->type;
        if ($type->value === XdrSCSpecType::SC_SPEC_TYPE_UDT) {
            if ($ty->udt === null) {
                throw new InvalidArgumentException("Failed to parse udt, udt is null.");
            }
            return $this->nativeToUdt($val, $ty->udt->name);
        }

        if ($type->value === XdrSCSpecType::SC_SPEC_TYPE_OPTION) {
            if ($ty->option === null) {
                throw new InvalidArgumentException("Failed to parse option, option is null.");
            }
            if ($val === null) {
                return XdrSCVal::forVoid();
            }
            return $this->nativeToXdrSCVal($val, $ty->option->valueType);
        }

        if ($val === null) {
            if ($type->value !== XdrSCSpecType::SC_SPEC_TYPE_VOID) {
                throw new InvalidArgumentException("Type was not void but val was null.");
            }
            return XdrSCVal::forVoid();
        }
        if ($val instanceof XdrSCVal) {
            return $val;
        }
        if ($val instanceof Address) {
            if ($type->value !== XdrSCSpecType::SC_SPEC_TYPE_ADDRESS) {
                throw new InvalidArgumentException("Type was not address but val was address.");
            }
            return  $val->toXdrSCVal();
        }

        if (is_array($val)) {
            if ($type->value === XdrSCSpecType::SC_SPEC_TYPE_VEC) {
                if ($ty->vec === null) {
                    throw new InvalidArgumentException("Failed to parse vec, vec is null.");
                }
                /**
                 * @var array<XdrSCVal> $scVals
                 */
                $scValues = array();
                foreach ($val as $v) {
                    $scValues[] = $this->nativeToXdrSCVal($v, $ty->vec->elementType);
                }
                return XdrSCVal::forVec($scValues);
            }
            if ($type->value === XdrSCSpecType::SC_SPEC_TYPE_TUPLE) {
                if ($ty->tuple === null) {
                    throw new InvalidArgumentException("Failed to parse tuple, tuple is null.");
                }
                $countValues = count($val);
                $countValueTypes = count($ty->tuple->valueTypes);
                if ($countValues !== $countValueTypes) {
                    throw new InvalidArgumentException("Tuple expects $countValueTypes values, but $countValues were provided.");
                }
                /**
                 * @var array<XdrSCVal> $scVals
                 */
                $scValues = array();
                $i = 0;
                foreach ($val as $v) {
                    $scValues[] = $this->nativeToXdrSCVal($v, $ty->tuple->valueTypes[$i]);
                    $i++;
                }
                return XdrSCVal::forVec($scValues);
            }
            if ($type->value === XdrSCSpecType::SC_SPEC_TYPE_MAP) {
                if ($ty->map === null) {
                    throw new InvalidArgumentException("Failed to parse map, map is null");
                }
                $keyType = $ty->map->keyType;
                $valueType = $ty->map->valueType;
                /**
                 * @var array<XdrSCMapEntry> $mapEntries
                 */
                $mapEntries = array();
                foreach ($val as $valKey => $valValue) {
                    $mapEntryKey = $this->nativeToXdrSCVal($valKey, $keyType);
                    $mapEntryValue = $this->nativeToXdrSCVal($valValue, $valueType);
                    $mapEntries[] = new XdrSCMapEntry($mapEntryKey, $mapEntryValue);
                }
                return XdrSCVal::forMap($mapEntries);
            }
            throw new InvalidArgumentException("Type was not vec, tuple or map but val was array");
        }

        if(is_int($val)) {
            if ($type->value === XdrSCSpecType::SC_SPEC_TYPE_U32) {
                if ($val < 0) {
                    throw new InvalidArgumentException("Negative integer value provided for u32.");
                }
                return XdrSCVal::forU32($val);
            }
            if ($type->value === XdrSCSpecType::SC_SPEC_TYPE_I32) {
                return XdrSCVal::forI32($val);
            }
            if ($type->value === XdrSCSpecType::SC_SPEC_TYPE_U64) {
                if ($val < 0) {
                    throw new InvalidArgumentException("Negative integer value provided for u64.");
                }
                return XdrSCVal::forU64($val);
            }
            if ($type->value === XdrSCSpecType::SC_SPEC_TYPE_I64) {
                return XdrSCVal::forI64($val);
            }
            if ($val >= 0) {
                if ($type->value === XdrSCSpecType::SC_SPEC_TYPE_U128) {
                    return XdrSCVal::forU128(new XdrUInt128Parts(hi:0, lo:$val));
                }
                if ($type->value === XdrSCSpecType::SC_SPEC_TYPE_I128) {
                    return XdrSCVal::forI128(new XdrInt128Parts(hi:0, lo:$val));
                }
                if ($type->value === XdrSCSpecType::SC_SPEC_TYPE_U256) {
                    return XdrSCVal::forU256(new XdrUInt256Parts(0,0,0, $val));
                }
                if ($type->value === XdrSCSpecType::SC_SPEC_TYPE_I256) {
                    return XdrSCVal::forI256(new XdrInt256Parts(0,0,0, $val));
                }
            }
            throw new InvalidArgumentException("Invalid type for val of type int.");
        }

        if (is_string($val)) {
            if ($type->value === XdrSCSpecType::SC_SPEC_TYPE_BYTES_N || $type->value === XdrSCSpecType::SC_SPEC_TYPE_BYTES) {
                return XdrSCVal::forBytes($val);
            }
            if ($type->value === XdrSCSpecType::SC_SPEC_TYPE_STRING) {
                return XdrSCVal::forString($val);
            }
            if ($type->value === XdrSCSpecType::SC_SPEC_TYPE_SYMBOL) {
                return XdrSCVal::forSymbol($val);
            }
            if ($type->value === XdrSCSpecType::SC_SPEC_TYPE_ADDRESS) {
                if (str_starts_with($val, "C")) {
                    $addr = new Address(type:1, contractId: $val);
                } else {
                    $addr = new Address(type:0, accountId: $val);
                }
                return $addr->toXdrSCVal();
            }
            throw new InvalidArgumentException("Invalid type for val of type string.");
        }

        if(is_bool($val)) {
            if ($type->value === XdrSCSpecType::SC_SPEC_TYPE_BOOL) {
                return XdrSCVal::forBool($val);
            }
            throw new InvalidArgumentException("Invalid type for val of type bool.");
        }
        $valType = gettype($val);
        throw new InvalidArgumentException("Failed to convert val of type $valType");
    }

    private function nativeToUdt(mixed $val, string $name) : XdrSCVal {
        $entry = $this->findEntry($name);
        if ($entry === null) {
            throw new InvalidArgumentException("entry not found for $name");
        }
        if ($entry->type->value === XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_ENUM_V0 && $entry->udtEnumV0 !== null) {
            if (!is_int($val)) {
                $t = gettype(value: $val);
                throw new InvalidArgumentException("expected int for enum $name, but got $t");
            }
            return $this->nativeToEnum($val, $entry->udtEnumV0);
        } else if ($entry->type->value === XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_STRUCT_V0 && $entry->udtStructV0 !== null) {
            return $this->nativeToStruct($val, $entry->udtStructV0);
        } else if ($entry->type->value === XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_UNION_V0 && $entry->udtUnionV0 !== null) {
            if ($val instanceof NativeUnionVal) {
                return $this->nativeToUnion($val, $entry->udtUnionV0);
            } else {
                $unionName = $entry->udtUnionV0->name;
                throw new InvalidArgumentException("for  union $unionName, val must be of type NativeUnionVal, got " . gettype($val));
            }
        } else {
            throw new InvalidArgumentException("failed to parse udt $name");
        }
    }

    private function nativeToEnum(int $val, XdrSCSpecUDTEnumV0 $enum) : XdrSCVal {
        foreach ($enum->cases as $case) {
            if ($case->value === $val) {
                return XdrSCVal::forU32($val);
            }
        }
        throw new InvalidArgumentException("no such enum entry: $val in $enum->name");
    }

    private function nativeToStruct(mixed $val, XdrSCSpecUDTStructV0 $struct) : XdrSCVal {
        $fields = $struct->fields;
        $hasNumeric = false;
        $allNumeric = true;
        foreach ($fields as $field) {
            if (is_numeric($field->name)) {
                $hasNumeric = true;
            } else {
                $allNumeric = false;
            }
        }
        if ($hasNumeric) {
            if (!$allNumeric) {
                throw new InvalidArgumentException("mixed numeric and non-numeric field names are not allowed");
            }

            if (!is_array($val)) {
                throw new InvalidArgumentException("value must be an array of numbers for struct $struct->name");
            }
            if (count($val) !== count($fields)) {
                throw new InvalidArgumentException("value contains invalid number of of entries for struct $struct->name");
            }
            /**
             * @var array<XdrSCVal> $arr
             */
            $arr = array();
            $i = 0;
            foreach ($fields as $field) {
                $arr[] = $this->nativeToXdrSCVal($val[$i], $field->type);
                $i++;
            }
            return XdrSCVal::forVec($arr);
        }

        /**
         * @var array<XdrSCMapEntry> $mapEntries
         */
        $mapEntries = array();
        foreach ($fields as $field) {
            $name = $field->name;
            $entryKey = $this->nativeToXdrSCVal($name, new XdrSCSpecTypeDef(XdrSCSpecType::SYMBOL()));
            $entryVal = $this->nativeToXdrSCVal($val[$name], $field->type);
            $mapEntries[] = new XdrSCMapEntry(key: $entryKey, val: $entryVal);
        }
        return XdrSCVal::forMap($mapEntries);
    }

    private function nativeToUnion(NativeUnionVal $val,XdrSCSpecUDTUnionV0 $union) : XdrSCVal {
        $entryName = $val->tag;
        /**
         * @var XdrSCSpecUDTUnionCaseV0 $caseFound
         */
        $caseFound = null;
        foreach ($union->cases as $case) {
            if($case->voidCase !== null && $case->voidCase->name === $entryName) {
                $caseFound = $case;
                break;
            } else if ($case->tupleCase !== null && $case->tupleCase->name === $entryName) {
                $caseFound = $case;
                break;
            }
        }
        if ($caseFound === null) {
            throw new InvalidArgumentException("no such enum entry: $entryName in $union->name");
        }
        $key = XdrSCVal::forSymbol($entryName);
        if ($caseFound->voidCase !== null) {
            return XdrSCVal::forVec([$key]);
        } else if ($caseFound->tupleCase !== null) {
            $types = $caseFound->tupleCase->type;
            if ($val->values === null || count($val->values) !== count($types)) {
                $countTypes = count($types);
                $countValues = $val->values === null ? 0: count($val->values);
                throw new InvalidArgumentException("union $union->name expects $countTypes values, but got $countValues");
            }
            /**
             * @var array<XdrSCVal> $scValues
             */
            $scValues = array();
            $scValues[] = $key;
            $i = 0;
            foreach ($val->values as $value) {
                $scValues[] = $this->nativeToXdrSCVal($value, $types[$i]);
                $i++;
            }
            return XdrSCVal::forVec($scValues);
        }
        throw new InvalidArgumentException("failed to parse union case $entryName with val $entryName");
    }

}