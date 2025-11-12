<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

use Soneso\StellarSDK\Responses\Response;

/**
 * Response containing a single SEP-24 transaction details
 *
 * This class represents the response from the /transaction endpoint when
 * querying for a specific transaction by ID. It wraps the SEP24Transaction
 * object and provides access to all transaction details including status,
 * amounts, and timestamps.
 *
 * @package Soneso\StellarSDK\SEP\Interactive
 * @see https://github.com/stellar/stellar-protocol/blob/v3.8.0/ecosystem/sep-0024.md SEP-24 Specification
 * @see SEP24Transaction For the transaction data structure
 * @see InteractiveService::transaction() For retrieving transaction details
 */
class SEP24TransactionResponse extends Response {

    /**
     * @var SEP24Transaction The parsed transaction from the anchor response.
     */
    public SEP24Transaction $transaction;

    /**
     * Loads the needed data from a json array.
     * @param array<array-key, mixed> $json the data array to read from.
     * @return void
     */
    protected function loadFromJson(array $json) : void {
        if (isset($json['transaction'])) {
            $this->transaction = SEP24Transaction::fromJson($json['transaction']);
        }
    }

    /**
     * Constructs a new instance of SEP24TransactionResponse by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return SEP24TransactionResponse the object containing the parsed data.
     */
    public static function fromJson(array $json) : SEP24TransactionResponse
    {
        $result = new SEP24TransactionResponse();
        $result->loadFromJson($json);
        return $result;
    }

    /**
     * @return SEP24Transaction The parsed transaction from the anchor response.
     */
    public function getTransaction(): SEP24Transaction
    {
        return $this->transaction;
    }

    /**
     * @param SEP24Transaction $transaction The parsed transaction from the anchor response.
     * @return void
     */
    public function setTransaction(SEP24Transaction $transaction): void
    {
        $this->transaction = $transaction;
    }
}