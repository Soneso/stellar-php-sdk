<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban;

use Soneso\StellarSDK\Xdr\XdrSCSpecEntry;
use Soneso\StellarSDK\Xdr\XdrSCSpecEntryKind;
use Soneso\StellarSDK\Xdr\XdrSCSpecFunctionV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTStructV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTUnionV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTEnumV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecUDTErrorEnumV0;
use Soneso\StellarSDK\Xdr\XdrSCSpecEventV0;

/**
 * Stores information parsed from a soroban contract byte code such as
 * Environment Meta, Contract Spec Entries and Contract Meta Entries.
 * See also: https://developers.stellar.org/docs/tools/sdks/build-your-own
 */
class SorobanContractInfo
{
    /**
     * @var int Environment interface number from Environment Meta.
     */
    public int $envInterfaceVersion;

    /**
     * @var array<XdrSCSpecEntry> $specEntries Contract Spec Entries.
     * There is a SCSpecEntry for every function, struct, and union exported by the contract.
     */
    public array $specEntries;

    /**
     * @var array<string,string> $metaEntries Contract Meta Entries. Key => Value pairs.
     * Contracts may store any metadata in the entries that can be used by applications
     * and tooling off-network.
     */
    public array $metaEntries;

    /**
     * @var array<string> $supportedSeps List of SEP numbers that this contract claims to support.
     * Extracted from meta entries with key "sep" as defined in SEP-47.
     * SEP-47: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0047.md
     */
    public array $supportedSeps;

    /**
     * @var array<XdrSCSpecFunctionV0> $funcs Contract functions extracted from spec entries.
     * Contains all function specifications exported by the contract.
     */
    public array $funcs;

    /**
     * @var array<XdrSCSpecUDTStructV0> $udtStructs User-defined type structs extracted from spec entries.
     * Contains all UDT struct specifications exported by the contract.
     */
    public array $udtStructs;

    /**
     * @var array<XdrSCSpecUDTUnionV0> $udtUnions User-defined type unions extracted from spec entries.
     * Contains all UDT union specifications exported by the contract.
     */
    public array $udtUnions;

    /**
     * @var array<XdrSCSpecUDTEnumV0> $udtEnums User-defined type enums extracted from spec entries.
     * Contains all UDT enum specifications exported by the contract.
     */
    public array $udtEnums;

    /**
     * @var array<XdrSCSpecUDTErrorEnumV0> $udtErrorEnums User-defined type error enums extracted from spec entries.
     * Contains all UDT error enum specifications exported by the contract.
     */
    public array $udtErrorEnums;

    /**
     * @var array<XdrSCSpecEventV0> $events Event specifications extracted from spec entries.
     * Contains all event specifications exported by the contract.
     */
    public array $events;

    /**
     * @param int $envInterfaceVersion Environment interface number from Environment Meta.
     * @param array<XdrSCSpecEntry> $specEntries Contract Spec Entries.
     * @param array<string,string> $metaEntries Contract Meta Entries. Key => Value pairs.
     */
    public function __construct(
        int $envInterfaceVersion,
        array $specEntries,
        array $metaEntries,
    )
    {
        $this->envInterfaceVersion = $envInterfaceVersion;
        $this->specEntries = $specEntries;
        $this->metaEntries = $metaEntries;

        // Parse supported SEPs from meta entries (SEP-47)
        // Meta entries with key "sep" contain comma-separated SEP numbers
        $this->supportedSeps = $this->parseSupportedSeps($metaEntries);

        // Extract functions from spec entries
        $this->funcs = $this->extractFunctions($specEntries);

        // Extract UDT structs from spec entries
        $this->udtStructs = $this->extractUdtStructs($specEntries);

        // Extract UDT unions from spec entries
        $this->udtUnions = $this->extractUdtUnions($specEntries);

        // Extract UDT enums from spec entries
        $this->udtEnums = $this->extractUdtEnums($specEntries);

        // Extract UDT error enums from spec entries
        $this->udtErrorEnums = $this->extractUdtErrorEnums($specEntries);

        // Extract events from spec entries
        $this->events = $this->extractEvents($specEntries);
    }

    /**
     * Extract function specifications from spec entries.
     * Iterates through all spec entries and collects those that define functions.
     *
     * @param array<XdrSCSpecEntry> $specEntries Contract Spec Entries
     * @return array<XdrSCSpecFunctionV0> Array of function specifications
     */
    private function extractFunctions(array $specEntries): array
    {
        /**
         * @var array<XdrSCSpecFunctionV0> $result
         */
        $result = [];

        foreach ($specEntries as $entry) {
            if ($entry->type->value === XdrSCSpecEntryKind::SC_SPEC_ENTRY_FUNCTION_V0 && $entry->functionV0 !== null) {
                $result[] = $entry->functionV0;
            }
        }

        return $result;
    }

