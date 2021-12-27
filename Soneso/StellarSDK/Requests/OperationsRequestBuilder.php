<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Requests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\Operations\OperationResponse;
use Soneso\StellarSDK\Responses\Operations\OperationsPageResponse;

class OperationsRequestBuilder extends RequestBuilder
{
    private const INCLUDE_FAILED_PARAMETER_NAME = "include_failed";
    private const JOIN_PARAMETER_NAME = "join";


    public function __construct(Client $httpClient)
    {
        parent::__construct($httpClient, "operations");
    }

    /**
     * The operation details endpoint provides information on a single operation.
     *
     * @param string $operationId the id of the operation to fetch the details for.
     * @return OperationResponse The operation details.
     * @throws HorizonRequestException
     */
    public function operation(string $operationId) : OperationResponse {
        $this->setSegments("operations", $operationId);
        return parent::executeRequest($this->buildUrl(),RequestType::SINGLE_OPERATION);
    }

    /**
     * Builds request to <code>GET /accounts/{account}/operations</code>
     * @param string $accountId ID of the account for which to get operations.
     * @return OperationsRequestBuilder
     * @see <a href="https://developers.stellar.org/api/resources/accounts/operations/">Operations for Account</a>
     */
    public function forAccount(string $accountId) : OperationsRequestBuilder {
        $this->setSegments("accounts", $accountId, "operations");
        return $this;
    }

    /**
     * Builds request to <code>GET /claimable_balances/{claimable_balance_id}/operations</code>
     * @param string $claimableBalanceId ID of Claimable Balance for which to get operations.
     * @return OperationsRequestBuilder
     * @see <a href="https://developers.stellar.org/api/resources/claimablebalances/operations/">Operations for ClaimableBalance</a>
     */
    public function forClaimableBalance(string $claimableBalanceId) : OperationsRequestBuilder {
        $this->setSegments("claimable_balances", $claimableBalanceId, "operations");
        return $this;
    }

    /**
     * Builds request to <code>GET /ledgers/{ledgerSeq}/operations</code>
     * @param string $ledgerSeq Ledger for which to get operations.
     * @return OperationsRequestBuilder
     * @see <a href="https://developers.stellar.org/api/resources/ledgers/operations/">Operations for Ledger</a>
     */
    public function forLedger(string $ledgerSeq) : OperationsRequestBuilder {
        $this->setSegments("ledgers", $ledgerSeq, "operations");
        return $this;
    }

    /**
     * Builds request to <code>GET /transactions/{transactionId}/operations</code>
     * @param string $transactionId Transaction ID for which to get operations.
     * @return OperationsRequestBuilder
     * @see <a href="https://developers.stellar.org/api/resources/ledgers/transactions/">Operations for Transaction</a>
     */
    public function forTransaction(string $transactionId) : OperationsRequestBuilder {
        $this->setSegments("transactions", $transactionId, "operations");
        return $this;
    }

    /**
     * Builds request to <code>GET /liquidity_pools/{poolID}/operations</code>
     * @param string $liquidityPoolId  Liquidity pool for which to get operations.
     * @return OperationsRequestBuilder
     * @see <a href="https://developers.stellar.org/api/resources/liquiditypools/operations/">Operations for Liquidity Pool</a>
     */
    public function forLiquidityPool(string $liquidityPoolId) : OperationsRequestBuilder {
        $this->setSegments("liquidity_pools", $liquidityPoolId, "operations");
        return $this;
    }

    /**
     * Adds a parameter defining whether to include operations of failed transactions. By default, only operations of
     * successful transactions are returned.
     * @param bool $value  Set to <code>true</code> to include operations of failed transactions.
     * @return OperationsRequestBuilder
     */
    public function includeFailed(bool $value) : OperationsRequestBuilder {
        $this->queryParameters[OperationsRequestBuilder::INCLUDE_FAILED_PARAMETER_NAME] = $value ? "true" : "false";
        return $this;
    }

    /**
     * Adds a parameter defining whether to include transactions in the response. By default, transaction data
     * is not included.
     * @param bool $include  Set to <code>true</code> to include transaction data in the operation response.
     * @return OperationsRequestBuilder
     */
    public function includeTransactions(bool $include) : OperationsRequestBuilder {
        // TODO improve this to allow multiple, different joins as soon as needed.
        if ($include) {
            $this->queryParameters[OperationsRequestBuilder::JOIN_PARAMETER_NAME] = "transactions";
        }  else if (array_key_exists(OperationsRequestBuilder::JOIN_PARAMETER_NAME, $this->queryParameters)) {
            unset($this->queryParameters[OperationsRequestBuilder::JOIN_PARAMETER_NAME]);
        }
        return $this;
    }

    /**
     * Sets <code>cursor</code> parameter on the request.
     * A cursor is a value that points to a specific location in a collection of resources.
     * The cursor attribute itself is an opaque value meaning that users should not try to parse it.
     * @see <a href="https://developers.stellar.org/api/introduction/pagination/">Page documentation</a>
     * @param string cursor
     */
    public function cursor(string $cursor) : OperationsRequestBuilder {
        return parent::cursor($cursor);
    }

    /**
     * Sets <code>limit</code> parameter on the request.
     * It defines maximum number of records to return.
     * For range and default values check documentation of the endpoint requested.
     * @param int number maximum number of records to return
     */
    public function limit(int $number) : OperationsRequestBuilder {
        return parent::limit($number);
    }

    /**
     * Sets <code>order</code> parameter on the request.
     * @param string direction "asc" or "desc"
     */
    public function order(string $direction = "asc") : OperationsRequestBuilder {
        return parent::order($direction);
    }
    /**
     * Requests specific <code>url</code> and returns {@link OperationsPageResponse}.
     * @throws HorizonRequestException
     */
    public function request(string $url): OperationsPageResponse {
        return parent::executeRequest($url, RequestType::OPERATIONS_PAGE);
    }

    /**
     *  Build and execute request.
     *  @throws HorizonRequestException
     */
    public function execute() : OperationsPageResponse {
        return $this->request($this->buildUrl());
    }

    /**
     * Streams Operation objects to $callback
     *
     * $callback should have arguments:
     *  OperationResponse
     *
     * For example:
     *
     * $sdk = StellarSDK::getTestNetInstance();
     * $sdk->operations()->cursor("now")->stream(function(OperationResponse $operation) {
     * printf('Operation id %s' . PHP_EOL, $operation->getOperationId());
     * });
     *
     * @param callable|null $callback
     * @throws GuzzleException
     */
    public function stream(callable $callback = null)
    {
        $this->getAndStream($this->buildUrl(), function($rawData) use ($callback) {
            $parsedObject = OperationResponse::fromJson($rawData);
            $callback($parsedObject);
        });
    }
}