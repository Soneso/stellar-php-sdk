<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use Exception;
use InvalidArgumentException;
use phpseclib3\Math\BigInteger;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\MuxedAccount;

/**
 * Shared utility functions for TxRep (SEP-0011) encoding and decoding.
 *
 * Provides consistent parsing, formatting, and Stellar type conversion used by
 * both generated and hand-written TxRep serialization code.
 *
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0011.md
 */
class TxRepHelper
{
    // ---------------------------------------------------------------------------
    // Parser utilities
    // ---------------------------------------------------------------------------

    /**
     * Parse TxRep text into a key-value map.
     *
     * Normalizes CRLF line endings, skips blank lines and comment-only lines
     * (lines whose first non-whitespace character is `:`), splits on the first
     * `:` only, and trims both key and value whitespace.
     *
     * @param string $txRep Raw TxRep text.
     * @return array<string, string> Ordered map of key => raw value strings.
     */
    public static function parse(string $txRep): array
    {
        $map = [];
        // Normalize CRLF to LF.
        $normalized = str_replace("\r\n", "\n", $txRep);
        $lines = explode("\n", $normalized);

        foreach ($lines as $line) {
            // Skip blank lines.
            if (trim($line) === '') {
                continue;
            }
            // Skip comment-only lines (leading colon with no key).
            if (ltrim($line)[0] === ':') {
                continue;
            }

            $colonPos = strpos($line, ':');
            if ($colonPos === false) {
                continue; // No colon — skip.
            }

            $key = trim(substr($line, 0, $colonPos));
            if ($key === '') {
                continue;
            }

            $value = trim(substr($line, $colonPos + 1));
            $map[$key] = $value;
        }

        return $map;
    }

    /**
     * Get a value from a parsed TxRep map, stripping inline comments.
     *
     * @param array<string, string> $map  Map produced by {@see self::parse()}.
     * @param string                $key  Key to look up.
     * @return string|null The cleaned value, or null if the key is not present.
     */
    public static function getValue(array $map, string $key): ?string
    {
        if (!array_key_exists($key, $map)) {
            return null;
        }
        return self::removeComment($map[$key]);
    }

    /**
     * Remove an inline comment from a TxRep value string.
     *
     * If the value starts with a double-quote, the closing quote is located
     * (respecting `\` escapes) and everything after it is discarded. For
     * unquoted values the first `(` marks the start of a comment.
     *
     * @param string $value Raw value string (possibly with trailing comment).
     * @return string The value with any inline comment removed and trimmed.
     */
    public static function removeComment(string $value): string
    {
        if (isset($value[0]) && $value[0] === '"') {
            $i = 1;
            $len = strlen($value);
            while ($i < $len) {
                if ($value[$i] === '\\') {
                    $i += 2; // Skip escaped character.
                    continue;
                }
                if ($value[$i] === '"') {
                    // Found closing quote — return everything up to and including it.
                    return substr($value, 0, $i + 1);
                }
                $i++;
            }
            // No closing quote found — return as-is.
            return $value;
        }

        // Not a quoted string — look for `(` as comment start.
        $idx = strpos($value, '(');
        if ($idx === false) {
            return trim($value);
        }

        return trim(substr($value, 0, $idx));
    }

    // ---------------------------------------------------------------------------
    // Formatting utilities
    // ---------------------------------------------------------------------------

    /**
     * Encode a binary string as a lowercase hex string.
     *
     * Returns `"0"` for empty input (SEP-0011 convention for empty byte arrays).
     *
     * @param string $bytes Raw binary string.
     * @return string Lowercase hex representation, or `"0"` for empty input.
     */
    public static function bytesToHex(string $bytes): string
    {
        if ($bytes === '') {
            return '0';
        }

        return bin2hex($bytes);
    }

