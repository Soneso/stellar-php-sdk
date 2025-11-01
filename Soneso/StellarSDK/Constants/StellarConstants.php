<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Constants;

/**
 * Stellar Protocol Constants
 *
 * This class contains fundamental constants defined by the Stellar protocol.
 * These values are specified in the protocol documentation and should not be
 * changed unless the protocol itself changes.
 *
 * References:
 * - Stellar Protocol: https://github.com/stellar/stellar-protocol
 * - Stellar Developers: https://developers.stellar.org/docs/
 * - CAP specifications: https://github.com/stellar/stellar-protocol/tree/master/core
 *
 * Note: This class cannot be instantiated. All constants are static and
 * should be accessed directly via the class name.
 */
final class StellarConstants
{
    // Private constructor to prevent instantiation
    private function __construct() {}

    // ============================================================================
    // CRYPTOGRAPHIC KEY SIZES
    // ============================================================================
    // Constants related to Ed25519 cryptography and hashing algorithms.
    //
    // Reference: Ed25519 specification https://ed25519.cr.yp.to/

    /**
     * Length of an Ed25519 public key in bytes.
     *
     * Ed25519 public keys are always 32 bytes (256 bits) as defined by
     * the cryptographic specification. This is the standard size for
     * Stellar account public keys.
     *
     * @see https://ed25519.cr.yp.to/
     */
    public const ED25519_PUBLIC_KEY_LENGTH_BYTES = 32;


    /**
     * Length of decoded muxed account data in bytes.
     *
     * Consists of Ed25519 public key (32 bytes) + muxed ID (8 bytes) = 40 bytes total.
     * This is the size of the decoded payload for a muxed account address.
     *
     * Reference: CAP-0027 (Muxed Accounts)
     * @see https://github.com/stellar/stellar-protocol/blob/master/core/cap-0027.md
     */
    public const MUXED_ACCOUNT_DECODED_LENGTH = 40; // 32 + 8 bytes

    // ============================================================================
    // ASSET CODE LENGTHS
    // ============================================================================
    // Asset codes can be 1-4 characters (AlphaNum4) or 5-12 characters (AlphaNum12).
    //
    // Reference: Stellar Protocol - Asset specification
    // @see https://developers.stellar.org/docs/learn/fundamentals/stellar-data-structures/assets

    /**
     * Minimum length for any asset code.
     *
     * Asset codes must be at least 1 character long.
     * Shorter codes are not valid in the Stellar protocol.
     */
    public const ASSET_CODE_MIN_LENGTH = 1;

    /**
     * Maximum length for AlphaNum4 asset codes.
     *
     * AlphaNum4 assets can have codes from 1 to 4 characters.
     * Common examples include USD, BTC, EUR, XLM.
     *
     * Example: USD, BTC, EUR
     */
    public const ASSET_CODE_ALPHANUMERIC_4_MAX_LENGTH = 4;

    /**
     * Minimum length for AlphaNum12 asset codes.
     *
     * AlphaNum12 assets must have codes from 5 to 12 characters.
     * Any asset code longer than 4 characters uses AlphaNum12 encoding.
     *
     * Example: USDC, EURT, MOBI
     */
    public const ASSET_CODE_ALPHANUMERIC_12_MIN_LENGTH = 5;

    /**
     * Maximum length for AlphaNum12 asset codes.
     *
     * AlphaNum12 assets can have codes from 5 to 12 characters.
     * This is the maximum length allowed by the protocol.
     *
     * Example: LONGASSET12, STELLARCOIN
     */
    public const ASSET_CODE_ALPHANUMERIC_12_MAX_LENGTH = 12;

    // ============================================================================
    // TRANSACTION AND ACCOUNT LIMITS
    // ============================================================================
    // Limits on transaction and account settings as defined by the Stellar protocol.
    //
    // Reference: stellar-core source code and XDR definitions
    // @see https://github.com/stellar/stellar-core

