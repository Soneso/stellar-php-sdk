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
use Throwable;

/**
 * Parser for extracting metadata from Soroban contract WASM bytecode
 *
 * This utility class parses compiled Soroban contract WASM bytecode to extract embedded metadata
 * following the custom sections format defined in SEP-46 and SEP-48. The parser extracts:
 * - Environment Meta: Soroban environment protocol and pre-release version
 * - Contract Spec: Function signatures, types, and events (SEP-48)
 * - Contract Meta: Custom metadata key-value pairs (SEP-46)
 *
 * The extracted metadata enables type-safe contract interaction and introspection without
 * executing the contract code.
 *
 * @package Soneso\StellarSDK\Soroban
 * @see https://developers.stellar.org/docs/tools/sdks/build-your-own SDK Building Guide
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0046.md SEP-46: Contract Meta
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0048.md SEP-48: Contract Interface Specification
 * @since 1.0.0
 */
class SorobanContractParser
{
    private const CUSTOM_SECTION_ID = 0;
    private const ENV_META_SECTION_NAME = 'contractenvmetav0';
    private const SPEC_SECTION_NAME = 'contractspecv0';
    private const META_SECTION_NAME = 'contractmetav0';

    /**
     * Parses contract WASM bytecode to extract all metadata.
     *
     * Uses the first environment meta and spec sections, and every meta section in module
     * order. The spec section may be empty; the environment meta must contain an entry.
     *
     * @param string $byteCode The compiled WASM bytecode of the contract
     * @return SorobanContractInfo Container object with all parsed metadata
     * @throws SorobanContractParserException If framing or an entry is invalid, or a required
     * section is missing. Entry errors name the section and the entry's module byte offset.
     */
    public static function parseContractByteCode (string $byteCode): SorobanContractInfo {
        $sections = self::readContractSections($byteCode);
        $envEntries = isset($sections[self::ENV_META_SECTION_NAME])
            ? self::decodeEntries($byteCode, self::ENV_META_SECTION_NAME, $sections[self::ENV_META_SECTION_NAME][0], [XdrSCEnvMetaEntry::class, 'decode']) : [];
        $iv = $envEntries[0]->interfaceVersion ?? null;
        if ($iv === null) {
            throw new SorobanContractParserException(message: "invalid byte code: environment meta not found.");
        }
        if (!isset($sections[self::SPEC_SECTION_NAME])) {
            throw new SorobanContractParserException(message: "invalid byte code: spec entries not found.");
        }
        $specEntries = self::decodeEntries($byteCode, self::SPEC_SECTION_NAME, $sections[self::SPEC_SECTION_NAME][0], [XdrSCSpecEntry::class, 'decode']);
        return new SorobanContractInfo(
            envMetaProtocol: $iv->protocol,
            envMetaPreRelease: $iv->preRelease,
            specEntries: $specEntries,
            metaEntries: self::parseMeta($byteCode, $sections[self::META_SECTION_NAME] ?? []),
        );
    }

    /**
     * Reads every meta section in module order. Repeated sep values are joined with a
     * comma as SEP-47 defines; a later value replaces any other repeated key.
     *
     * @param string $byteCode The contract WASM bytecode
     * @param list<array{start: int, length: int}> $sections Meta content bounds
     * @return array<string, string> Contract metadata
     */
    private static function parseMeta(string $byteCode, array $sections): array {
        $result = [];
        foreach ($sections as $section) {
            $entries = self::decodeEntries($byteCode, self::META_SECTION_NAME, $section, [XdrSCMetaEntry::class, 'decode']);
            foreach ($entries as $entry) {
                $key = $entry->v0->key;
                $value = $entry->v0->value;
                $result[$key] = $key === 'sep' && isset($result[$key]) ? $result[$key] . ',' . $value : $value;
            }
        }
        return $result;
    }

