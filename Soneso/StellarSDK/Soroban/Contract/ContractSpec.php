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
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTErrorEnumV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTStructV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionCaseV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecEventV0;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrUInt128Parts;
use Soneso\StellarSDK\Xdr\XdrUInt256Parts;

/**
 * Contract specification parser and type converter for Soroban smart contracts
 *
 * This class parses contract spec entries extracted from contract WASM bytecode and provides
 * utilities for working with contract functions, types, and arguments. It handles conversion
 * between native PHP values and XDR SCVal types based on the contract specification, enabling
 * type-safe contract interactions.
 *
 * The contract spec includes function signatures, user-defined types (structs, unions, enums),
 * error definitions, and event specifications as defined in SEP-48.
 *
 * @package Soneso\StellarSDK\Soroban\Contract
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0048.md SEP-48: Smart Contract Spec
 * @see SorobanContractParser For parsing contract bytecode to extract spec entries
 * @since 1.0.0
 */
class ContractSpec
{
    /**
     * @var array<XdrSCSpecEntry> The parsed contract specification entries
     */
    public array $entries;

    /**
     * Creates a new contract spec from parsed spec entries
     *
     * @param array<XdrSCSpecEntry> $entries The contract spec entries from the contract bytecode
     */
    public function __construct(array $entries)
    {
        $this->entries = $entries;
    }

    /**
     * Retrieves all function specifications from the contract spec
     *
     * Returns the XDR function definitions including function names, input parameters,
     * output types, and documentation strings.
     *
     * @return array<XdrSCSpecFunctionV0> Array of function specifications
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
     * Retrieves the function specification for a specific function name
     *
     * Searches the contract spec for a function with the given name and returns its
     * specification including parameters and return type.
     *
     * @param string $name The name of the function to look up
     * @return XdrSCSpecFunctionV0|null The function specification or null if not found
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
     * Retrieves all user-defined struct type specifications
     *
     * Returns struct definitions including field names and types for all structs
     * defined in the contract.
     *
     * @return array<XdrSCSpecUDTStructV0> Array of struct specifications
     */
    public function udtStructs() : array {
        /**
         * @var array<XdrSCSpecUDTStructV0> $result
         */
        $result = array();
        foreach ($this->entries as $entry) {
            if($entry->type->value == XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_STRUCT_V0 && $entry->udtStructV0 !== null) {
                $result[] = $entry->udtStructV0;
            }
        }
        return $result;
    }

    /**
     * Retrieves all user-defined union type specifications
     *
     * Returns union definitions including case names and their associated types
     * for all unions defined in the contract.
     *
     * @return array<XdrSCSpecUDTUnionV0> Array of union specifications
     */
    public function udtUnions() : array {
        /**
         * @var array<XdrSCSpecUDTUnionV0> $result
         */
        $result = array();
        foreach ($this->entries as $entry) {
            if($entry->type->value == XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_UNION_V0 && $entry->udtUnionV0 !== null) {
                $result[] = $entry->udtUnionV0;
            }
        }
        return $result;
    }

    /**
     * Retrieves all user-defined enum type specifications
     *
     * Returns enum definitions including case names and values for all enums
     * defined in the contract.
     *
     * @return array<XdrSCSpecUDTEnumV0> Array of enum specifications
     */
    public function udtEnums() : array {
        /**
         * @var array<XdrSCSpecUDTEnumV0> $result
         */
        $result = array();
        foreach ($this->entries as $entry) {
            if($entry->type->value == XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_ENUM_V0 && $entry->udtEnumV0 !== null) {
                $result[] = $entry->udtEnumV0;
            }
        }
        return $result;
    }

    /**
     * Retrieves all user-defined error enum type specifications
     *
     * Returns error enum definitions including error codes and messages for all
     * error types defined in the contract.
     *
     * @return array<XdrSCSpecUDTErrorEnumV0> Array of error enum specifications
     */
    public function udtErrorEnums() : array {
        /**
         * @var array<XdrSCSpecUDTErrorEnumV0> $result
         */
        $result = array();
        foreach ($this->entries as $entry) {
            if($entry->type->value == XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_ERROR_ENUM_V0 && $entry->udtErrorEnumV0 !== null) {
                $result[] = $entry->udtErrorEnumV0;
            }
        }
        return $result;
    }

