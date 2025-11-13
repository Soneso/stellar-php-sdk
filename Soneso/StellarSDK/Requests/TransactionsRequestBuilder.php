<?php

namespace Soneso\StellarSDK\Requests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\Transaction\TransactionResponse;
use Soneso\StellarSDK\Responses\Transaction\TransactionsPageResponse;

/**
 * Builds requests for the transactions endpoint in Horizon
 *
 * This class provides methods to query transactions on the Stellar network, including
 * fetching individual transactions, querying transactions for specific accounts, ledgers,
 * or liquidity pools, and streaming real-time transaction updates.
 *
 * Transactions represent operations that have been submitted to and processed by the
 * network. Each transaction contains one or more operations and is signed by one or
 * more accounts.
 *
 * Query Methods:
 * - forAccount(): Get transactions for a specific account
 * - forLedger(): Get transactions in a specific ledger
 * - forClaimableBalance(): Get transactions involving a claimable balance
 * - forLiquidityPool(): Get transactions involving a liquidity pool
 * - includeFailed(): Include failed transactions in results
 *
 * Usage Examples:
 *
 * // Get a single transaction
 * $tx = $sdk->transactions()->transaction("hash123...");
 *
 * // Get transactions for an account with pagination
 * $txs = $sdk->transactions()
 *     ->forAccount("GDAT5...")
 *     ->limit(20)
 *     ->order("desc")
 *     ->execute();
 *
 * // Include failed transactions
 * $txs = $sdk->transactions()
 *     ->forAccount("GDAT5...")
 *     ->includeFailed(true)
 *     ->execute();
 *
 * // Stream real-time transactions
 * $sdk->transactions()
 *     ->cursor("now")
 *     ->stream(function($tx) {
 *         echo "New transaction: " . $tx->getHash();
 *     });
 *
 * @package Soneso\StellarSDK\Requests
 * @see https://developers.stellar.org Stellar developer docs Transactions API documentation
 */
class TransactionsRequestBuilder extends RequestBuilder
{
    private const INCLUDE_FAILED_PARAMETER_NAME = "include_failed";

    /**
     * Constructs a new TransactionsRequestBuilder instance
     *
     * @param Client $httpClient The Guzzle HTTP client for making requests
     */
    public function __construct(Client $httpClient)
    {
        parent::__construct($httpClient, "transactions");
    }

    /**
     * Fetches a single transaction by its hash
     *
     * Requests GET /transactions/{transactionId}
     *
     * @param string $transactionId Hash of the transaction to fetch
     * @return TransactionResponse The transaction details including operations, memo, and XDR
     * @throws HorizonRequestException If the transaction does not exist or request fails
     */
    public function transaction(string $transactionId): TransactionResponse
    {
        $this->setSegments("transactions", $transactionId);
        return parent::executeRequest($this->buildUrl(), RequestType::SINGLE_TRANSACTION);
    }
    /**
     * Filters transactions to those affecting a specific account
     *
     * Returns transactions where the specified account is either the source account
     * or affected by one of the transaction's operations.
     *
     * Builds request to GET /accounts/{account}/transactions
     *
     * @param string $accountId Public key of the account to filter by (G-address)
     * @return TransactionsRequestBuilder This instance for method chaining
     * @see https://developers.stellar.org Stellar developer docs Transactions for Account
     */
    public function forAccount(string $accountId) : TransactionsRequestBuilder {
        $this->setSegments("accounts", $accountId, "transactions");
        return $this;
    }

    /**
     * Filters transactions to those affecting a specific claimable balance
     *
     * Returns transactions that created, claimed, or clawed back the specified
     * claimable balance.
     *
     * Builds request to GET /claimable_balances/{claimable_balance_id}/transactions
     *
     * @param string $claimableBalanceId ID of the claimable balance (B-address or hex format)
     * @return TransactionsRequestBuilder This instance for method chaining
     * @see https://developers.stellar.org Stellar developer docs Transactions for ClaimableBalance
     */
    public function forClaimableBalance(string $claimableBalanceId) : TransactionsRequestBuilder {
        $idHex = $claimableBalanceId;
        if (str_starts_with($idHex, "B")) {
            $idHex = StrKey::decodeClaimableBalanceIdHex($idHex);
        }
        $this->setSegments("claimable_balances", $idHex, "transactions");
        return $this;
    }

    /**
     * Filters transactions to those in a specific ledger
     *
     * Returns all transactions that were included in the specified ledger.
     *
     * Builds request to GET /ledgers/{ledgerSeq}/transactions
     *
     * @param string $ledgerSeq The ledger sequence number
     * @return TransactionsRequestBuilder This instance for method chaining
     * @see https://developers.stellar.org Stellar developer docs Transactions for Ledger
     */
    public function forLedger(string $ledgerSeq) : TransactionsRequestBuilder {
        $this->setSegments("ledgers", $ledgerSeq, "transactions");
        return $this;
    }

