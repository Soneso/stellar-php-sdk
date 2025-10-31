<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Constants;

/**
 * Cryptographic Constants for Stellar StrKey Encoding
 *
 * This class contains constants related to Stellar's strkey encoding format,
 * which is used for encoding public keys, secret seeds, and other identifiers
 * in a human-readable base32 format with checksums.
 *
 * Strkey Format: version byte + payload + 2-byte CRC16 checksum, then base32 encoded
 *
 * References:
 * - SEP-0023 (Strkeys): https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0023.md
 * - Stellar Protocol: https://github.com/stellar/stellar-protocol
 *
 * Note: This class cannot be instantiated. All constants are static and
 * should be accessed directly via the class name.
 */
final class CryptoConstants
{
    // Private constructor to prevent instantiation
    private function __construct() {}

    // ============================================================================
    // STRKEY ENCODING LENGTHS
    // ============================================================================
    // Strkey is the base32 encoding format used for Stellar addresses and keys.
    // Format: version byte + payload + 2-byte CRC16 checksum, then base32 encoded
    // Reference: SEP-0023 https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0023.md

    /**
     * Length of a Stellar account ID in strkey format (G...).
     *
     * Format: 1 byte version + 32 bytes public key + 2 bytes checksum = 35 bytes
     * Base32 encoded: ceil(35 × 8 ÷ 5) = 56 characters
     *
     * Account IDs are Ed25519 public keys encoded with the 'G' prefix.
     *
     * Example: GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H
     *
     * Unit: characters (base32 encoded)
     *
     * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0023.md
     */
    public const STRKEY_ACCOUNT_ID_LENGTH = 56;

    /**
     * Length of a muxed account ID in strkey format (M...).
     *
     * Format: 1 byte version + 32 bytes public key + 8 bytes ID + 2 bytes checksum = 43 bytes
     * Base32 encoded: ceil(43 × 8 ÷ 5) = 69 characters
     *
     * Muxed accounts allow multiple virtual accounts to share the same underlying
     * Stellar account. Defined in CAP-0027.
     *
     * Example: MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVAAAAAAAAAAAAAJLK
     *
     * Unit: characters (base32 encoded)
     *
     * Reference: CAP-0027 (Muxed Accounts)
     * @see https://github.com/stellar/stellar-protocol/blob/master/core/cap-0027.md
     */
    public const STRKEY_MUXED_ACCOUNT_ID_LENGTH = 69;


    /**
     * Length of a claimable balance ID in strkey format (B... or encoded hex).
     *
     * Format: 1 byte discriminant + 32 bytes balance ID + 2 bytes checksum = 35 bytes
     * Base32 encoded: 58 characters (due to encoding specifics with the balance ID structure)
     *
     * Claimable balances are defined in CAP-0023. Note that this length is different
     * from other strkey formats due to the balance ID structure.
     *
     * Example: 00000000178826fbfe339e1f5c53417c6fedfe2c05e8bec14303143ec46b38981b09c3f9
     *
     * Unit: characters
     *
     * Reference: CAP-0023 (Claimable Balances)
     * @see https://github.com/stellar/stellar-protocol/blob/master/core/cap-0023.md
     */
    public const STRKEY_CLAIMABLE_BALANCE_LENGTH = 58;


    /**
     * Minimum length of a signed payload in strkey format (P...).
     *
     * Format: 1 byte version + 32 bytes public key + 4 bytes length prefix + 4 bytes min payload + 2 bytes checksum = 43 bytes
     * Base32 encoded minimum: ceil(43 × 8 ÷ 5) = 69 characters
     *
     * Signed payloads combine a public key with arbitrary data (4-64 bytes).
     * They are used for per-signature authorization data in Soroban contracts.
     *
     * IMPORTANT: This was incorrectly set to 56 in the previous implementation.
     * The correct value is 69 characters.
     *
     * Example: PA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAOQCAQDAQCQMBYIBEFAWDANBYHRAEISCMKBKFQXDAMRUGY4DUPB6IBZGM
     *
     * Unit: characters (base32 encoded)
     *
     * Reference: CAP-0040 (Signed Payload Signer)
     * @see https://github.com/stellar/stellar-protocol/blob/master/core/cap-0040.md
     */
    public const STRKEY_SIGNED_PAYLOAD_MIN_LENGTH = 69;

    /**
     * Maximum length of a signed payload in strkey format (P...).
     *
     * Format: 1 byte version + 32 bytes public key + 4 bytes length prefix + 64 bytes max payload + 2 bytes checksum = 103 bytes
     * Base32 encoded: ceil(103 × 8 ÷ 5) = 165 characters
     *
     * The payload can be up to 64 bytes as defined in CAP-0040.
     *
     * Unit: characters (base32 encoded)
     *
     * Reference: CAP-0040 (Signed Payload Signer)
     * @see https://github.com/stellar/stellar-protocol/blob/master/core/cap-0040.md
     */
    public const STRKEY_SIGNED_PAYLOAD_MAX_LENGTH = 165;


    // ============================================================================
    // CRC16 CHECKSUM CONSTANTS
    // ============================================================================
    // Constants used in CRC16 checksum calculation for strkey encoding.
    // The CRC16 checksum provides error detection for encoded addresses.
    //
    // Reference: CRC16-CCITT algorithm

    /**
     * CRC16 initial value.
     *
     * The starting value for CRC16 checksum calculation. The algorithm
     * begins with this value and XORs/shifts through the input data.
     *
     * Hexadecimal: 0x0000
     * Decimal: 0
     */
    public const CRC16_INITIAL = 0x0000;

    /**
     * CRC16 polynomial for checksum calculation.
     *
     * This is the CRC16-CCITT polynomial used by Stellar for strkey checksums.
     * The polynomial defines the bit pattern used in the CRC algorithm.
     *
     * Hexadecimal: 0x1021
     * Decimal: 4129
     * Binary: 0001 0000 0010 0001
     *
     * Reference: CRC16-CCITT (XModem) polynomial
     */
    public const CRC16_POLYNOMIAL = 0x1021;

    /**
     * CRC16 mask for 16-bit values.
     *
     * Used to ensure CRC16 intermediate values remain within 16-bit range
     * by masking off higher bits during calculation. This ensures the
     * checksum is always 2 bytes (16 bits).
     *
     * Hexadecimal: 0xFFFF
     * Decimal: 65535
     * Binary: 1111 1111 1111 1111
     */
    public const CRC16_MASK = 0xFFFF;


    // ============================================================================
    // SIGNED PAYLOAD STRUCTURE CONSTANTS
    // ============================================================================
    // Constants for signed payload internal structure.
    //
    // Reference: CAP-0040 (Signed Payload Signer)
    // @see https://github.com/stellar/stellar-protocol/blob/master/core/cap-0040.md

    /**
     * Length of the payload length prefix in signed payloads.
     *
     * Signed payloads include a 4-byte length prefix that specifies the
     * size of the following payload data. This allows variable-length
     * payloads up to the maximum size.
     *
     * Unit: bytes
     */
    public const SIGNED_PAYLOAD_LENGTH_PREFIX_BYTES = 4;

}
