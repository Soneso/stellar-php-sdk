<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Requests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\Operations\OperationResponse;
use Soneso\StellarSDK\Responses\Operations\OperationsPageResponse;

/**
 * Builds requests for the operations endpoint in Horizon
 *
 * This class provides methods to query operations on the Stellar network. Operations
 * are individual actions within transactions, such as payments, account creation,
 * trustline management, and trades.
 *
 * Query Methods:
 * - forAccount(): Get operations for a specific account
 * - forLedger(): Get operations in a specific ledger
 * - forTransaction(): Get operations in a specific transaction
 * - forClaimableBalance(): Get operations affecting a claimable balance
 * - forLiquidityPool(): Get operations affecting a liquidity pool
 * - includeFailed(): Include operations from failed transactions
 * - includeTransactions(): Include full transaction data in responses
 *
 * Usage Examples:
 *
 * // Get a single operation
 * $operation = $sdk->operations()->operation("123456789");
 *
 * // Get operations for an account
 * $operations = $sdk->operations()
 *     ->forAccount("GDAT5...")
 *     ->limit(50)
 *     ->order("desc")
 *     ->execute();
 *
 * // Get operations with transaction data
 * $operations = $sdk->operations()
 *     ->forAccount("GDAT5...")
 *     ->includeTransactions(true)
 *     ->execute();
 *
 * // Stream real-time operations
 * $sdk->operations()
 *     ->cursor("now")
 *     ->stream(function($op) {
 *         echo "Operation type: " . $op->getType();
 *     });
 *
 * @package Soneso\StellarSDK\Requests
 * @see https://developers.stellar.org Stellar developer docs Operations API documentation
 */
class OperationsRequestBuilder extends RequestBuilder
{
    private const INCLUDE_FAILED_PARAMETER_NAME = "include_failed";
    private const JOIN_PARAMETER_NAME = "join";


    /**
     * Constructs a new OperationsRequestBuilder instance
     *
     * @param Client $httpClient The Guzzle HTTP client for making requests
     */
    public function __construct(Client $httpClient)
    {
        parent::__construct($httpClient, "operations");
    }

    /**
     * Fetches a single operation by its ID
     *
     * Requests GET /operations/{operationId}
     *
     * @param string $operationId The operation ID to fetch
     * @return OperationResponse The operation details with type-specific fields
     * @throws HorizonRequestException If the operation does not exist or request fails
     */
    public function operation(string $operationId) : OperationResponse {
        $this->setSegments("operations", $operationId);
        return parent::executeRequest($this->buildUrl(),RequestType::SINGLE_OPERATION);
    }

    /**
     * Filters operations to those affecting a specific account
     *
     * Returns operations where the specified account is involved as source,
     * destination, or affected by the operation.
     *
     * Builds request to GET /accounts/{account}/operations
     *
     * @param string $accountId Public key of the account to filter by (G-address)
     * @return OperationsRequestBuilder This instance for method chaining
     * @see https://developers.stellar.org Stellar developer docs Operations for Account
     */
    public function forAccount(string $accountId) : OperationsRequestBuilder {
        $this->setSegments("accounts", $accountId, "operations");
        return $this;
    }

    /**
     * Filters operations to those affecting a specific claimable balance
     *
     * Returns operations that created, claimed, or clawed back the specified
     * claimable balance.
     *
     * Builds request to GET /claimable_balances/{claimable_balance_id}/operations
     *
     * @param string $claimableBalanceId ID of the claimable balance (B-address or hex format)
     * @return OperationsRequestBuilder This instance for method chaining
     * @see https://developers.stellar.org Stellar developer docs Operations for ClaimableBalance
     */
    public function forClaimableBalance(string $claimableBalanceId) : OperationsRequestBuilder {
        $idHex = $claimableBalanceId;
        if (str_starts_with($idHex, "B")) {
            $idHex = StrKey::decodeClaimableBalanceIdHex($idHex);
        }
        $this->setSegments("claimable_balances", $idHex, "operations");
        return $this;
    }

    /**
     * Filters operations to those in a specific ledger
     *
     * Returns all operations that were executed in the specified ledger.
     *
     * Builds request to GET /ledgers/{ledgerSeq}/operations
     *
     * @param string $ledgerSeq The ledger sequence number
     * @return OperationsRequestBuilder This instance for method chaining
     * @see https://developers.stellar.org Stellar developer docs Operations for Ledger
     */
    public function forLedger(string $ledgerSeq) : OperationsRequestBuilder {
        $this->setSegments("ledgers", $ledgerSeq, "operations");
        return $this;
    }

    /**
     * Filters operations to those in a specific transaction
     *
     * Returns all operations that are part of the specified transaction.
     *
     * Builds request to GET /transactions/{transactionId}/operations
     *
     * @param string $transactionId The transaction hash
     * @return OperationsRequestBuilder This instance for method chaining
     * @see https://developers.stellar.org Stellar developer docs Operations for Transaction
     */
    public function forTransaction(string $transactionId) : OperationsRequestBuilder {
        $this->setSegments("transactions", $transactionId, "operations");
        return $this;
    }