    /**
     * Filters transactions to those affecting a specific liquidity pool
     *
     * Returns transactions that deposited to, withdrew from, or traded with
     * the specified liquidity pool.
     *
     * Builds request to GET /liquidity_pools/{poolID}/transactions
     *
     * @param string $liquidityPoolId The liquidity pool ID (L-address or hex format)
     * @return TransactionsRequestBuilder This instance for method chaining
     * @see https://developers.stellar.org Stellar developer docs Transactions for Liquidity Pool
     */
    public function forLiquidityPool(string $liquidityPoolId) : TransactionsRequestBuilder {
        $idHex = $liquidityPoolId;
        if (str_starts_with($idHex, "L")) {
            $idHex = StrKey::decodeLiquidityPoolIdHex($idHex);
        }
        $this->setSegments("liquidity_pools", $idHex, "transactions");
        return $this;
    }

    /**
     * Includes failed transactions in the results
     *
     * By default, only successful transactions are returned. Set this to true to
     * also include transactions that failed during execution.
     *
     * @param bool $value Set to true to include failed transactions, false to exclude them
     * @return TransactionsRequestBuilder This instance for method chaining
     */
    public function includeFailed(bool $value) : TransactionsRequestBuilder {
        $this->queryParameters[TransactionsRequestBuilder::INCLUDE_FAILED_PARAMETER_NAME] = $value ? "true" : "false";
        return $this;
    }

    /**
     * Sets the cursor position for pagination
     *
     * A cursor is an opaque value that points to a specific location in a result set.
     * Use "now" as the cursor to stream only new transactions.
     *
     * @param string $cursor The paging token from a previous response or "now"
     * @return TransactionsRequestBuilder This instance for method chaining
     * @see https://developers.stellar.org Stellar developer docs Pagination documentation
     */
    public function cursor(string $cursor) : TransactionsRequestBuilder {
        return parent::cursor($cursor);
    }

    /**
     * Sets the maximum number of transactions to return
     *
     * Defines the maximum number of records in the response. Maximum allowed is typically 200.
     *
     * @param int $number Maximum number of transactions to return (1-200)
     * @return TransactionsRequestBuilder This instance for method chaining
     */
    public function limit(int $number) : TransactionsRequestBuilder {
        return parent::limit($number);
    }

    /**
     * Sets the sort order for results
     *
     * Determines whether results are sorted by ledger sequence in ascending or descending order.
     *
     * @param string $direction Sort direction: "asc" for ascending, "desc" for descending
     * @return TransactionsRequestBuilder This instance for method chaining
     */
    public function order(string $direction = "asc") : TransactionsRequestBuilder {
        return parent::order($direction);
    }

    /**
     * Requests a specific URL and returns paginated transactions
     *
     * This method is typically used internally for pagination. Use execute() instead
     * for normal queries.
     *
     * @param string $url The complete URL to request
     * @return TransactionsPageResponse Paginated list of transactions
     * @throws HorizonRequestException If the request fails
     */
    public function request(string $url): TransactionsPageResponse
    {
        return parent::executeRequest($url, RequestType::TRANSACTIONS_PAGE);
    }

    /**
     * Builds the query URL and executes the request
     *
     * Combines all query parameters and filters to build the final URL, then
     * executes the request and returns paginated transaction results.
     *
     * @return TransactionsPageResponse Paginated list of transactions matching the query
     * @throws HorizonRequestException If the request fails
     */
    public function execute() : TransactionsPageResponse {
        return $this->request($this->buildUrl());
    }


    /**
     * Streams real-time transaction updates to a callback function
     *
     * This method establishes a persistent connection to Horizon and streams
     * new transactions as they are added to the network. The callback is invoked
     * for each new transaction.
     *
     * Use cursor("now") before calling stream() to only receive new transactions
     * and skip historical ones.
     *
     * Example:
     * $sdk->transactions()->cursor("now")->stream(function(TransactionResponse $tx) {
     *     printf("New transaction: %s\n", $tx->getHash());
     * });
     *
     * @param callable|null $callback Function to receive TransactionResponse objects
     * @return void This method runs indefinitely until interrupted
     * @throws GuzzleException If the streaming connection fails
     */
    public function stream(callable $callback = null)
    {
        $this->getAndStream($this->buildUrl(), function($rawData) use ($callback) {
            $parsedObject = TransactionResponse::fromJson($rawData);
            $callback($parsedObject);
        });
    }
}