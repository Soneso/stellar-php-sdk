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


    // ============================================================================
    // HD WALLET KEY DERIVATION CONSTANTS (BIP32)
    // ============================================================================
    // Constants related to hierarchical deterministic wallet key derivation
    // as defined in BIP32 specification.
    //
    // Reference: BIP32 specification
    // @see https://github.com/bitcoin/bips/blob/master/bip-0032.mediawiki

    /**
     * Length of BIP32 chain code in bytes.
     *
     * Chain codes are used in HD wallet key derivation and are
     * always 32 bytes as defined by BIP32. The chain code is combined
     * with the private key to derive child keys.
     *
     * Unit: bytes
     *
     * Reference: BIP32 specification
     * @see https://github.com/bitcoin/bips/blob/master/bip-0032.mediawiki
     */
    public const CHAIN_CODE_LENGTH_BYTES = 32;

    /**
     * BIP32 hardened derivation minimum index.
     *
     * In BIP32 HD wallets, indices >= 2^31 (0x80000000) indicate
     * hardened key derivation. Hardened derivation prevents child
     * public keys from being derived from the parent public key,
     * providing additional security.
     *
     * Hexadecimal: 0x80000000
     * Decimal: 2147483648
     * Binary: 10000000 00000000 00000000 00000000
     *
     * Reference: BIP32 specification - Hardened keys
     * @see https://github.com/bitcoin/bips/blob/master/bip-0032.mediawiki#child-key-derivation-ckd-functions
     */
    public const BIP32_HARDENED_MINIMUM_INDEX = 0x80000000;

    /**
     * Length of HMAC key part in HD wallet derivation.
     *
     * When performing HMAC-SHA512 during BIP32 key derivation, the 64-byte
     * output is split into two 32-byte parts. The first 32 bytes are used
     * as the child key material.
     *
     * Unit: bytes
     *
     * Reference: BIP32 specification - Key derivation
     * @see https://github.com/bitcoin/bips/blob/master/bip-0032.mediawiki#child-key-derivation-ckd-functions
     */
    public const HMAC_KEY_PART_LENGTH = 32;

    /**
     * Offset for HMAC chain part in HD wallet derivation.
     *
     * When performing HMAC-SHA512 during BIP32 key derivation, the 64-byte
     * output is split into two 32-byte parts. The second 32 bytes (at offset 32)
     * are used as the child chain code.
     *
     * Unit: bytes (offset)
     *
     * Reference: BIP32 specification - Key derivation
     * @see https://github.com/bitcoin/bips/blob/master/bip-0032.mediawiki#child-key-derivation-ckd-functions
     */
    public const HMAC_CHAIN_PART_OFFSET = 32;

    /**
     * BIP32 key padding byte for hardened derivation.
     *
     * When deriving hardened child keys in BIP32, a 0x00 byte is prepended
     * to the private key before HMAC hashing. This ensures hardened keys
     * cannot be derived from the public key.
     *
     * Hexadecimal: 0x00
     * Decimal: 0
     *
     * Reference: BIP32 specification - Hardened key derivation
     * @see https://github.com/bitcoin/bips/blob/master/bip-0032.mediawiki#child-key-derivation-ckd-functions
     */
    public const KEY_PADDING_BYTE = 0x00;

}