    /**
     * Filters operations to those affecting a specific liquidity pool
     *
     * Returns operations that deposited to, withdrew from, or traded with
     * the specified liquidity pool.
     *
     * Builds request to GET /liquidity_pools/{poolID}/operations
     *
     * @param string $liquidityPoolId The liquidity pool ID (L-address or hex format)
     * @return OperationsRequestBuilder This instance for method chaining
     * @see https://developers.stellar.org Stellar developer docs Operations for Liquidity Pool
     */
    public function forLiquidityPool(string $liquidityPoolId) : OperationsRequestBuilder {
        $idHex = $liquidityPoolId;
        if (str_starts_with($idHex, "L")) {
            $idHex = StrKey::decodeLiquidityPoolIdHex($idHex);
        }
        $this->setSegments("liquidity_pools", $idHex, "operations");
        return $this;
    }

    /**
     * Includes operations from failed transactions in the results
     *
     * By default, only operations from successful transactions are returned.
     * Set this to true to also include operations from transactions that failed.
     *
     * @param bool $value Set to true to include failed operations, false to exclude them
     * @return OperationsRequestBuilder This instance for method chaining
     */
    public function includeFailed(bool $value) : OperationsRequestBuilder {
        $this->queryParameters[OperationsRequestBuilder::INCLUDE_FAILED_PARAMETER_NAME] = $value ? "true" : "false";
        return $this;
    }

    /**
     * Includes full transaction data in operation responses
     *
     * By default, operation responses only contain a link to the transaction.
     * Set this to true to embed the complete transaction data in each operation response.
     *
     * @param bool $include Set to true to include transaction data, false to exclude
     * @return OperationsRequestBuilder This instance for method chaining
     */
    public function includeTransactions(bool $include) : OperationsRequestBuilder {
        if ($include) {
            $this->queryParameters[OperationsRequestBuilder::JOIN_PARAMETER_NAME] = "transactions";
        }  else if (array_key_exists(OperationsRequestBuilder::JOIN_PARAMETER_NAME, $this->queryParameters)) {
            unset($this->queryParameters[OperationsRequestBuilder::JOIN_PARAMETER_NAME]);
        }
        return $this;
    }

    /**
     * Sets the cursor position for pagination
     *
     * A cursor is an opaque value that points to a specific location in a result set.
     * Use "now" as the cursor to stream only new operations.
     *
     * @param string $cursor The paging token from a previous response or "now"
     * @return OperationsRequestBuilder This instance for method chaining
     * @see https://developers.stellar.org Stellar developer docs Pagination documentation
     */
    public function cursor(string $cursor) : OperationsRequestBuilder {
        return parent::cursor($cursor);
    }

    /**
     * Sets the maximum number of operations to return
     *
     * Defines the maximum number of records in the response. Maximum allowed is typically 200.
     *
     * @param int $number Maximum number of operations to return (1-200)
     * @return OperationsRequestBuilder This instance for method chaining
     */
    public function limit(int $number) : OperationsRequestBuilder {
        return parent::limit($number);
    }

    /**
     * Sets the sort order for results
     *
     * Determines whether results are sorted by operation ID in ascending or descending order.
     *
     * @param string $direction Sort direction: "asc" for ascending, "desc" for descending
     * @return OperationsRequestBuilder This instance for method chaining
     */
    public function order(string $direction = "asc") : OperationsRequestBuilder {
        return parent::order($direction);
    }

    /**
     * Requests a specific URL and returns paginated operations
     *
     * This method is typically used internally for pagination. Use execute() instead
     * for normal queries.
     *
     * @param string $url The complete URL to request
     * @return OperationsPageResponse Paginated list of operations
     * @throws HorizonRequestException If the request fails
     */
    public function request(string $url): OperationsPageResponse {
        return parent::executeRequest($url, RequestType::OPERATIONS_PAGE);
    }

    /**
     * Builds the query URL and executes the request
     *
     * Combines all query parameters and filters to build the final URL, then
     * executes the request and returns paginated operation results.
     *
     * @return OperationsPageResponse Paginated list of operations matching the query
     * @throws HorizonRequestException If the request fails
     */
    public function execute() : OperationsPageResponse {
        return $this->request($this->buildUrl());
    }

    /**
     * Streams real-time operation updates to a callback function
     *
     * This method establishes a persistent connection to Horizon and streams
     * new operations as they occur on the network. The callback is invoked
     * for each new operation.
     *
     * Use cursor("now") before calling stream() to only receive new operations
     * and skip historical ones.
     *
     * Example:
     * $sdk->operations()->cursor("now")->stream(function(OperationResponse $operation) {
     *     printf("Operation ID: %s, Type: %s\n", $operation->getId(), $operation->getType());
     * });
     *
     * @param callable|null $callback Function to receive OperationResponse objects
     * @return void This method runs indefinitely until interrupted
     * @throws GuzzleException If the streaming connection fails
     */
    public function stream(callable $callback = null)
    {
        $this->getAndStream($this->buildUrl(), function($rawData) use ($callback) {
            $parsedObject = OperationResponse::fromJson($rawData);
            $callback($parsedObject);
        });
    }
}
