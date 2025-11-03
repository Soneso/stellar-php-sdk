<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Util;

/**
 * SHA-256 hash utility for Stellar operations
 *
 * Provides cryptographic hash functions commonly used throughout the Stellar SDK,
 * particularly for transaction hashing, signature verification, and data integrity checks.
 *
 * @package Soneso\StellarSDK\Util
 * @see https://developers.stellar.org/docs/encyclopedia/signatures-multisig Documentation on Stellar signatures
 */
class Hash
{
    /**
     * Returns the raw bytes of a sha-256 hash of $data
     *
     * @param string $data The data to hash
     * @return string Raw binary hash output (32 bytes)
     */
    public static function generate(string $data): string
    {
        return hash('sha256', $data, true);
    }

    /**
     * Returns a string representation of the sha-256 hash of $data
     *
     * @param string $data The data to hash
     * @return string Hexadecimal string representation of the hash (64 characters)
     */
    public static function asString(string $data): string
    {
        return hash('sha256', $data, false);
    }
}