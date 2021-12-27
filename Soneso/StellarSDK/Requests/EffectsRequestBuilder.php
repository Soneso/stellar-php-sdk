<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Requests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\Effects\EffectResponse;
use Soneso\StellarSDK\Responses\Effects\EffectsPageResponse;

class EffectsRequestBuilder extends RequestBuilder
{
    public function __construct(Client $httpClient) {
        parent::__construct($httpClient, "effects");
    }

    /**
     * Builds request to <code>GET /accounts/{account}/effects</code>
     * @param string $accountId ID of the account for which to get effects.
     * @return EffectsRequestBuilder
     * @see <a href="https://developers.stellar.org/api/resources/accounts/effects/">Effects for Account</a>
     */
    public function forAccount(string $accountId) : EffectsRequestBuilder {
        $this->setSegments("accounts", $accountId, "effects");
        return $this;
    }

    /**
     * Builds request to <code>GET /ledgers/{ledgerSeq}/effects</code>
     * @param string $ledgerSeq Ledger for which to get effects.
     * @return EffectsRequestBuilder
     * @see <a href="https://developers.stellar.org/api/resources/ledgers/effects/">Effects for Ledger</a>
     */
    public function forLedger(string $ledgerSeq) : EffectsRequestBuilder {
        $this->setSegments("ledgers", $ledgerSeq, "effects");
        return $this;
    }


    /**
     * Builds request to <code>GET /transactions/{transactionId}/effects</code>
     * @param string $transactionId Transaction ID for which to get effects.
     * @return EffectsRequestBuilder
     * @see <a href="https://developers.stellar.org/api/resources/transactions/effects/">Effect for Transaction</a>
     */
    public function forTransaction(string $transactionId) : EffectsRequestBuilder {
        $this->setSegments("transactions", $transactionId, "effects");
        return $this;
    }

    /**
     * Builds request to <code>GET /liquidity_pools/{poolID}/effects</code>
     * @param string $liquidityPoolId  Liquidity pool for which to get effects.
     * @return EffectsRequestBuilder
     * @see <a href="https://developers.stellar.org/api/resources/liquiditypools/effects/">Effects for Liquidity Pool</a>
     */
    public function forLiquidityPool(string $liquidityPoolId) : EffectsRequestBuilder {
        $this->setSegments("liquidity_pools", $liquidityPoolId, "effects");
        return $this;
    }
    /**
     * Builds request to <code>GET /operation/{operationId}/effects</code>
     * @param string $operationId ID of operation for which to get effects.
     * @return EffectsRequestBuilder
     * @see @see <a href="https://developers.stellar.org/api/resources/operations/effects/">Effect for Operation</a>
     */
    public function forOperation(string $operationId) : EffectsRequestBuilder {
        $this->setSegments("operations", $operationId, "effects");
        return $this;
    }

    /**
     * Sets <code>cursor</code> parameter on the request.
     * A cursor is a value that points to a specific location in a collection of resources.
     * The cursor attribute itself is an opaque value meaning that users should not try to parse it.
     * @see <a href="https://developers.stellar.org/api/introduction/pagination/">Page documentation</a>
     * @param string cursor
     */
    public function cursor(string $cursor) : EffectsRequestBuilder {
        return parent::cursor($cursor);
    }

    /**
     * Sets <code>limit</code> parameter on the request.
     * It defines maximum number of records to return.
     * For range and default values check documentation of the endpoint requested.
     * @param int number maximum number of records to return
     */
    public function limit(int $number) : EffectsRequestBuilder {
        return parent::limit($number);
    }

    /**
     * Sets <code>order</code> parameter on the request.
     * @param string direction "asc" or "desc"
     */
    public function order(string $direction = "asc") : EffectsRequestBuilder {
        return parent::order($direction);
    }
    /**
     * Requests specific <code>url</code> and returns {@link EffectsPageResponse}.
     * @throws HorizonRequestException
     */
    public function request(string $url): EffectsPageResponse {
        return parent::executeRequest($url, RequestType::EFFECTS_PAGE);
    }

    /**
     *  Build and execute request.
     *  @throws HorizonRequestException
     */
    public function execute() : EffectsPageResponse {
        return $this->request($this->buildUrl());
    }

    /**
     * Streams Effect objects to $callback
     *
     * $callback should have arguments:
     *  EffectResponse
     *
     * For example:
     *
     * $sdk = StellarSDK::getTestNetInstance();
     * $sdk->effects()->cursor("now")->stream(function(EffectResponse $effect) {
     * printf('Effect type: %s' . PHP_EOL, $effect->getEffectType());
     * });
     *
     * @param callable|null $callback
     * @throws GuzzleException
     */
    public function stream(callable $callback = null)
    {
        $this->getAndStream($this->buildUrl(), function($rawData) use ($callback) {
            $parsedObject = EffectResponse::fromJson($rawData);
            $callback($parsedObject);
        });
    }
}