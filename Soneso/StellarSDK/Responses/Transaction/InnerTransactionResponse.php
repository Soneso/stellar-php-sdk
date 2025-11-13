<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Transaction;

/**
 * Represents the wrapped inner transaction in a fee-bump transaction
 *
 * When a transaction is fee-bumped, the original transaction becomes the inner transaction
 * and is wrapped by a fee-bump transaction envelope. This response contains the original
 * transaction's hash, maximum fee, and signatures.
 *
 * The inner transaction retains its original properties but is executed with the fee and
 * fee account specified in the outer fee-bump transaction. This allows the original
 * transaction to remain unchanged while a different account sponsors the fee.
 *
 * Available in fee-bump transactions introduced in Protocol 13 (CAP-15).
 *
 * @package Soneso\StellarSDK\Responses\Transaction
 * @see FeeBumpTransactionResponse For the outer fee-bump transaction wrapper
 * @see TransactionResponse For the parent transaction response
 * @see https://developers.stellar.org Stellar developer docs Fee-Bump Transactions
 * @see https://stellar.org/protocol/cap-15 CAP-15: Fee-Bump Transactions
 * @since 1.0.0
 */
class InnerTransactionResponse
{
    private string $hash;
    private TransactionSignaturesResponse $signatures;
    private string $maxFee;

    /**
     * Gets the maximum fee the original transaction was willing to pay
     *
     * Returns the max_fee from the original transaction in stroops. Note that in a fee-bump
     * transaction, this fee is not actually charged; the fee-bump transaction's fee is used
     * instead. This value is retained for reference to the original transaction parameters.
     *
     * @return string The maximum fee in stroops as a string
     */
    public function getMaxFee(): string
    {
        return $this->maxFee;
    }

    /**
     * Gets the hash of the original inner transaction
     *
     * Returns the hexadecimal hash of the inner transaction envelope before it was wrapped
     * by the fee-bump transaction. This is the original transaction hash that would have
     * been used if the transaction was not fee-bumped.
     *
     * @return string The 64-character hexadecimal inner transaction hash
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * Gets the signatures from the original inner transaction
     *
     * Returns the collection of signatures that were attached to the inner transaction
     * before it was fee-bumped. These signatures remain valid and are required for the
     * transaction to execute successfully.
     *
     * @return TransactionSignaturesResponse Collection of inner transaction signatures
     */
    public function getSignatures(): TransactionSignaturesResponse
    {
        return $this->signatures;
    }

    /**
     * Loads inner transaction data from JSON response
     *
     * @param array $json The JSON array containing inner transaction data
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

        if (isset($json['max_fee'])) $this->maxFee = $json['max_fee'];
    }

    /**
     * Creates an InnerTransactionResponse instance from JSON data
     *
     * @param array $json The JSON array containing inner transaction data from Horizon
     * @return InnerTransactionResponse The parsed inner transaction response
     */
    public static function fromJson(array $json) : InnerTransactionResponse
    {
        $result = new InnerTransactionResponse();
        $result->loadFromJson($json);
        return $result;
    }
}