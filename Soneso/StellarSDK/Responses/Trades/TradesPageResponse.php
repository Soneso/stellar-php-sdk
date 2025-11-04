<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Trades;

use Soneso\StellarSDK\Requests\RequestType;
use Soneso\StellarSDK\Responses\Page\PageResponse;

/**
 * Paginated collection of trades from Horizon API
 *
 * This response represents a single page of executed trades returned by Horizon's trade
 * endpoints. Each page contains a collection of trade records representing completed exchanges
 * on the Stellar distributed exchange (DEX), along with pagination links to navigate forward
 * and backward through the complete result set.
 *
 * Trades represent executed transactions where one asset was exchanged for another, including
 * the accounts involved, assets traded, amounts, price, and timestamp. The response follows
 * Horizon's standard pagination pattern with cursor-based navigation.
 *
 * Returned by Horizon endpoints:
 * - GET /trades - All trades on the DEX
 * - GET /accounts/{account_id}/trades - Trades involving an account
 * - GET /liquidity_pools/{liquidity_pool_id}/trades - Trades involving a liquidity pool
 * - GET /offers/{offer_id}/trades - Trades that filled a specific offer
 *
 * @package Soneso\StellarSDK\Responses\Trades
 * @see PageResponse For pagination functionality
 * @see TradesResponse For the collection of trades in this page
 * @see TradeResponse For individual trade details
 * @see https://developers.stellar.org/api/resources/trades/list Horizon Trades List API
 * @see https://developers.stellar.org/api/introduction/pagination Horizon Pagination
 */
class TradesPageResponse extends PageResponse
{
    private TradesResponse $trades;

    /**
     * Gets the collection of trades in this page
     *
     * @return TradesResponse The iterable collection of trade records
     */
    public function getTrades(): TradesResponse {
        return $this->trades;
    }

    protected function loadFromJson(array $json) : void {
        parent::loadFromJson($json);
        if (isset($json['_embedded']['records'])) {
            $this->trades = new TradesResponse();
            foreach ($json['_embedded']['records'] as $jsonTrade) {
                $account = TradeResponse::fromJson($jsonTrade);
                $this->trades->add($account);
            }
        }
    }

    /**
     * Creates a TradesPageResponse from JSON data
     *
     * @param array $json Associative array of parsed JSON response
     * @return TradesPageResponse The populated page response
     */
    public static function fromJson(array $json) : TradesPageResponse {
        $result = new TradesPageResponse();
        $result->loadFromJson($json);
        return $result;
    }

    /**
     * Fetches the next page of trades
     *
     * @return TradesPageResponse|null The next page or null if no next page exists
     */
    public function getNextPage(): TradesPageResponse | null {
        return $this->executeRequest(RequestType::TRADES_PAGE, $this->getNextPageUrl());
    }

    /**
     * Fetches the previous page of trades
     *
     * @return TradesPageResponse|null The previous page or null if no previous page exists
     */
    public function getPreviousPage(): TradesPageResponse | null {
        return $this->executeRequest(RequestType::TRADES_PAGE, $this->getPrevPageUrl());
    }
}