<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Crypto;

/**
 * Version byte constants for Stellar StrKey encoding
 *
 * Each Stellar address type has a unique version byte that determines the first
 * character of the encoded string representation. These version bytes are used
 * when encoding and decoding between binary data and human-readable strings.
 *
 * Address prefixes:
 * - G: Account ID (Ed25519 public key)
 * - M: Muxed account ID (multiplexed account)
 * - S: Secret seed (Ed25519 private key)
 * - T: Pre-authorized transaction hash
 * - X: SHA-256 hash (for hash-locked transactions)
 * - P: Signed payload (Ed25519 with additional signature data)
 * - C: Contract ID (Soroban smart contract)
 * - L: Liquidity pool ID
 * - B: Claimable balance ID
 *
 * @package Soneso\StellarSDK\Crypto
 * @see StrKey For encoding and decoding operations
 * @see https://developers.stellar.org Stellar developer docs Documentation on address types
 */
class VersionByte
{
    const ACCOUNT_ID = 6 << 3; // G
    const MUXED_ACCOUNT_ID = 12 << 3; // M
    const SEED = 18 << 3; // S
    const PRE_AUTH_TX = 19 << 3; // T
    const SHA256_HASH = 23 << 3; //X
    const SIGNED_PAYLOAD = 15 << 3; // P
    const CONTRACT_ID = 2 << 3; // C
    const LIQUIDITY_POOL_ID = 11 << 3; // L
    const CLAIMABLE_BALANCE_ID = 1 << 3; // B
}