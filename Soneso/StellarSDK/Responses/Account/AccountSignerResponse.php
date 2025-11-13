<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Account;

/**
 * Represents a single signer configured on an account
 *
 * Signers are additional keys that can authorize transactions for an account. Each signer
 * has a weight that contributes to meeting the account's threshold requirements for operations.
 * Multiple signers enable multi-signature functionality for enhanced security.
 *
 * Signer types:
 * - ed25519_public_key: Standard Stellar public key (G...)
 * - sha256_hash: Hash preimage signer (X...)
 * - preauth_tx: Pre-authorized transaction hash (T...)
 *
 * This response is included in AccountResponse as part of the signers array.
 *
 * @package Soneso\StellarSDK\Responses\Account
 * @see AccountResponse For the parent account details
 * @see AccountThresholdsResponse For threshold requirements
 * @see https://developers.stellar.org Stellar developer docs Multi-Signature Documentation
 * @since 1.0.0
 */
class AccountSignerResponse
{

    private string $key;
    private string $type;
    private int $weight;
    private ?string $sponsor = null;

    /**
     * Gets the signer's public key or hash
     *
     * Format depends on the signer type (G... for ed25519, X... for hash, T... for preauth).
     *
     * @return string The signer key
     */
    public function getKey() : string {
        return $this->key;
    }

    /**
     * Gets the signer type
     *
     * Possible values: ed25519_public_key, sha256_hash, preauth_tx
     *
     * @return string The signer type
     */
    public function getType() : string {
        return $this->type;
    }

    /**
     * Gets the signer's weight
     *
     * The weight contributes to meeting threshold requirements for authorizing operations.
     *
     * @return int The signer weight (0-255)
     */
    public function getWeight() : int {
        return $this->weight;
    }

    /**
     * Gets the sponsor account ID for this signer entry
     *
     * @return string|null The sponsor account ID, or null if not sponsored
     */
    public function getSponsor() : ?string {
        return $this->sponsor;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['key'])) $this->key = $json['key'];
        if (isset($json['type'])) $this->type = $json['type'];
        if (isset($json['weight'])) $this->weight = $json['weight'];
        if (isset($json['sponsor'])) $this->sponsor = $json['sponsor'];
    }

    /**
     * Creates an AccountSignerResponse instance from JSON data
     *
     * @param array $json The JSON array containing signer data from Horizon
     * @return AccountSignerResponse The parsed signer response
     */
    public static function fromJson(array $json) : AccountSignerResponse {
        $result = new AccountSignerResponse();
        $result->loadFromJson($json);
        return $result;
    }

}

