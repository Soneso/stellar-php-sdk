<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Transaction;

/**
 * Represents additional submission details for transaction responses
 *
 * This response contains supplementary information returned by Horizon when a transaction
 * is submitted, particularly useful for debugging failed transactions. Includes base64-encoded
 * XDR representations of the transaction envelope and result, plus human-readable result codes.
 *
 * The envelope XDR contains the complete signed transaction as submitted, while the result XDR
 * contains the execution outcome. Result codes provide specific error information at both the
 * transaction and operation levels when failures occur.
 *
 * Typically populated for failed transaction submissions, but may also be included in
 * successful submissions depending on Horizon configuration.
 *
 * @package Soneso\StellarSDK\Responses\Transaction
 * @see SubmitTransactionResponse For the parent submission response
 * @see ExtrasResultCodes For detailed result code information
 * @see https://developers.stellar.org/api/aggregations/endpoints/submit-transaction Submit Transaction
 * @since 1.0.0
 */
class SubmitTransactionResponseExtras
{
    private string $envelopeXdr;
    private string $resultXdr;
    private ?ExtrasResultCodes $resultCodes = null;

    /**
     * Gets the base64-encoded transaction envelope XDR
     *
     * Returns the XDR representation of the complete signed transaction envelope as it
     * was submitted to the network. This includes the transaction data, signatures, and
     * any fee-bump wrapper. Useful for debugging or resubmitting transactions.
     *
     * @return string The base64-encoded envelope XDR
     */
    public function getEnvelopeXdr(): string
    {
        return $this->envelopeXdr;
    }

    /**
     * Gets the base64-encoded transaction result XDR
     *
     * Returns the XDR representation of the transaction execution result. Contains the
     * result code, fee charged, and individual operation results. Essential for diagnosing
     * why a transaction failed.
     *
     * @return string The base64-encoded result XDR
     */
    public function getResultXdr(): string
    {
        return $this->resultXdr;
    }

    /**
     * Gets the human-readable result codes
     *
     * Returns detailed result codes for transaction and operation-level failures. Provides
     * easier error diagnosis than parsing result XDR manually. Null if result codes are
     * not included in the response.
     *
     * @return ExtrasResultCodes|null The result codes, or null if not available
     */
    public function getResultCodes(): ?ExtrasResultCodes
    {
        return $this->resultCodes;
    }

    /**
     * Loads extras data from JSON response
     *
     * @param array $json The JSON array containing submission extras data
     * @return void
     */
    protected function loadFromJson(array $json) : void {
        if (isset($json['envelope_xdr'])) $this->envelopeXdr = $json['envelope_xdr'];
        if (isset($json['result_xdr'])) $this->resultXdr = $json['result_xdr'];
        if (isset($json['result_codes'])) $this->resultCodes = ExtrasResultCodes::fromJson($json['result_codes']);
    }

    /**
     * Creates a SubmitTransactionResponseExtras instance from JSON data
     *
     * @param array $json The JSON array containing submission extras data from Horizon
     * @return SubmitTransactionResponseExtras The parsed submission extras
     */
    public static function fromJson(array $json) : SubmitTransactionResponseExtras
    {
        $result = new SubmitTransactionResponseExtras();
        $result->loadFromJson($json);
        return $result;
    }
}