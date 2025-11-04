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
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0030.md
 * @see SEP30AccountResponse
 */
class SEP30ResponseSigner
{
    public string $key;

    /**
     * Constructor.
     *
     * @param string $key The signer's public key (G-address).
     */
    public function __construct(string $key)
    {
        $this->key = $key;
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
}