    /**
     * Decode a hex string to a raw binary string.
     *
     * The special value `"0"` decodes to an empty string. Odd-length hex
     * strings are left-padded with a leading zero before decoding.
     *
     * @param string $hex Lowercase or uppercase hex string, or `"0"`.
     * @return string Raw binary string.
     * @throws InvalidArgumentException If the hex string contains invalid characters.
     */
    public static function hexToBytes(string $hex): string
    {
        if ($hex === '0') {
            return '';
        }

        $h = $hex;
        if (strlen($h) % 2 !== 0) {
            $h = '0' . $h;
        }

        if (!ctype_xdigit($h)) {
            throw new InvalidArgumentException('Invalid hex string: ' . $hex);
        }

        return (string)hex2bin($h);
    }

    /**
     * Escape a string for TxRep double-quoted format.
     *
     * Wraps the result in double quotes. Escape rules:
     * - `\` becomes `\\`
     * - `"` becomes `\"`
     * - newline (0x0A) becomes `\n`
     * - carriage return (0x0D) becomes `\r`
     * - tab (0x09) becomes `\t`
     * - printable ASCII (0x20–0x7E) is passed through unchanged
     * - all other bytes are encoded as `\xNN` (two hex digits, lowercase)
     *
     * @param string $s The raw UTF-8 string to escape.
     * @return string The escaped string wrapped in double quotes.
     */
    public static function escapeString(string $s): string
    {
        $out = '"';
        $len = strlen($s);

        for ($i = 0; $i < $len; $i++) {
            $byte = ord($s[$i]);

            if ($byte === 0x5C) {
                // backslash
                $out .= '\\\\';
            } elseif ($byte === 0x22) {
                // double quote
                $out .= '\\"';
            } elseif ($byte === 0x0A) {
                // newline
                $out .= '\\n';
            } elseif ($byte === 0x0D) {
                // carriage return
                $out .= '\\r';
            } elseif ($byte === 0x09) {
                // tab
                $out .= '\\t';
            } elseif ($byte >= 0x20 && $byte <= 0x7E) {
                // printable ASCII
                $out .= $s[$i];
            } else {
                // Non-printable or non-ASCII byte.
                $out .= '\\x' . str_pad(dechex($byte), 2, '0', STR_PAD_LEFT);
            }
        }

        $out .= '"';

        return $out;
    }

    /**
     * Unescape a TxRep string value.
     *
     * Strips surrounding double quotes if present, then processes escape
     * sequences: `\"`, `\\`, `\n`, `\r`, `\t`, and `\xNN` (two hex digits).
     *
     * @param string $s The raw TxRep string value (optionally quoted).
     * @return string The unescaped string.
     */
    public static function unescapeString(string $s): string
    {
        $input = $s;
        if (strlen($input) >= 2 && $input[0] === '"' && $input[strlen($input) - 1] === '"') {
            $input = substr($input, 1, strlen($input) - 2);
        }

        $out = '';
        $len = strlen($input);
        $pendingBytes = [];

        $flushPending = static function () use (&$pendingBytes, &$out): void {
            if ($pendingBytes !== []) {
                foreach ($pendingBytes as $b) {
                    $out .= chr($b);
                }
                $pendingBytes = [];
            }
        };

        $i = 0;
        while ($i < $len) {
            if ($input[$i] === '\\' && $i + 1 < $len) {
                $next = $input[$i + 1];

                if ($next === '"') {
                    $flushPending();
                    $out .= '"';
                    $i += 2;
                } elseif ($next === '\\') {
                    $flushPending();
                    $out .= '\\';
                    $i += 2;
                } elseif ($next === 'n') {
                    $flushPending();
                    $out .= "\n";
                    $i += 2;
                } elseif ($next === 'r') {
                    $flushPending();
                    $out .= "\r";
                    $i += 2;
                } elseif ($next === 't') {
                    $flushPending();
                    $out .= "\t";
                    $i += 2;
                } elseif ($next === 'x' && $i + 3 < $len) {
                    $hexStr = substr($input, $i + 2, 2);
                    $byteVal = hexdec($hexStr);
                    // Validate that hexStr is actually valid hex (hexdec silently
                    // accepts partial strings, so re-check by re-encoding).
                    if (strtolower($hexStr) === str_pad(dechex($byteVal), 2, '0', STR_PAD_LEFT)) {
                        $pendingBytes[] = $byteVal;
                        $i += 4;
                    } else {
                        $flushPending();
                        $out .= $input[$i];
                        $i++;
                    }
                } else {
                    $flushPending();
                    $out .= $input[$i];
                    $i++;
                }
            } else {
                $flushPending();
                $out .= $input[$i];
                $i++;
            }
        }

        $flushPending();

        return $out;
    }

