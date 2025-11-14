<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Transaction;

/**
 * Represents a Protocol 13+ fee-bump transaction wrapper
 *
 * Fee-bump transactions allow any account to pay the fee for an existing transaction
 * without needing to re-sign the original transaction. This is useful for sponsored
 * transactions, fee bumping stuck transactions, or third-party fee payment scenarios.
 *
 * This response contains the hash of the fee-bump transaction envelope and its signatures.
 * The fee-bump transaction wraps an inner transaction, which is accessible through
 * InnerTransactionResponse.
 *
 * Introduced in Stellar Protocol 13 (CAP-15).
 *
 * @package Soneso\StellarSDK\Responses\Transaction
 * @see InnerTransactionResponse For the wrapped inner transaction details
 * @see TransactionResponse For the parent transaction response containing fee-bump details
 * @see https://developers.stellar.org Stellar developer docs Fee-Bump Transactions
 * @see https://stellar.org/protocol/cap-15 CAP-15: Fee-Bump Transactions
 * @since 1.0.0
 */
class FeeBumpTransactionResponse
{
    private string $hash;
    private TransactionSignaturesResponse $signatures;

    /**
     * Gets the hash of the fee-bump transaction envelope
     *
     * Returns the hexadecimal hash of the fee-bump transaction envelope, which is distinct
     * from the inner transaction hash. This hash identifies the entire fee-bump transaction
     * including the new fee and fee payer.
     *
     * @return string The 64-character hexadecimal fee-bump transaction hash
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * Gets the signatures attached to the fee-bump transaction
     *
     * Returns the collection of signatures from the account that created the fee-bump
     * transaction. These are separate from the inner transaction's signatures.
     *
     * @return TransactionSignaturesResponse Collection of fee-bump transaction signatures
     */
    public function getSignatures(): TransactionSignaturesResponse
    {
        return $this->signatures;
    }

    /**
     * Loads fee-bump transaction data from JSON response
     *
     * @param array $json The JSON array containing fee-bump transaction data
     * @return void
     */
    protected function loadFromJson(array $json) : void {

        if (isset($json['hash'])) $this->hash = $json['hash'];

        if (isset($json['signatures'])) {
            $this->signatures = new TransactionSignaturesResponse();
            foreach ($json['signatures'] as $signature) {
                $this->signatures->add($signature);
            }
        }
    }

    /**
     * Creates a FeeBumpTransactionResponse instance from JSON data
     *
     * @param array $json The JSON array containing fee-bump transaction data from Horizon
     * @return FeeBumpTransactionResponse The parsed fee-bump transaction response
     */
    public static function fromJson(array $json) : FeeBumpTransactionResponse
    {
        $result = new FeeBumpTransactionResponse();
        $result->loadFromJson($json);
        return $result;
    }
}