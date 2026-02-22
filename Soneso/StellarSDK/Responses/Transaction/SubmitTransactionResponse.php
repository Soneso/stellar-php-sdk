<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Transaction;

use Soneso\StellarSDK\Xdr\XdrTransactionResultCode;

/**
 * Represents the response from synchronous transaction submission
 *
 * This response extends TransactionResponse and is returned when submitting a transaction
 * to Horizon via POST /transactions. It includes all transaction details plus submission-specific
 * information such as result codes and XDR data for failed transactions.
 *
 * The response indicates whether the transaction succeeded or failed:
 * - Success: Transaction was validated, included in a ledger, and all operations executed successfully
 * - Failure: Transaction validation or execution failed with detailed error codes in extras
 *
 * Use isSuccessful() to check transaction outcome. For failures, examine extras.resultCodes
 * to identify the specific transaction or operation error that occurred.
 *
 * For fee-bump transactions, success is determined by checking both the outer result code
 * (FEE_BUMP_INNER_SUCCESS) and the inner transaction result code (SUCCESS).
 *
 * @package Soneso\StellarSDK\Responses\Transaction
 * @see TransactionResponse For base transaction response fields
 * @see SubmitTransactionResponseExtras For submission extras including result codes
 * @see SubmitAsyncTransactionResponse For asynchronous transaction submission
 * @see https://developers.stellar.org Stellar developer docs Submit Transaction
 * @since 1.0.0
 */
class SubmitTransactionResponse extends TransactionResponse
{

    private ?SubmitTransactionResponseExtras $extras = null;


    /**
     * Loads submission response data from JSON
     *
     * @param array $json The JSON array containing transaction submission response data
     * @return void
     */
    protected function loadFromJson(array $json) : void {
        if (isset($json['extras'])) $this->extras = SubmitTransactionResponseExtras::fromJson($json['extras']);
        parent::loadFromJson($json);
    }

    /**
     * Creates a SubmitTransactionResponse instance from JSON data
     *
     * @param array $json The JSON array containing transaction submission response data from Horizon
     * @return SubmitTransactionResponse The parsed transaction submission response
     */
    public static function fromJson(array $json) : SubmitTransactionResponse
    {
        $result = new SubmitTransactionResponse();
        $result->loadFromJson($json);
        return $result;
    }

    /**
     * Checks if the transaction submission was successful
     *
     * Returns true if the transaction was successfully included in the ledger and all
     * operations executed successfully. For regular transactions, checks for SUCCESS result code.
     * For fee-bump transactions, verifies both the outer transaction (FEE_BUMP_INNER_SUCCESS)
     * and inner transaction (SUCCESS) succeeded.
     *
     * @return bool True if transaction succeeded, false otherwise
     */
    public function isSuccessful() : bool {
        $result = $this->getResultXdr();
        if ($result->result->resultCode->getValue() == XdrTransactionResultCode::SUCCESS) {
            return true;
        } else if ($result->result->resultCode->getValue() == XdrTransactionResultCode::FEE_BUMP_INNER_SUCCESS
            && $result->result->innerResultPair !== null) {
            $innerResultPair = $result->result->innerResultPair;
            if ($innerResultPair->result->result->resultCode->getValue() == XdrTransactionResultCode::SUCCESS) {
                return true;
            }
        }
        return false;
    }

    /**
     * Gets the submission extras containing result codes and XDR data
     *
     * Returns additional submission details including envelope XDR, result XDR, and
     * detailed result codes for failed transactions. Null for successful transactions
     * or when extras are not included in the response.
     *
     * @return SubmitTransactionResponseExtras|null The submission extras, or null
     */
    public function getExtras(): ?SubmitTransactionResponseExtras
    {
        return $this->extras;
    }

    /**
     * Sets the submission extras
     *
     * @param SubmitTransactionResponseExtras|null $extras The submission extras to set
     * @return void
     */
    public function setExtras(?SubmitTransactionResponseExtras $extras): void
    {
        $this->extras = $extras;
    }
}