    /**
     * Minimum base fee for a transaction in stroops.
     *
     * The base fee is the minimum fee per operation in a transaction.
     * 1 stroop = 0.0000001 XLM, so 100 stroops = 0.00001 XLM.
     * The total transaction fee is: base_fee × number_of_operations.
     *
     * Unit: stroops (1 stroop = 10^-7 XLM)
     *
     * Note: During network congestion, higher fees may be required
     * for transaction inclusion.
     *
     * @see https://developers.stellar.org/docs/learn/fundamentals/fees-resource-limits-metering
     */
    public const MIN_BASE_FEE_STROOPS = 100;

    /**
     * Maximum length for an account's home domain string.
     *
     * The home domain is used for federation and stellar.toml file hosting.
     * It can be up to 32 characters and must be a valid domain name.
     * This corresponds to the XDR string32 type.
     *
     * Reference: XDR type string32 and federation specification
     * @see https://developers.stellar.org/docs/learn/encyclopedia/federation
     * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0001.md
     */
    public const HOME_DOMAIN_MAX_LENGTH = 32;

    // ============================================================================
    // MEMO LIMITS
    // ============================================================================
    // Constants related to transaction memo fields.
    //
    // Reference: Stellar Protocol - Transaction specification
    // @see https://developers.stellar.org/docs/learn/fundamentals/transactions/transaction-data-structures

    /**
     * Maximum length for a text memo in bytes.
     *
     * Text memos can contain up to 28 bytes of UTF-8 encoded text.
     * This is smaller than the hash memo to leave room for the XDR
     * discriminant in the encoded transaction.
     *
     * Unit: bytes (UTF-8 encoded)
     *
     * Reference: XDR Memo specification
     */
    public const MEMO_TEXT_MAX_LENGTH = 28;

    /**
     * Length of a hash/return hash memo in bytes.
     *
     * Hash and return hash memos must be exactly 32 bytes.
     * These are typically SHA256 hashes used to reference
     * external transaction data or payment identifiers.
     *
     * Unit: bytes
     */
    public const MEMO_HASH_LENGTH = 32;

    // ============================================================================
    // SIGNED PAYLOAD CONSTANTS
    // ============================================================================
    // Constants related to signed payloads as defined in CAP-0040.
    //
    // Reference: CAP-0040 (Signed Payload Signer)
    // @see https://github.com/stellar/stellar-protocol/blob/master/core/cap-0040.md

    /**
     * Maximum length of a signed payload in bytes.
     *
     * Signed payloads can contain up to 64 bytes of arbitrary data.
     * This allows for attaching small amounts of metadata to signatures,
     * useful for per-signature authorization data in smart contracts.
     *
     * Unit: bytes
     *
     * Reference: CAP-0040
     * @see https://github.com/stellar/stellar-protocol/blob/master/core/cap-0040.md
     */
    public const SIGNED_PAYLOAD_MAX_LENGTH_BYTES = 64;

    /**
     * Minimum length of a signed payload in bytes.
     *
     * Signed payloads must contain at least 4 bytes of data to be valid.
     * This ensures there is meaningful data being signed.
     *
     * Unit: bytes
     */
    public const SIGNED_PAYLOAD_MIN_LENGTH_BYTES = 4;

    // ============================================================================
    // THRESHOLD CONSTANTS
    // ============================================================================
    // Constants related to account thresholds and signer weights.
    //
    // Reference: Stellar Protocol - Multisig specification
    // @see https://developers.stellar.org/docs/learn/encyclopedia/security/signatures-multisig

    /**
     * Minimum value for account thresholds and signer weights.
     *
     * Thresholds and weights must be at least 0 (disabled/no weight).
     * A threshold of 0 means the operation can be performed without
     * any signatures.
     *
     * Unit: weight (dimensionless)
     */
    public const THRESHOLD_MIN = 0;