    /**
     * Retrieves all event specifications from the contract spec
     *
     * Returns event definitions including event names, topics, and data fields
     * for all events that can be emitted by the contract.
     *
     * @return array<XdrSCSpecEventV0> Array of event specifications
     */
    public function events() : array {
        /**
         * @var array<XdrSCSpecEventV0> $result
         */
        $result = array();
        foreach ($this->entries as $entry) {
            if($entry->type->value == XdrSCSpecEntryKind::SC_SPEC_ENTRY_EVENT_V0 && $entry->eventV0 !== null) {
                $result[] = $entry->eventV0;
            }
        }
        return $result;
    }

    /**
     * Converts native PHP arguments to XDR SCVal values for a contract function call
     *
     * Takes a function name and associative array of named arguments, looks up the function
     * specification, and converts each argument to the appropriate XDR SCVal type based on
     * the function's parameter types. The returned array is ordered by parameter position.
     *
     * Supported PHP types include primitives (int, string, bool), arrays (for vectors/tuples/maps),
     * Address objects, and BigInt values for large integers. See the Soroban documentation for
     * full type mapping details.
     *
     * @param string $name The name of the function to call
     * @param array<string, mixed> $args Associative array of argument name to value (e.g., ["amount" => 1000])
     * @return array<XdrSCVal> Array of converted XDR values in parameter order
     * @throws \InvalidArgumentException If the function is not found or argument conversion fails
     * @see https://github.com/Soneso/stellar-php-sdk/blob/main/soroban.md Soroban Type Conversion Documentation
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
     * Finds a spec entry by name across all entry types
     *
     * Searches for a function, struct, union, enum, error enum, or event with the given name
     * and returns the matching spec entry.
     *
     * @param string $name The name of the entry to find
     * @return XdrSCSpecEntry|null The spec entry or null if not found
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
            } else if  ($entry->eventV0 !== null) {
                if ($entry->eventV0->name === $name) {
                    return $entry;
                }
            }
        }
        return null;
    }

    /**
     * Converts a native PHP value to an XDR SCVal based on the contract spec type
     *
     * Performs type-safe conversion from PHP native types to Soroban XDR SCVal types according
     * to the contract specification. Handles primitives, collections, user-defined types, and
     * special types like addresses and big integers.
     *
     * Supported conversions include:
     * - Primitives: int, string, bool
     * - Collections: arrays to vectors, tuples, or maps
     * - BigInts: GMP resources or numeric strings for 128/256-bit integers
     * - Addresses: Address objects or G/C-prefixed strings
     * - UDTs: Custom structs, unions, and enums
     *
     * @param mixed $val The native PHP value to convert
     * @param XdrSCSpecTypeDef $ty The expected Soroban type from the contract spec
     * @return XdrSCVal The converted XDR value
     * @throws InvalidArgumentException If the UDT is not found in the contract spec
     * @throws InvalidArgumentException If the value type doesn't match the expected type (e.g., string when int expected)
     * @throws InvalidArgumentException If type validation fails (e.g., negative value for unsigned type)
     * @throws InvalidArgumentException If array/tuple/map structure doesn't match the spec
     * @throws InvalidArgumentException If the value cannot be converted to the specified type
     * @see https://github.com/Soneso/stellar-php-sdk/blob/main/soroban.md Soroban Type Conversion Documentation
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
            // For 128-bit and 256-bit types, use BigInt methods which handle all integer values
            if ($type->value === XdrSCSpecType::SC_SPEC_TYPE_U128) {
                return XdrSCVal::forU128BigInt($val);
            }
            if ($type->value === XdrSCSpecType::SC_SPEC_TYPE_I128) {
                return XdrSCVal::forI128BigInt($val);
            }
            if ($type->value === XdrSCSpecType::SC_SPEC_TYPE_U256) {
                return XdrSCVal::forU256BigInt($val);
            }
            if ($type->value === XdrSCSpecType::SC_SPEC_TYPE_I256) {
                return XdrSCVal::forI256BigInt($val);
            }
            throw new InvalidArgumentException("Invalid type for val of type int.");
        }

        // Handle GMP resources and string BigInt values for U128, I128, U256, I256
        if ((is_resource($val) && get_resource_type($val) === 'GMP integer') || 
            (is_object($val) && $val instanceof \GMP)) {
            
            // Handle BigInt values for 128-bit and 256-bit types
            if ($type->value === XdrSCSpecType::SC_SPEC_TYPE_U128) {
                return XdrSCVal::forU128BigInt($val);
            }
            if ($type->value === XdrSCSpecType::SC_SPEC_TYPE_I128) {
                return XdrSCVal::forI128BigInt($val);
            }
            if ($type->value === XdrSCSpecType::SC_SPEC_TYPE_U256) {
                return XdrSCVal::forU256BigInt($val);
            }
            if ($type->value === XdrSCSpecType::SC_SPEC_TYPE_I256) {
                return XdrSCVal::forI256BigInt($val);
            }
        }
        
        // Handle numeric strings for BigInt types
        if (is_string($val) && preg_match('/^-?\d+$/', $val)) {
            if ($type->value === XdrSCSpecType::SC_SPEC_TYPE_U128) {
                return XdrSCVal::forU128BigInt($val);
            }
            if ($type->value === XdrSCSpecType::SC_SPEC_TYPE_I128) {
                return XdrSCVal::forI128BigInt($val);
            }
            if ($type->value === XdrSCSpecType::SC_SPEC_TYPE_U256) {
                return XdrSCVal::forU256BigInt($val);
            }
            if ($type->value === XdrSCSpecType::SC_SPEC_TYPE_I256) {
                return XdrSCVal::forI256BigInt($val);
            }
        }

        // Handle XdrUInt128Parts and XdrInt128Parts directly for backward compatibility
        if ($val instanceof XdrUInt128Parts && $type->value === XdrSCSpecType::SC_SPEC_TYPE_U128) {
            return XdrSCVal::forU128($val);
        }
        if ($val instanceof XdrInt128Parts && $type->value === XdrSCSpecType::SC_SPEC_TYPE_I128) {
            return XdrSCVal::forI128($val);
        }
        if ($val instanceof XdrUInt256Parts && $type->value === XdrSCSpecType::SC_SPEC_TYPE_U256) {
            return XdrSCVal::forU256($val);
        }
        if ($val instanceof XdrInt256Parts && $type->value === XdrSCSpecType::SC_SPEC_TYPE_I256) {
            return XdrSCVal::forI256($val);
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

    /**
     * Converts a native value to a user-defined type (UDT)
     *
     * Handles conversion of PHP values to custom contract types including structs, unions, and enums.
     * For structs, expects an associative array with field names as keys. For unions, expects a
     * NativeUnionVal instance. For enums, expects an integer value.
     *
     * @internal This is an internal helper method used by nativeToXdrSCVal()
     *
     * @param mixed $val The native PHP value (array for struct, NativeUnionVal for union, int for enum)
     * @param string $name The name of the user-defined type as defined in the contract spec
     * @return XdrSCVal The converted XDR value
     * @throws InvalidArgumentException If the UDT is not found in the contract spec
     * @throws InvalidArgumentException If the value type doesn't match the expected UDT type
     * @throws InvalidArgumentException If struct field values don't match field types
     */
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