    /**
     * Parse an integer string, supporting decimal and `0x`/`0X` hex notation.
     *
     * Handles an optional leading minus sign for negative values.
     *
     * @param string $s The integer string to parse.
     * @return int The parsed integer.
     * @throws InvalidArgumentException If the string cannot be parsed.
     */
    public static function parseInt(string $s): int
    {
        $trimmed = trim($s);
        $negative = false;

        if (isset($trimmed[0]) && $trimmed[0] === '-') {
            $negative = true;
            $trimmed = substr($trimmed, 1);
        }

        if (str_starts_with($trimmed, '0x') || str_starts_with($trimmed, '0X')) {
            $result = intval(substr($trimmed, 2), 16);
        } else {
            if (!is_numeric($trimmed) || strpos($trimmed, '.') !== false) {
                throw new InvalidArgumentException('Cannot parse integer: ' . $s);
            }
            $result = intval($trimmed, 10);
        }

        return $negative ? -$result : $result;
    }

    /**
     * Parse a big-integer string, supporting decimal and `0x`/`0X` hex notation.
     *
     * Handles an optional leading minus sign for negative values.
     *
     * @param string $s The integer string to parse.
     * @return BigInteger The parsed big integer.
     * @throws InvalidArgumentException If the string cannot be parsed.
     */
    public static function parseBigInt(string $s): BigInteger
    {
        $trimmed = trim($s);
        $negative = false;

        if (isset($trimmed[0]) && $trimmed[0] === '-') {
            $negative = true;
            $trimmed = substr($trimmed, 1);
        }

        if (str_starts_with($trimmed, '0x') || str_starts_with($trimmed, '0X')) {
            $hex = substr($trimmed, 2);
            $result = new BigInteger($hex, 16);
        } else {
            $result = new BigInteger($trimmed, 10);
        }

        if ($negative) {
            $result = $result->negate();
        }

        return $result;
    }

    // ---------------------------------------------------------------------------
    // Amount formatting (SEP-0011: decimal XLM, not stroops)
    // ---------------------------------------------------------------------------

    /**
     * Convert a stroop value to a decimal XLM string with exactly 7 decimal places.
     *
     * Example: `1000000000` → `"100.0000000"`
     *
     * @param int $stroops Amount in stroops (1 XLM = 10,000,000 stroops).
     * @return string Decimal string with 7 decimal places.
     */
    public static function formatAmount(int $stroops): string
    {
        $scale = 10000000;
        $negative = $stroops < 0;
        $abs = abs($stroops);

        $whole = intdiv($abs, $scale);
        $fraction = $abs % $scale;

        $result = ($negative ? '-' : '') . $whole . '.' . str_pad((string)$fraction, 7, '0', STR_PAD_LEFT);

        return $result;
    }

    /**
     * Convert a decimal XLM string to stroops.
     *
     * Example: `"100.0000000"` → `1000000000`
     *
     * The input may have fewer than 7 decimal places; missing places are treated
     * as zeros. Values with more than 7 decimal places are truncated.
     *
     * @param string $decimal Decimal amount string (e.g. `"100.5"`, `"0.0000001"`).
     * @return int Amount in stroops.
     * @throws InvalidArgumentException If the string cannot be parsed as a decimal.
     */
    public static function parseAmount(string $decimal): int
    {
        $decimal = trim($decimal);

        if (!is_numeric($decimal)) {
            throw new InvalidArgumentException('Cannot parse amount: ' . $decimal);
        }

        $parts = explode('.', $decimal);
        // Detect sign from the original string to handle "-0.NNNN" correctly.
        $sign = (isset($decimal[0]) && $decimal[0] === '-') ? -1 : 1;
        $whole = (int)$parts[0];
        $absWhole = abs($whole);

        $stroops = $absWhole * 10000000;

        if (isset($parts[1])) {
            // Pad or truncate to exactly 7 fractional digits.
            $frac = str_pad(substr($parts[1], 0, 7), 7, '0', STR_PAD_RIGHT);
            $stroops += (int)$frac;
        }

        return $sign * $stroops;
    }

