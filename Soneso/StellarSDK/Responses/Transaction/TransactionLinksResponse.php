<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Transaction;

use Soneso\StellarSDK\Responses\Link\LinkResponse;

/**
 * Represents HAL-style hypermedia links for transaction resources
 *
 * This response provides navigational links to related resources for a transaction following
 * the HAL (Hypertext Application Language) specification. Links enable clients to discover
 * and traverse related resources without constructing URLs manually.
 *
 * Available links include:
 * - self: The transaction itself
 * - account: The source account that created the transaction
 * - ledger: The ledger that includes this transaction
 * - operations: Operations contained in this transaction
 * - effects: Effects produced by this transaction
 * - precedes: The next transaction in chronological order
 * - succeeds: The previous transaction in chronological order
 * - transaction: Reference to the transaction resource (for fee-bump inner transactions)
 *
 * All links are templated URLs that can be followed to retrieve the related resource.
 *
 * @package Soneso\StellarSDK\Responses\Transaction
 * @see TransactionResponse For the parent transaction response
 * @see LinkResponse For individual link details
 * @see https://developers.stellar.org/api/introduction/response-format HAL Response Format
 * @since 1.0.0
 */
class TransactionLinksResponse
{

    private LinkResponse $account;
    private LinkResponse $ledger;
    private LinkResponse $operations;
    private LinkResponse $self;
    private LinkResponse $effects;
    private LinkResponse $precedes;
    private LinkResponse $succeeds;
    private LinkResponse $transaction;

    /**
     * Loads hypermedia links from JSON response
     *
     * @param array $json The JSON array containing HAL links data
     * @return void
     */
    protected function loadFromJson(array $json) : void {


        if (isset($json['account'])) $this->account = LinkResponse::fromJson($json['account']);
        if (isset($json['ledger'])) $this->account = LinkResponse::fromJson($json['ledger']);
        if (isset($json['operations'])) $this->operations = LinkResponse::fromJson($json['operations']);
        if (isset($json['self'])) $this->self = LinkResponse::fromJson($json['self']);
        if (isset($json['effects'])) $this->effects = LinkResponse::fromJson($json['effects']);
        if (isset($json['precedes'])) $this->precedes = LinkResponse::fromJson($json['precedes']);
        if (isset($json['succeeds'])) $this->succeeds = LinkResponse::fromJson($json['succeeds']);
        if (isset($json['transaction'])) $this->transaction = LinkResponse::fromJson($json['transaction']);
    }

    /**
     * Creates a TransactionLinksResponse instance from JSON data
     *
     * @param array $json The JSON array containing HAL links data from Horizon
     * @return TransactionLinksResponse The parsed transaction links response
     */
    public static function fromJson(array $json) : TransactionLinksResponse {
        $result = new TransactionLinksResponse();
        $result->loadFromJson($json);
        return $result;
    }
}