    /**
     * Converts an integer to a contract enum value
     *
     * @internal This is an internal helper method used by nativeToUdt()
     *
     * @param int $val The enum case value
     * @param XdrSCSpecUDTEnumV0 $enum The enum specification
     * @return XdrSCVal The converted enum value as U32
     * @throws InvalidArgumentException If the value is not a valid enum case
     */
    private function nativeToEnum(int $val, XdrSCSpecUDTEnumV0 $enum) : XdrSCVal {
        foreach ($enum->cases as $case) {
            if ($case->value === $val) {
                return XdrSCVal::forU32($val);
            }
        }
        throw new InvalidArgumentException("no such enum entry: $val in $enum->name");
    }

    /**
     * Converts a PHP array to a contract struct
     *
     * Handles both tuple-style structs (numeric field names) and map-style structs (named fields).
     *
     * @internal This is an internal helper method used by nativeToUdt()
     *
     * @param mixed $val The PHP array value
     * @param XdrSCSpecUDTStructV0 $struct The struct specification
     * @return XdrSCVal The converted struct as a Vec or Map
     * @throws InvalidArgumentException If the value is not an array or fields don't match
     * @throws InvalidArgumentException If field count doesn't match struct definition
     */
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

    /**
     * Converts a NativeUnionVal to a contract union
     *
     * Unions are represented as vectors with the first element being the case name symbol.
     *
     * @internal This is an internal helper method used by nativeToUdt()
     *
     * @param NativeUnionVal $val The union value with tag and optional values
     * @param XdrSCSpecUDTUnionV0 $union The union specification
     * @return XdrSCVal The converted union as a Vec
     * @throws InvalidArgumentException If the case is not found or values don't match
     * @throws InvalidArgumentException If the value count doesn't match the case type definition
     */
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