    /**
     * Extract UDT struct specifications from spec entries.
     * Iterates through all spec entries and collects those that define user-defined type structs.
     *
     * @param array<XdrSCSpecEntry> $specEntries Contract Spec Entries
     * @return array<XdrSCSpecUDTStructV0> Array of UDT struct specifications
     */
    private function extractUdtStructs(array $specEntries): array
    {
        /**
         * @var array<XdrSCSpecUDTStructV0> $result
         */
        $result = [];

        foreach ($specEntries as $entry) {
            if ($entry->type->value === XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_STRUCT_V0 && $entry->udtStructV0 !== null) {
                $result[] = $entry->udtStructV0;
            }
        }

        return $result;
    }

    /**
     * Extract UDT union specifications from spec entries.
     * Iterates through all spec entries and collects those that define user-defined type unions.
     *
     * @param array<XdrSCSpecEntry> $specEntries Contract Spec Entries
     * @return array<XdrSCSpecUDTUnionV0> Array of UDT union specifications
     */
    private function extractUdtUnions(array $specEntries): array
    {
        /**
         * @var array<XdrSCSpecUDTUnionV0> $result
         */
        $result = [];

        foreach ($specEntries as $entry) {
            if ($entry->type->value === XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_UNION_V0 && $entry->udtUnionV0 !== null) {
                $result[] = $entry->udtUnionV0;
            }
        }

        return $result;
    }

    /**
     * Extract UDT enum specifications from spec entries.
     * Iterates through all spec entries and collects those that define user-defined type enums.
     *
     * @param array<XdrSCSpecEntry> $specEntries Contract Spec Entries
     * @return array<XdrSCSpecUDTEnumV0> Array of UDT enum specifications
     */
    private function extractUdtEnums(array $specEntries): array
    {
        /**
         * @var array<XdrSCSpecUDTEnumV0> $result
         */
        $result = [];

        foreach ($specEntries as $entry) {
            if ($entry->type->value === XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_ENUM_V0 && $entry->udtEnumV0 !== null) {
                $result[] = $entry->udtEnumV0;
            }
        }

        return $result;
    }

    /**
     * Extract UDT error enum specifications from spec entries.
     * Iterates through all spec entries and collects those that define user-defined type error enums.
     *
     * @param array<XdrSCSpecEntry> $specEntries Contract Spec Entries
     * @return array<XdrSCSpecUDTErrorEnumV0> Array of UDT error enum specifications
     */
    private function extractUdtErrorEnums(array $specEntries): array
    {
        /**
         * @var array<XdrSCSpecUDTErrorEnumV0> $result
         */
        $result = [];

        foreach ($specEntries as $entry) {
            if ($entry->type->value === XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_ERROR_ENUM_V0 && $entry->udtErrorEnumV0 !== null) {
                $result[] = $entry->udtErrorEnumV0;
            }
        }

        return $result;
    }

    /**
     * Extract event specifications from spec entries.
     * Iterates through all spec entries and collects those that define events.
     *
     * @param array<XdrSCSpecEntry> $specEntries Contract Spec Entries
     * @return array<XdrSCSpecEventV0> Array of event specifications
     */
    private function extractEvents(array $specEntries): array
    {
        /**
         * @var array<XdrSCSpecEventV0> $result
         */
        $result = [];

        foreach ($specEntries as $entry) {
            if ($entry->type->value === XdrSCSpecEntryKind::SC_SPEC_ENTRY_EVENT_V0 && $entry->eventV0 !== null) {
                $result[] = $entry->eventV0;
            }
        }

        return $result;
    }

    /**
     * Parse supported SEP numbers from meta entries.
     * According to SEP-47, contracts can indicate which SEPs they support
     * through meta entries with key "sep" containing comma-separated SEP numbers.
     * Multiple "sep" entries are concatenated.
     *
     * @param array<string,string> $metaEntries Contract Meta Entries
     * @return array<string> List of SEP numbers (e.g., ["41", "40"])
     */
    private function parseSupportedSeps(array $metaEntries): array
    {
        $sepNumbers = [];

        // Look for all meta entries with key "sep"
        foreach ($metaEntries as $key => $value) {
            if ($key === 'sep') {
                // Parse comma-separated SEP numbers
                $seps = array_map('trim', explode(',', $value));
                foreach ($seps as $sep) {
                    if (!empty($sep) && !in_array($sep, $sepNumbers)) {
                        $sepNumbers[] = $sep;
                    }
                }
            }
        }

        return $sepNumbers;
    }


}