    // ---------------------------------------------------------------------------
    // Stellar type formatters
    // ---------------------------------------------------------------------------

    /**
     * Convert an XdrAccountID to a StrKey account ID string (`G...`).
     *
     * @param XdrAccountID $id The XDR account ID.
     * @return string The StrKey-encoded account ID (G...).
     */
    public static function formatAccountId(XdrAccountID $id): string
    {
        return $id->getAccountId();
    }

    /**
     * Parse a StrKey account ID string (`G...`) to an XdrAccountID.
     *
     * @param string $strKey StrKey-encoded account ID (G...).
     * @return XdrAccountID The decoded XDR account ID.
     * @throws InvalidArgumentException If the string is not a valid account ID.
     */
    public static function parseAccountId(string $strKey): XdrAccountID
    {
        if (!StrKey::isValidAccountId($strKey)) {
            throw new InvalidArgumentException('Invalid account ID: ' . $strKey);
        }

        return XdrAccountID::fromAccountId($strKey);
    }

    /**
     * Convert an XdrMuxedAccount to a StrKey string.
     *
     * Returns a `G...` address for plain Ed25519 accounts or an `M...` address
     * for muxed (med25519) accounts.
     *
     * @param XdrMuxedAccount $mux The XDR muxed account.
     * @return string The StrKey-encoded account address.
     */
    public static function formatMuxedAccount(XdrMuxedAccount $mux): string
    {
        $ma = MuxedAccount::fromXdr($mux);

        return $ma->getAccountId();
    }

    /**
     * Parse a StrKey string to an XdrMuxedAccount.
     *
     * Accepts both `G...` (standard Ed25519) and `M...` (muxed ed25519) addresses.
     *
     * @param string $strKey StrKey-encoded account address (G... or M...).
     * @return XdrMuxedAccount The decoded XDR muxed account.
     * @throws InvalidArgumentException If the string is not a valid account address.
     */
    public static function parseMuxedAccount(string $strKey): XdrMuxedAccount
    {
        try {
            $ma = MuxedAccount::fromAccountId($strKey);
        } catch (Exception $e) {
            throw new InvalidArgumentException('Invalid muxed account: ' . $strKey, 0, $e);
        }

        return $ma->toXdr();
    }

    /**
     * Format an XdrAsset as a TxRep asset string.
     *
     * Returns `native` for XLM (ASSET_TYPE_NATIVE), and `CODE:ISSUER` for
     * credit assets (ASSET_TYPE_CREDIT_ALPHANUM4 / ASSET_TYPE_CREDIT_ALPHANUM12).
     *
     * @param XdrAsset $asset The XDR asset.
     * @return string The TxRep asset string.
     * @throws InvalidArgumentException For unsupported asset types.
     */
    public static function formatAsset(XdrAsset $asset): string
    {
        switch ($asset->getType()->getValue()) {
            case XdrAssetType::ASSET_TYPE_NATIVE:
                return 'XLM';

            case XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4:
                $a4 = $asset->getAlphaNum4();
                if ($a4 === null) {
                    throw new InvalidArgumentException('Missing alphaNum4 in asset');
                }
                return self::assetCodeFromString($a4->getAssetCode()) . ':' . $a4->getIssuer()->getAccountId();

            case XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12:
                $a12 = $asset->getAlphaNum12();
                if ($a12 === null) {
                    throw new InvalidArgumentException('Missing alphaNum12 in asset');
                }
                return self::assetCodeFromString($a12->getAssetCode()) . ':' . $a12->getIssuer()->getAccountId();

            default:
                throw new InvalidArgumentException(
                    'Unsupported asset type: ' . $asset->getType()->getValue()
                );
        }
    }

