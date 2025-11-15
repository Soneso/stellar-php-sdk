<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban;

use Soneso\StellarSDK\Soroban\Exceptions\SorobanContractParserException;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrSCEnvMetaEntry;
use Soneso\StellarSDK\Xdr\XdrSCMetaEntry;
use Soneso\StellarSDK\Xdr\XdrSCSpecEntry;
use Soneso\StellarSDK\Xdr\XdrSCSpecEntryKind;
use Throwable;

/**
 * Parser for extracting metadata from Soroban contract WASM bytecode
 *
 * This utility class parses compiled Soroban contract WASM bytecode to extract embedded metadata
 * following the custom sections format defined in SEP-47 and SEP-48. The parser extracts:
 * - Environment Meta: Soroban interface version requirements
 * - Contract Spec: Function signatures, types, and events (SEP-48)
 * - Contract Meta: Custom metadata key-value pairs (SEP-47)
 *
 * The extracted metadata enables type-safe contract interaction and introspection without
 * executing the contract code.
 *
 * @package Soneso\StellarSDK\Soroban
 * @see https://developers.stellar.org/docs/tools/sdks/build-your-own SDK Building Guide
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0047.md SEP-47: Contract Metadata
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0048.md SEP-48: Contract Spec
 * @since 1.0.0
 */
class SorobanContractParser
{

    /**
     * Parses contract WASM bytecode to extract all metadata
     *
     * Extracts environment meta, contract spec entries, and custom metadata from the
     * contract's WASM bytecode custom sections. All metadata must be present for successful parsing.
     *
     * @param string $byteCode The compiled WASM bytecode of the contract
     * @return SorobanContractInfo Container object with all parsed metadata
     * @throws SorobanContractParserException If bytecode is invalid or required metadata is missing
     */
    public static function parseContractByteCode (string $byteCode): SorobanContractInfo {
        $xdrEnvMeta = self::parseEnvironmentMeta($byteCode);
        if ($xdrEnvMeta === null || $xdrEnvMeta->interfaceVersion === null) {
            throw new SorobanContractParserException(message: "invalid byte code: environment meta not found.");
        }
        $specEntries = self::parseContractSpec($byteCode);
        if($specEntries === null) {
            throw new SorobanContractParserException(message: "invalid byte code: spec entries not found.");
        }
        /**
         * @var array<string, string> $metaEntries
         */
        $metaEntries = self::parseMeta($byteCode);

        return new SorobanContractInfo(
            envInterfaceVersion: $xdrEnvMeta->interfaceVersion,
            specEntries: $specEntries,
            metaEntries: $metaEntries,
        );
    }

