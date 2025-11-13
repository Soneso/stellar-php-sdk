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

/**
 * Builds requests for the ledgers endpoint in Horizon
 *
 * This class provides methods to query ledgers on the Stellar network. Ledgers represent
 * the state of the Stellar universe at a specific point in time. They close approximately
 * every 5 seconds and contain all transactions, operations, and effects that occurred
 * during that period.
 *
 * Query Methods:
 * - ledger(): Fetch a single ledger by sequence number
 * - stream(): Stream ledgers in real-time as they close
 *
 * Ledgers are the fundamental unit of time in Stellar and are essential for understanding
 * the network's history and current state.
 *
 * Usage Examples:
 *
 * // Get a specific ledger by sequence number
 * $ledger = $sdk->ledgers()->ledger("123456");
 *
 * // Get recent ledgers with pagination
 * $ledgers = $sdk->ledgers()
 *     ->limit(20)
 *     ->order("desc")
 *     ->execute();
 *
 * // Stream ledgers in real-time
 * $sdk->ledgers()
 *     ->cursor("now")
 *     ->stream(function(LedgerResponse $ledger) {
 *         echo "Ledger closed: " . $ledger->getSequence() . PHP_EOL;
 *     });
 *
 * @package Soneso\StellarSDK\Requests
 * @see LedgersPageResponse For the response format
 * @see https://developers.stellar.org Stellar developer docs Horizon API Ledgers endpoint
 */
class LedgersRequestBuilder extends RequestBuilder
{
    /**
     * Constructor
     *
     * @param Client $httpClient The HTTP client used for making requests to Horizon
     */
    public function __construct(Client $httpClient)
    {
        parent::__construct($httpClient, "ledgers");
    }

    /**
     * Requests <code>GET /ledgers/{ledgerSequence}</code>
     * @param string $ledgerSequence (ledger_id) of the ledger to fetch
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
     * @see https://developers.stellar.org Stellar developer docs Page documentation
     * @param string $cursor
     */
    public function cursor(string $cursor) : LedgersRequestBuilder {
        return parent::cursor($cursor);
    }

    /**
     * Sets <code>limit</code> parameter on the request.
     * It defines maximum number of records to return.
     * For range and default values check documentation of the endpoint requested.
     * @param int $number Maximum number of records to return
     */
    public function limit(int $number) : LedgersRequestBuilder {
        return parent::limit($number);
    }

    /**
     * Sets <code>order</code> parameter on the request.
     * @param string $direction "asc" or "desc"
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