    /**
     * Parse a TxRep asset string (`native` or `CODE:ISSUER`) to an XdrAsset.
     *
     * Accepts both `native` and `XLM` as representations of the native asset.
     *
     * @param string $value TxRep asset string.
     * @return XdrAsset The decoded XDR asset.
     * @throws InvalidArgumentException If the asset string is invalid.
     */
    public static function parseAsset(string $value): XdrAsset
    {
        if ($value === 'native' || $value === 'XLM') {
            $asset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
            return $asset;
        }

        $parts = explode(':', $value, 2);
        if (count($parts) !== 2) {
            throw new InvalidArgumentException('Invalid asset: ' . $value);
        }

        $code = trim($parts[0]);
        $issuer = trim($parts[1]);

        if (!StrKey::isValidAccountId($issuer)) {
            throw new InvalidArgumentException('Invalid asset issuer: ' . $issuer);
        }

        $issuerId = XdrAccountID::fromAccountId($issuer);
        $codeLen = strlen($code);

        if ($codeLen === 0 || $codeLen > 12) {
            throw new InvalidArgumentException('Asset code length must be 1–12 characters: ' . $code);
        }

        if ($codeLen <= 4) {
            $asset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
            $asset->setAlphaNum4(new XdrAssetAlphaNum4($code, $issuerId));
            return $asset;
        }

        $asset = new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
        $asset->setAlphaNum12(new XdrAssetAlphaNum12($code, $issuerId));

        return $asset;
    }

    /**
     * Format an XdrChangeTrustAsset as a TxRep string.
     *
     * Returns `native` for XLM and `CODE:ISSUER` for credit assets.
     * Pool share assets cannot be represented as a single compact string and
     * throw an exception — callers must expand those field-by-field.
     *
     * @param XdrChangeTrustAsset $asset The XDR change-trust asset.
     * @return string The TxRep asset string.
     * @throws InvalidArgumentException For pool share or unsupported asset types.
     */
    public static function formatChangeTrustAsset(XdrChangeTrustAsset $asset): string
    {
        switch ($asset->getType()->getValue()) {
            case XdrAssetType::ASSET_TYPE_NATIVE:
                return 'XLM';

            case XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4:
                $a4 = $asset->getAlphaNum4();
                if ($a4 === null) {
                    throw new InvalidArgumentException('Missing alphaNum4 in change trust asset');
                }
                return self::assetCodeFromString($a4->getAssetCode()) . ':' . $a4->getIssuer()->getAccountId();

            case XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12:
                $a12 = $asset->getAlphaNum12();
                if ($a12 === null) {
                    throw new InvalidArgumentException('Missing alphaNum12 in change trust asset');
                }
                return self::assetCodeFromString($a12->getAssetCode()) . ':' . $a12->getIssuer()->getAccountId();

            case XdrAssetType::ASSET_TYPE_POOL_SHARE:
                throw new InvalidArgumentException(
                    'Pool share assets must be serialized field-by-field, not as a compact string'
                );

            default:
                throw new InvalidArgumentException(
                    'Unsupported change trust asset type: ' . $asset->getType()->getValue()
                );
        }
    }

    /**
     * Parse a TxRep string to an XdrChangeTrustAsset.
     *
     * Handles `native`, `XLM`, and `CODE:ISSUER` representations. Pool share
     * assets cannot be parsed from a compact string; use field-by-field parsing
     * in the caller.
     *
     * @param string $value TxRep asset string.
     * @return XdrChangeTrustAsset The decoded XDR change-trust asset.
     * @throws InvalidArgumentException If the asset string is invalid.
     */
    public static function parseChangeTrustAsset(string $value): XdrChangeTrustAsset
    {
        if ($value === 'native' || $value === 'XLM') {
            return new XdrChangeTrustAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        }

        $parts = explode(':', $value, 2);
        if (count($parts) !== 2) {
            throw new InvalidArgumentException('Invalid change trust asset: ' . $value);
        }

        $code = trim($parts[0]);
        $issuer = trim($parts[1]);

        if (!StrKey::isValidAccountId($issuer)) {
            throw new InvalidArgumentException('Invalid change trust asset issuer: ' . $issuer);
        }

        $issuerId = XdrAccountID::fromAccountId($issuer);
        $codeLen = strlen($code);

        if ($codeLen === 0 || $codeLen > 12) {
            throw new InvalidArgumentException('Asset code length must be 1–12 characters: ' . $code);
        }

        if ($codeLen <= 4) {
            $result = new XdrChangeTrustAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
            $result->setAlphaNum4(new XdrAssetAlphaNum4($code, $issuerId));
            return $result;
        }

        $result = new XdrChangeTrustAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
        $result->setAlphaNum12(new XdrAssetAlphaNum12($code, $issuerId));

        return $result;
    }