    /**
     * Walks all wasm sections, checking payload and custom name bounds. Only the content
     * bounds of contract sections are retained; other payloads are skipped by size.
     *
     * @param string $byteCode The contract WASM bytecode
     * @return array<string, list<array{start: int, length: int}>> Bounds in module order
     * @throws SorobanContractParserException If the header or section framing is invalid
     */
    private static function readContractSections(string $byteCode): array {
        $length = strlen($byteCode);
        // The wasm header holds a four-byte magic and a four-byte version.
        if ($length < 8) {
            throw new SorobanContractParserException(message:
                "invalid byte code: the " . self::formatByteCount($length) . " at byte offset 0 "
                . ($length === 1 ? 'is' : 'are') . " shorter than the 8-byte wasm header.");
        }
        foreach ([0 => ['magic', '0061736d'], 4 => ['version', '01000000']] as $offset => [$field, $expected]) {
            $actual = bin2hex(substr($byteCode, $offset, 4));
            if ($actual !== $expected) {
                throw new SorobanContractParserException(message:
                    "invalid byte code: the wasm $field at byte offset $offset is $actual, expected $expected.");
            }
        }
        $sections = [];
        $offset = 8;
        while ($offset < $length) {
            $sectionStart = $offset;
            $id = ord($byteCode[$offset++]);
            $size = self::readVarUint32($byteCode, $offset, $length, "size of the section at byte offset $sectionStart (id $id)");
            $remaining = $length - $offset;
            if ($size > $remaining) {
                throw new SorobanContractParserException(message:
                    "invalid byte code: the section with id $id at byte offset $sectionStart declares "
                    . self::formatByteCount($size) . ", but " . self::formatByteCount($remaining)
                    . ($remaining === 1 ? ' remains.' : ' remain.'));
            }
            $end = $offset + $size;
            if ($id === self::CUSTOM_SECTION_ID) {
                $nameLength = self::readVarUint32($byteCode, $offset, $end, "name length of the custom section at byte offset $sectionStart");
                $remaining = $end - $offset;
                if ($nameLength > $remaining) {
                    throw new SorobanContractParserException(message:
                        "invalid byte code: the custom section at byte offset $sectionStart declares a $nameLength-byte name, but "
                        . self::formatByteCount($remaining) . " of the section"
                        . ($remaining === 1 ? ' remains.' : ' remain.'));
                }
                $name = substr($byteCode, $offset, $nameLength);
                $offset += $nameLength;
                if (in_array($name, [self::ENV_META_SECTION_NAME, self::SPEC_SECTION_NAME, self::META_SECTION_NAME], true)) {
                    $sections[$name][] = ['start' => $offset, 'length' => $end - $offset];
                }
            }
            $offset = $end;
        }
        return $sections;
    }

    /**
     * Formats a byte count for framing error messages with the matching unit.
     *
     * @param int $count Number of bytes
     * @return string The count followed by byte or bytes
     */
    private static function formatByteCount(int $count): string {
        if ($count === 1) {
            return '1 byte';
        }
        return "$count bytes";
    }

    /**
     * Reads a wasm unsigned LEB128 u32, accepting padded encodings of at most five bytes.
     *
     * @param string $bytes Module bytes
     * @param int $offset Field start, advanced past the field on success
     * @param int $limit Exclusive bound for this field
     * @param string $field Field description and enclosing section offset
     * @return int The decoded value
     * @throws SorobanContractParserException If the field is cut off or exceeds u32
     */
    private static function readVarUint32(string $bytes, int &$offset, int $limit, string $field): int {
        $start = $offset;
        $value = 0;
        for ($shift = 0; ; $shift += 7) {
            if ($offset >= $limit) {
                throw new SorobanContractParserException(message:
                    "invalid byte code: the $field starting at byte offset $start ends at byte offset $limit before its last byte.");
            }
            $byte = ord($bytes[$offset++]);
            // The fifth byte holds bits 28 to 31 and must be the last.
            if ($shift === 28 && $byte > 0x0f) {
                $problem = ($byte & 0x80) !== 0 ? 'is longer than 5 bytes' : 'exceeds the u32 range';
                throw new SorobanContractParserException(message:
                    "invalid byte code: the $field starting at byte offset $start $problem.");
            }
            $value |= ($byte & 0x7f) << $shift;
            if (($byte & 0x80) === 0) {
                return $value;
            }
        }
    }

    /**
     * Decodes an entire section as a stream of XDR entries using buffer positions.
     *
     * @template T
     * @param string $byteCode Module bytes
     * @param string $name Custom section name
     * @param array{start: int, length: int} $section Content bounds
     * @param callable(XdrBuffer): T $decode Entry decoder
     * @return list<T> Decoded entries
     * @throws SorobanContractParserException If any entry does not decode
     */
    private static function decodeEntries(string $byteCode, string $name, array $section, callable $decode): array {
        $buffer = new XdrBuffer(substr($byteCode, $section['start'], $section['length']));
        $entries = [];
        while ($buffer->getPosition() < $section['length']) {
            $offset = $section['start'] + $buffer->getPosition();
            try {
                $entries[] = $decode($buffer);
            } catch (Throwable $e) {
                throw new SorobanContractParserException(message:
                    "invalid byte code: the $name entry at byte offset $offset does not decode: " . $e->getMessage(),
                    previous: $e);
            }
        }
        return $entries;
    }
}