    /**
     * Maximum value for account thresholds and signer weights.
     *
     * Thresholds and weights can be at most 255. This is the maximum
     * value that can be stored in a single byte (uint8).
     *
     * Unit: weight (dimensionless)
     */
    public const THRESHOLD_MAX = 255;

    /**
     * Bitmask for extracting valid threshold/weight values.
     *
     * Used to ensure weight values are within the valid range [0-255]
     * by masking to the lower 8 bits. This corresponds to a single byte.
     *
     * Binary: 0b11111111
     * Hexadecimal: 0xFF
     * Decimal: 255
     */
    public const SIGNER_WEIGHT_MASK = 0xFF;

    /**
     * Length of decoded data for claimable balance IDs.
     *
     * Claimable balance IDs consist of a 1-byte discriminant followed by
     * a 32-byte balance ID hash, for a total of 33 bytes when decoded.
     *
     * Unit: bytes
     *
     * Reference: CAP-0023 (Claimable Balances)
     * @see https://github.com/stellar/stellar-protocol/blob/master/core/cap-0023.md
     */
    public const CLAIMABLE_BALANCE_DECODED_LENGTH = 33;

    // ============================================================================
    // STROOP AND AMOUNT CONVERSIONS
    // ============================================================================
    // Constants related to Stellar's currency unit conversions.
    //
    // Reference: Stellar Protocol - Asset specification
    // @see https://developers.stellar.org/docs/learn/fundamentals/stellar-data-structures/assets

    /**
     * Stroop scale factor for converting between XLM and stroops.
     *
     * 1 XLM = 10,000,000 stroops (10 million)
     * This is the fundamental unit conversion in Stellar.
     * Stroops are the smallest unit of XLM, similar to satoshis in Bitcoin.
     *
     * Unit: stroops per XLM
     *
     * Example:
     * - 1 XLM = 10,000,000 stroops
     * - 0.1 XLM = 1,000,000 stroops
     * - 100 stroops = 0.00001 XLM (minimum base fee)
     *
     * @see https://developers.stellar.org/docs/learn/fundamentals/fees-resource-limits-metering
     */
    public const STROOP_SCALE = 10000000;

    // ============================================================================
    // TRANSACTION LIMITS
    // ============================================================================
    // Constants related to transaction construction and validation.
    //
    // Reference: Stellar Protocol - Transaction specification
    // @see https://developers.stellar.org/docs/learn/fundamentals/transactions

    /**
     * Maximum number of operations allowed in a single transaction.
     *
     * Stellar protocol limits transactions to 100 operations maximum.
     * This ensures transactions remain within reasonable size limits
     * and can be processed efficiently by validators.
     *
     * Unit: count (number of operations)
     *
     * Note: The total transaction fee is calculated as:
     * total_fee = base_fee × number_of_operations
     *
     * Reference: Stellar Protocol - Transaction specification
     * @see https://developers.stellar.org/docs/learn/fundamentals/transactions
     */
    public const MAX_OPERATIONS_PER_TRANSACTION = 100;

    // ============================================================================
    // SOROBAN AND LEDGER CONSTANTS
    // ============================================================================
    // Constants related to Soroban smart contracts and ledger management.
    //
    // Reference: Soroban documentation
    // @see https://developers.stellar.org/docs/learn/smart-contract-internals/state-archival

    /**
     * Default ledger expiration offset for Soroban transactions.
     *
     * When setting ledger bounds for Soroban transactions, this offset
     * is added to the current ledger sequence to determine expiration.
     *
     * Default: current sequence + 100 blocks (approximately 8.3 minutes)
     * - Average ledger close time: ~5 seconds
     * - 100 ledgers ≈ 8.3 minutes
     *
     * Unit: ledger blocks
     *
     * Note: This provides a reasonable validity window for contract
     * invocations while preventing stale transactions.
     *
     * Reference: Soroban documentation - State archival
     * @see https://developers.stellar.org/docs/learn/smart-contract-internals/state-archival
     */
    public const DEFAULT_LEDGER_EXPIRATION_OFFSET = 100;
}
