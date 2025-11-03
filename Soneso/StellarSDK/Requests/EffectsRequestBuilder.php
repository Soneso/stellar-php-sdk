<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Requests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\Effects\EffectResponse;
use Soneso\StellarSDK\Responses\Effects\EffectsPageResponse;

/**
 * Builds requests for the effects endpoint in Horizon
 *
 * This class provides methods to query effects on the Stellar network. Effects represent
 * specific changes that occur in the ledger as a result of successful operations. Each
 * operation can produce multiple effects (e.g., a payment operation produces a debit
 * effect for the sender and a credit effect for the receiver).
 *
 * Query Methods:
 * - forAccount(): Get effects for a specific account
 * - forLedger(): Get effects in a specific ledger
 * - forTransaction(): Get effects from a specific transaction
 * - forOperation(): Get effects from a specific operation
 * - forLiquidityPool(): Get effects for a specific liquidity pool
 *
 * Effects provide a detailed view of ledger changes and are useful for building
 * account activity feeds and transaction history displays.
 *
 * Usage Examples:
 *
 * // Get recent effects for an account
 * $effects = $sdk->effects()
 *     ->forAccount("GDAT5...")
 *     ->limit(20)
 *     ->order("desc")
 *     ->execute();
 *
 * // Stream real-time effects
 * $sdk->effects()
 *     ->cursor("now")
 *     ->stream(function(EffectResponse $effect) {
 *         echo "Effect: " . $effect->getType() . PHP_EOL;
 *     });
 *
 * // Get effects for a specific operation
 * $effects = $sdk->effects()
 *     ->forOperation("123456789")
 *     ->execute();
 *
 * @package Soneso\StellarSDK\Requests
 * @see EffectsPageResponse For the response format
 * @see https://developers.stellar.org/api/resources/effects Horizon API Effects endpoint
 */
class EffectsRequestBuilder extends RequestBuilder
{
    /**
     * Constructor
     *
     * @param Client $httpClient The HTTP client used for making requests to Horizon
     */
    public function __construct(Client $httpClient) {
        parent::__construct($httpClient, "effects");
    }

    /**
     * Builds request to <code>GET /accounts/{account}/effects</code>
     * @param string $accountId ID of the account for which to get effects.
     * @return EffectsRequestBuilder
     * @see https://developers.stellar.org/api/resources/accounts/effects/ Effects for Account
     */
    public function forAccount(string $accountId) : EffectsRequestBuilder {
        $this->setSegments("accounts", $accountId, "effects");
        return $this;
    }

    /**
     * Builds request to <code>GET /ledgers/{ledgerSeq}/effects</code>
     * @param string $ledgerSeq Ledger for which to get effects.
     * @return EffectsRequestBuilder
     * @see https://developers.stellar.org/api/resources/ledgers/effects/ Effects for Ledger
     */
    public function forLedger(string $ledgerSeq) : EffectsRequestBuilder {
        $this->setSegments("ledgers", $ledgerSeq, "effects");
        return $this;
    }


    /**
     * Builds request to <code>GET /transactions/{transactionId}/effects</code>
     * @param string $transactionId Transaction ID for which to get effects.
     * @return EffectsRequestBuilder
     * @see https://developers.stellar.org/api/resources/transactions/effects/ Effect for Transaction
     */
    public function forTransaction(string $transactionId) : EffectsRequestBuilder {
        $this->setSegments("transactions", $transactionId, "effects");
        return $this;
    }

    /**
     * Builds request to <code>GET /liquidity_pools/{poolID}/effects</code>
     * @param string $liquidityPoolId Liquidity pool for which to get effects.
     * @return EffectsRequestBuilder
     * @see https://developers.stellar.org/api/resources/liquiditypools/effects/ Effects for Liquidity Pool
     */
    public function forLiquidityPool(string $liquidityPoolId) : EffectsRequestBuilder {
        $idHex = $liquidityPoolId;
        if (str_starts_with($idHex, "L")) {
            $idHex = StrKey::decodeLiquidityPoolIdHex($idHex);
        }
        $this->setSegments("liquidity_pools", $idHex, "effects");
        return $this;
    }
    /**
     * Builds request to <code>GET /operation/{operationId}/effects</code>
     * @param string $operationId ID of operation for which to get effects.
     * @return EffectsRequestBuilder
     * @see https://developers.stellar.org/api/resources/operations/effects/ Effect for Operation
     */
    public function forOperation(string $operationId) : EffectsRequestBuilder {
        $this->setSegments("operations", $operationId, "effects");
        return $this;
    }

    /**
     * Sets <code>cursor</code> parameter on the request.
     * A cursor is a value that points to a specific location in a collection of resources.
     * The cursor attribute itself is an opaque value meaning that users should not try to parse it.
     * @see https://developers.stellar.org/api/introduction/pagination/ Page documentation
     * @param string $cursor
     */
    public function cursor(string $cursor) : EffectsRequestBuilder {
        return parent::cursor($cursor);
    }

    /**
     * Sets <code>limit</code> parameter on the request.
     * It defines maximum number of records to return.
     * For range and default values check documentation of the endpoint requested.
     * @param int $number Maximum number of records to return
     */
    public function limit(int $number) : EffectsRequestBuilder {
        return parent::limit($number);
    }

    /**
     * Sets <code>order</code> parameter on the request.
     * @param string $direction "asc" or "desc"
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