    /**
     * Format an XdrTrustlineAsset as a TxRep string.
     *
     * Returns `native` for XLM, `CODE:ISSUER` for credit assets, and the
     * 64-character lowercase hex pool ID for pool share assets.
     *
     * @param XdrTrustlineAsset $asset The XDR trustline asset.
     * @return string The TxRep asset string.
     * @throws InvalidArgumentException For unsupported asset types.
     */
    public static function formatTrustlineAsset(XdrTrustlineAsset $asset): string
    {
        switch ($asset->getType()->getValue()) {
            case XdrAssetType::ASSET_TYPE_NATIVE:
                return 'XLM';

            case XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4:
                $a4 = $asset->getAlphaNum4();
                if ($a4 === null) {
                    throw new InvalidArgumentException('Missing alphaNum4 in trustline asset');
                }
                return self::assetCodeFromString($a4->getAssetCode()) . ':' . $a4->getIssuer()->getAccountId();

            case XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12:
                $a12 = $asset->getAlphaNum12();
                if ($a12 === null) {
                    throw new InvalidArgumentException('Missing alphaNum12 in trustline asset');
                }
                return self::assetCodeFromString($a12->getAssetCode()) . ':' . $a12->getIssuer()->getAccountId();

            case XdrAssetType::ASSET_TYPE_POOL_SHARE:
                $poolId = $asset->getLiquidityPoolID();
                if ($poolId === null) {
                    throw new InvalidArgumentException('Missing liquidityPoolID in pool share trustline asset');
                }
                return bin2hex($poolId);

            default:
                throw new InvalidArgumentException(
                    'Unsupported trustline asset type: ' . $asset->getType()->getValue()
                );
        }
    }

