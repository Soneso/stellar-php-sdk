<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Recovery;

/**
 * Signer information in SEP-0030 account responses.
 *
 * This class represents a signer public key that can be used to sign
 * transactions for account recovery operations.
 *
 * @package Soneso\StellarSDK\SEP\Recovery
 * @see https://github.com/stellar/stellar-protocol/blob/v0.8.1/ecosystem/sep-0030.md
 * @see SEP30AccountResponse
 * @see RecoveryService::signTransaction()
 */
class SEP30ResponseSigner
{
    /**
     * @param string $key The signer's public key in Stellar G... format. This key should be added to the account as a signer.
     */
    public function __construct(
        public string $key,
    ) {
    }

    /**
     * Constructs a SEP30ResponseSigner from JSON data.
     *
     * @param array<array-key, mixed> $json The JSON data to parse.
     * @return SEP30ResponseSigner The constructed signer.
     */
    public static function fromJson(array $json) : SEP30ResponseSigner
    {
        return new SEP30ResponseSigner($json['key']);
    }

    /**
     * Gets the signer's public key.
     *
     * @return string The Stellar public key in G... format that the server uses to sign transactions.
     *                This key should be added as a signer to the account with appropriate weight.
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Sets the signer's public key.
     *
     * @param string $key The Stellar public key in G... format.
     */
    public function setKey(string $key): void
    {
        $this->key = $key;
    }
}