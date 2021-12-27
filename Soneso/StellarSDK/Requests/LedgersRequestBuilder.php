<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Requests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\Ledger\LedgerResponse;
use Soneso\StellarSDK\Responses\Ledger\LedgersPageResponse;

class LedgersRequestBuilder extends RequestBuilder
{
    public function __construct(Client $httpClient)
    {
        parent::__construct($httpClient, "ledgers");
    }

    /**
     * Requests <code>GET /ledgers/{ledgerSequence}</code>
     * @param string sequence (ledger_id) of the ledger to fetch
     * @throws HorizonRequestException
     */
    public function ledger(string $ledgerSequence): LedgerResponse
    {
        $this->setSegments("ledgers", $ledgerSequence);
        return parent::executeRequest($this->buildUrl(), RequestType::SINGLE_LEDGER);
    }

    /**
     * Sets <code>cursor</code> parameter on the request.
     * A cursor is a value that points to a specific location in a collection of resources.
     * The cursor attribute itself is an opaque value meaning that users should not try to parse it.
     * @see <a href="https://developers.stellar.org/api/introduction/pagination/">Page documentation</a>
     * @param string cursor
     */
    public function cursor(string $cursor) : LedgersRequestBuilder {
        return parent::cursor($cursor);
    }

    /**
     * Sets <code>limit</code> parameter on the request.
     * It defines maximum number of records to return.
     * For range and default values check documentation of the endpoint requested.
     * @param int number maximum number of records to return
     */
    public function limit(int $number) : LedgersRequestBuilder {
        return parent::limit($number);
    }

    /**
     * Sets <code>order</code> parameter on the request.
     * @param string direction "asc" or "desc"
     */
    public function order(string $direction = "asc") : LedgersRequestBuilder {
        return parent::order($direction);
    }

    /**
     * Requests specific <code>url</code> and returns {@link LedgersPageResponse}.
     * @throws HorizonRequestException
     */
    public function request(string $url): LedgersPageResponse {
        return parent::executeRequest($url, RequestType::LEDGERS_PAGE);
    }

    /**
     *  Build and execute request.
     *  @throws HorizonRequestException
     */
    public function execute() : LedgersPageResponse {
        return $this->request($this->buildUrl());
    }

    /**
     * Streams Ledger objects to $callback
     *
     * $callback should have arguments:
     *  LedgerResponse
     *
     * For example:
     *
     * $sdk = StellarSDK::getTestNetInstance();
     * $sdk->ledgers()->cursor("now")->stream(function(LedgerResponse $ledger) {
     * printf('Ledger closed at: %s' . PHP_EOL, $ledger->getCreatedAt());
     * });
     *
     * @param callable|null $callback
     * @throws GuzzleException
     */
    public function stream(callable $callback = null)
    {
        $this->getAndStream($this->buildUrl(), function($rawData) use ($callback) {
            $parsedObject = LedgerResponse::fromJson($rawData);
            $callback($parsedObject);
        });
    }
}