    /**
     * Extracts environment metadata from WASM bytecode
     *
     * Parses the contractenvmetav0 custom section to extract the Soroban interface
     * version requirements.
     *
     * @param string $byteCode The contract WASM bytecode
     * @return XdrSCEnvMetaEntry|null The environment meta if found, null otherwise
     */
    private static function parseEnvironmentMeta (string $byteCode) : ?XdrSCEnvMetaEntry {
        /**
         * @var ?string $metaEnvEntryBytes
         */
        $metaEnvEntryBytes = null;

        if (preg_match('/contractenvmetav0(.*?)contractmetav0/', $byteCode, $match) == 1) {
            $metaEnvEntryBytes = $match[1];
        } else if (preg_match('/contractenvmetav0(.*?)contractspecv0/', $byteCode, $match) == 1) {
            $metaEnvEntryBytes = $match[1];
        } else {
            $startPos = strpos($byteCode, 'contractenvmetav0');
            if ($startPos !== false) {
                $metaEnvEntryBytes = substr($byteCode, $startPos + strlen('contractenvmetav0'));
            }
        }
        try {
            return $metaEnvEntryBytes === null ? null : XdrSCEnvMetaEntry::decode(new XdrBuffer($metaEnvEntryBytes));
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Extracts contract specification entries from WASM bytecode
     *
     * Parses the contractspecv0 custom section to extract function signatures, user-defined types,
     * and event definitions. Each exported function, struct, union, enum, and event has a corresponding spec entry.
     *
     * @param string $byteCode The contract WASM bytecode
     * @return array<XdrSCSpecEntry>|null Array of spec entries if found, null otherwise
     */
    private static function parseContractSpec (string $byteCode) : ?array {
        /**
         * @var ?string $specBytes
         */
        $specBytes = null;

        if (preg_match('/contractspecv0(.*?)contractenvmetav0/', $byteCode, $match) == 1) {
            $specBytes = $match[1];
        } else if (preg_match('/contractspecv0(.*?)contractmetav0/', $byteCode, $match) == 1) {
            $specBytes = $match[1];
        } else {
            $startPos = strpos($byteCode, 'contractspecv0');
            if ($startPos !== false) {
                $specBytes = substr($byteCode, $startPos + strlen('contractspecv0'));
            }
        }

        if ($specBytes === null) {
            return null;
        }
        /**
         * @var array<XdrSCSpecEntry> $result
         */
        $result = [];

        while(strlen($specBytes) > 0) {
            try {
                $entry = XdrSCSpecEntry::decode(new XdrBuffer($specBytes));
                if (!isset($entry->type) || !isset($entry->type->value)) {
                    break;
                }
                if ($entry->type->value === XdrSCSpecEntryKind::SC_SPEC_ENTRY_FUNCTION_V0 ||
                    $entry->type->value === XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_STRUCT_V0 ||
                    $entry->type->value === XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_UNION_V0 ||
                    $entry->type->value === XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_ENUM_V0 ||
                    $entry->type->value === XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_ERROR_ENUM_V0 ||
                    $entry->type->value === XdrSCSpecEntryKind::SC_SPEC_ENTRY_EVENT_V0) {

                    array_push($result, $entry);
                    $entryBytes = $entry->encode();
                    if (str_starts_with($specBytes, $entryBytes)) {
                        $specBytes = substr($specBytes, strlen($entryBytes));
                    } else {
                        break;
                    }

                } else {
                    break;
                }
            } catch (Throwable) {
                break;
            }
        }

        return count($result) > 0 ? $result : null;
    }

    /**
     * Parses the Contract Meta from the given contract byte code. Returns an array of found key => value pairs.
     * Contracts may store any metadata in the entries that can be used by applications and tooling off-network.
     * @param string $byteCode the contract byte code to parse the data from.
     * @return array<string,string> array of found Contract Meta key => value pairs.
     */
    private static function parseMeta(string $byteCode) : array {
        /**
         * @var ?string $metaEntryBytes
         */
        $metaEntryBytes = null;

        if (preg_match('/contractmetav0(.*?)contractenvmetav0/', $byteCode, $match) == 1) {
            $metaEntryBytes = $match[1];
        } else if (preg_match('/contractmetav0(.*?)contractspecv0/', $byteCode, $match) == 1) {
            $metaEntryBytes = $match[1];
        } else {
            $startPos = strpos($byteCode, 'contractmetav0');
            if ($startPos !== false) {
                $metaEntryBytes = substr($byteCode, $startPos + strlen('contractmetav0'));
            }
        }
        /**
         * @var array<string,string> $result
         */
        $result = [];

        if ($metaEntryBytes === null) {
            return $result;
        }

        while(strlen($metaEntryBytes) > 0) {
            try {
                $entry = XdrSCMetaEntry::decode(new XdrBuffer($metaEntryBytes));
                if (!isset($entry->v0)) {
                    break;
                }
                $result[$entry->v0->key] = $entry->v0->value;
                $entryBytes = $entry->encode();
                if (str_starts_with($metaEntryBytes, $entryBytes)) {
                    $metaEntryBytes = substr($metaEntryBytes, strlen($entryBytes));
                } else {
                    break;
                }
            } catch (Throwable) {
                break;
            }
        }
        return $result;
    }
}