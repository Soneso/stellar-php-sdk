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
 * Parses a soroban contract byte code to get Environment Meta, Contract Spec and Contract Meta.
 * see: https://developers.stellar.org/docs/tools/sdks/build-your-own
 */
class SorobanContractParser
{

    /**
     * Parses a soroban contract bytecode to get Environment Meta, Contract Spec and Contract Meta.
     * see: https://developers.stellar.org/docs/tools/sdks/build-your-own
     * Returns SorobanContractInfo containing the parsed data.
     *
     * @param string $byteCode the byte code of the contract
     * @return SorobanContractInfo return object containing the parsed contract data
     * @throws SorobanContractParserException if any exception occurred during the byte code parsing. E.g. invalid byte code.
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
     * Parses the Environment Meta from the given byte code
     * @param string $byteCode the contract byte code to parse the data from.
     * @return XdrSCEnvMetaEntry|null Environment Meta as XDR if found.
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
     * Parses the Contract Spec Entries from the given byte code. There is a XdrSCSpecEntry for every function,
     * struct, and union exported by the contract.
     * @param string $byteCode the contract byte code to parse the data from.
     * @return array<XdrSCSpecEntry>|null The array of parsed Contract Spec Entries (XdrSCSpecEntry) if found.
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
                    $entry->type->value === XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_ERROR_ENUM_V0) {

                    array_push($result, $entry);
                    $entryBytes = $entry->encode();
                    if (substr($specBytes, 0, strlen($entryBytes)) === $entryBytes) {
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
                if (substr($metaEntryBytes, 0, strlen($entryBytes)) === $entryBytes) {
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