    /**
     * Parse a TxRep string to an XdrTrustlineAsset.
     *
     * Handles:
     * - `native` / `XLM` — native asset
     * - 64-character hex string — pool share asset (liquidity pool ID)
     * - `CODE:ISSUER` — credit asset
     *
     * @param string $value TxRep asset string.
     * @return XdrTrustlineAsset The decoded XDR trustline asset.
     * @throws InvalidArgumentException If the asset string is invalid.
     */
    public static function parseTrustlineAsset(string $value): XdrTrustlineAsset
    {
        if ($value === 'native' || $value === 'XLM') {
            return new XdrTrustlineAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        }

        // A 64-character hex string (with no colon) is treated as a pool ID.
        if (strlen($value) === 64 && strpos($value, ':') === false) {
            $bytes = hex2bin($value);
            if ($bytes === false) {
                throw new InvalidArgumentException(
                    'Invalid trustline asset: expected 64-char hex pool ID but got invalid hex: ' . $value
                );
            }
            $result = new XdrTrustlineAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_POOL_SHARE));
            $result->setLiquidityPoolID($bytes);
            return $result;
        }

        $parts = explode(':', $value, 2);
        if (count($parts) !== 2) {
            throw new InvalidArgumentException('Invalid trustline asset: ' . $value);
        }

        $code = trim($parts[0]);
        $issuer = trim($parts[1]);

        if (!StrKey::isValidAccountId($issuer)) {
            throw new InvalidArgumentException('Invalid trustline asset issuer: ' . $issuer);
        }

        $issuerId = XdrAccountID::fromAccountId($issuer);
        $codeLen = strlen($code);

        if ($codeLen === 0 || $codeLen > 12) {
            throw new InvalidArgumentException('Asset code length must be 1–12 characters: ' . $code);
        }

        if ($codeLen <= 4) {
            $result = new XdrTrustlineAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
            $result->setAlphaNum4(new XdrAssetAlphaNum4($code, $issuerId));
            return $result;
        }

        $result = new XdrTrustlineAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
        $result->setAlphaNum12(new XdrAssetAlphaNum12($code, $issuerId));

        return $result;
    }

    /**
     * Format an XdrSignerKey as a StrKey string.
     *
     * Uses the prefix appropriate for the key type:
     * - `G...` for Ed25519 public keys
     * - `T...` for pre-authorization transaction hashes
     * - `X...` for SHA-256 hash keys
     * - `P...` for signed payload keys
     *
     * @param XdrSignerKey $key The XDR signer key.
     * @return string The StrKey-encoded signer key.
     * @throws InvalidArgumentException For unknown signer key types.
     */
    public static function formatSignerKey(XdrSignerKey $key): string
    {
        switch ($key->getType()->getValue()) {
            case XdrSignerKeyType::SIGNER_KEY_TYPE_ED25519:
                $ed25519 = $key->getEd25519();
                if ($ed25519 === null) {
                    throw new InvalidArgumentException('Missing ed25519 bytes in signer key');
                }
                return StrKey::encodeAccountId($ed25519);

            case XdrSignerKeyType::SIGNER_KEY_TYPE_PRE_AUTH_TX:
                $preAuthTx = $key->getPreAuthTx();
                if ($preAuthTx === null) {
                    throw new InvalidArgumentException('Missing preAuthTx bytes in signer key');
                }
                return StrKey::encodePreAuthTx($preAuthTx);

            case XdrSignerKeyType::SIGNER_KEY_TYPE_HASH_X:
                $hashX = $key->getHashX();
                if ($hashX === null) {
                    throw new InvalidArgumentException('Missing hashX bytes in signer key');
                }
                return StrKey::encodeSha256Hash($hashX);

            case XdrSignerKeyType::SIGNER_KEY_TYPE_ED25519_SIGNED_PAYLOAD:
                $signedPayload = $key->getSignedPayload();
                if ($signedPayload === null) {
                    throw new InvalidArgumentException('Missing signedPayload in signer key');
                }
                return StrKey::encodeXdrSignedPayload($signedPayload);

            default:
                throw new InvalidArgumentException(
                    'Unknown signer key type: ' . $key->getType()->getValue()
                );
        }
    }

    /**
     * Parse a StrKey string to an XdrSignerKey.
     *
     * The key type is inferred from the StrKey prefix:
     * - `G` — Ed25519 public key (SIGNER_KEY_TYPE_ED25519)
     * - `T` — pre-authorization transaction hash (SIGNER_KEY_TYPE_PRE_AUTH_TX)
     * - `X` — SHA-256 hash (SIGNER_KEY_TYPE_HASH_X)
     * - `P` — signed payload (SIGNER_KEY_TYPE_ED25519_SIGNED_PAYLOAD)
     *
     * @param string $value StrKey-encoded signer key.
     * @return XdrSignerKey The decoded XDR signer key.
     * @throws InvalidArgumentException If the prefix is unrecognized or the key is invalid.
     */
    public static function parseSignerKey(string $value): XdrSignerKey
    {
        if ($value === '') {
            throw new InvalidArgumentException('Empty signer key string');
        }

        $prefix = $value[0];

        if ($prefix === 'G') {
            if (!StrKey::isValidAccountId($value)) {
                throw new InvalidArgumentException('Invalid Ed25519 signer key: ' . $value);
            }
            $signer = new XdrSignerKey(new XdrSignerKeyType(XdrSignerKeyType::SIGNER_KEY_TYPE_ED25519));
            $signer->setEd25519(StrKey::decodeAccountId($value));
            return $signer;
        }

        if ($prefix === 'T') {
            if (!StrKey::isValidPreAuthTx($value)) {
                throw new InvalidArgumentException('Invalid pre-auth tx signer key: ' . $value);
            }
            $signer = new XdrSignerKey(new XdrSignerKeyType(XdrSignerKeyType::SIGNER_KEY_TYPE_PRE_AUTH_TX));
            $signer->setPreAuthTx(StrKey::decodePreAuthTx($value));
            return $signer;
        }

        if ($prefix === 'X') {
            if (!StrKey::isValidSha256Hash($value)) {
                throw new InvalidArgumentException('Invalid sha256-hash signer key: ' . $value);
            }
            $signer = new XdrSignerKey(new XdrSignerKeyType(XdrSignerKeyType::SIGNER_KEY_TYPE_HASH_X));
            $signer->setHashX(StrKey::decodeSha256Hash($value));
            return $signer;
        }

        if ($prefix === 'P') {
            $signer = new XdrSignerKey(new XdrSignerKeyType(XdrSignerKeyType::SIGNER_KEY_TYPE_ED25519_SIGNED_PAYLOAD));
            $signer->setSignedPayload(StrKey::decodeXdrSignedPayload($value));
            return $signer;
        }

        throw new InvalidArgumentException('Unknown signer key prefix: ' . $value);
    }

    /**
     * Format an XdrAllowTrustOperationAsset as a compact asset code string.
     *
     * Returns the asset code with trailing null bytes stripped.
     *
     * @param XdrAllowTrustOperationAsset $asset The XDR allow-trust asset.
     * @return string The asset code string.
     * @throws InvalidArgumentException For unsupported asset types.
     */
    public static function formatAllowTrustAsset(XdrAllowTrustOperationAsset $asset): string
    {
        switch ($asset->getType()->getValue()) {
            case XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4:
                $code = $asset->getAssetCode4();
                if ($code === null) {
                    throw new InvalidArgumentException('Missing assetCode4 in allow trust asset');
                }
                return self::assetCodeFromString($code);

            case XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12:
                $code = $asset->getAssetCode12();
                if ($code === null) {
                    throw new InvalidArgumentException('Missing assetCode12 in allow trust asset');
                }
                return self::assetCodeFromString($code);

            default:
                throw new InvalidArgumentException(
                    'Unsupported allow trust asset type: ' . $asset->getType()->getValue()
                );
        }
    }

    /**
     * Parse an asset code string to an XdrAllowTrustOperationAsset.
     *
     * Codes up to 4 characters become ASSET_TYPE_CREDIT_ALPHANUM4; codes up
     * to 12 characters become ASSET_TYPE_CREDIT_ALPHANUM12.
     *
     * @param string $code The asset code (1–12 characters).
     * @return XdrAllowTrustOperationAsset The decoded XDR allow-trust asset.
     * @throws InvalidArgumentException If the code length is invalid.
     */
    public static function parseAllowTrustAsset(string $code): XdrAllowTrustOperationAsset
    {
        $codeLen = strlen($code);

        if ($codeLen === 0 || $codeLen > 12) {
            throw new InvalidArgumentException('Asset code length must be 1–12 characters: ' . $code);
        }

        if ($codeLen <= 4) {
            $result = new XdrAllowTrustOperationAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
            $result->setAssetCode4($code);
            return $result;
        }

        $result = new XdrAllowTrustOperationAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
        $result->setAssetCode12($code);

        return $result;
    }

    // ---------------------------------------------------------------------------
    // Private helpers
    // ---------------------------------------------------------------------------

    /**
     * Extract a clean asset code from a raw XDR asset code string.
     *
     * XDR stores asset codes as null-padded fixed-length fields. The PHP SDK's
     * XdrBuffer::readOpaqueFixedString() strips trailing nulls when decoding, but
     * raw binary fields may still contain them. This method ensures trailing null
     * bytes are removed.
     *
     * @param string $code Raw asset code string (may have trailing null bytes).
     * @return string Clean asset code without trailing nulls.
     */
    private static function assetCodeFromString(string $code): string
    {
        return rtrim($code, "\x00");
    }
}
