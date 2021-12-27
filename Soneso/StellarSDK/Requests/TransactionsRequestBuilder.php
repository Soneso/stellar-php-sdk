<?php

namespace Soneso\StellarSDK\Requests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\Transaction\TransactionResponse;
use Soneso\StellarSDK\Responses\Transaction\TransactionsPageResponse;

class TransactionsRequestBuilder extends RequestBuilder
{
    private const INCLUDE_FAILED_PARAMETER_NAME = "include_failed";

    public function __construct(Client $httpClient)
    {
        parent::__construct($httpClient, "transactions");
    }

    /**
     * Requests <code>GET /transactions/{transactionId}</code>
     * @param string sequence (transactionId) of the transaction to fetch
     * @throws HorizonRequestException
     */
    public function transaction(string $transactionId): TransactionResponse
    {
        $this->setSegments("transactions", $transactionId);
        return parent::executeRequest($this->buildUrl(), RequestType::SINGLE_TRANSACTION);
    }
    /**
     * Builds request to <code>GET /accounts/{account}/transactions</code>
     * @param string $accountId ID of the account for which to get transactions.
     * @return TransactionsRequestBuilder
     * @see <a href="https://developers.stellar.org/api/resources/accounts/transactions/">Transactions for Account</a>
     */
    public function forAccount(string $accountId) : TransactionsRequestBuilder {
        $this->setSegments("accounts", $accountId, "transactions");
        return $this;
    }

    /**
     * Builds request to <code>GET /claimable_balances/{claimable_balance_id}/transactions</code>
     * @param string $claimableBalanceId ID of Claimable Balance for which to get transactions.
     * @return TransactionsRequestBuilder
     * @see <a href="https://developers.stellar.org/api/resources/claimablebalances/transactions/">transactions for ClaimableBalance</a>
     */
    public function forClaimableBalance(string $claimableBalanceId) : TransactionsRequestBuilder {
        $this->setSegments("claimable_balances", $claimableBalanceId, "transactions");
        return $this;
    }

    /**
     * Builds request to <code>GET /ledgers/{ledgerSeq}/transactions</code>
     * @param string $ledgerSeq Ledger for which to get transactions.
     * @return TransactionsRequestBuilder
     * @see <a href="https://developers.stellar.org/api/resources/ledgers/transactions/">transactions for Ledger</a>
     */
    public function forLedger(string $ledgerSeq) : TransactionsRequestBuilder {
        $this->setSegments("ledgers", $ledgerSeq, "transactions");
        return $this;
    }

    /**
     * Builds request to <code>GET /liquidity_pools/{poolID}/transactions</code>
     * @param string $liquidityPoolId  Liquidity pool for which to get transactions.
     * @return TransactionsRequestBuilder
     * @see <a href="https://developers.stellar.org/api/resources/liquiditypools/transactions/">transactions for Liquidity Pool</a>
     */
    public function forLiquidityPool(string $liquidityPoolId) : TransactionsRequestBuilder {
        $this->setSegments("liquidity_pools", $liquidityPoolId, "transactions");
        return $this;
    }

    /**
     * Adds a parameter defining whether to include operations of failed transactions. By default, only transactions of
     * successful transactions are returned.
     * @param bool $value  Set to <code>true</code> to include failed transactions.
     * @return TransactionsRequestBuilder
     */
    public function includeFailed(bool $value) : TransactionsRequestBuilder {
        $this->queryParameters[TransactionsRequestBuilder::INCLUDE_FAILED_PARAMETER_NAME] = $value ? "true" : "false";
        return $this;
    }

    /**
     * Sets <code>cursor</code> parameter on the request.
     * A cursor is a value that points to a specific location in a collection of resources.
     * The cursor attribute itself is an opaque value meaning that users should not try to parse it.
     * @see <a href="https://developers.stellar.org/api/introduction/pagination/">Page documentation</a>
     * @param string cursor
     */
    public function cursor(string $cursor) : TransactionsRequestBuilder {
        return parent::cursor($cursor);
    }

    /**
     * Sets <code>limit</code> parameter on the request.
     * It defines maximum number of records to return.
     * For range and default values check documentation of the endpoint requested.
     * @param int number maximum number of records to return
     */
    public function limit(int $number) : TransactionsRequestBuilder {
        return parent::limit($number);
    }

    /**
     * Sets <code>order</code> parameter on the request.
     * @param string direction "asc" or "desc"
     */
    public function order(string $direction = "asc") : TransactionsRequestBuilder {
        return parent::order($direction);
    }

    /**
     * Requests specific <code>url</code> and returns {@link TransactionsPageResponse}.
     * @throws HorizonRequestException
     */
    public function request(string $url): TransactionsPageResponse
    {
        return parent::executeRequest($url, RequestType::TRANSACTIONS_PAGE);
    }

    /**
     *  Build and execute request.
     *  @throws HorizonRequestException
     */
    public function execute() : TransactionsPageResponse {
        return $this->request($this->buildUrl());
    }


    /**
     * Streams Transaction objects to $callback
     *
     * $callback should have arguments:
     *  TransactionResponse
     *
     * For example:
     *
     * $sdk = StellarSDK::getTestNetInstance();
     * $sdk->transactions()->cursor("now")->stream(function(TransactionResponse $transaction) {
     * printf('Transaction Hash %s' . PHP_EOL, $transaction->getHash());
     * });
     *
     * @param callable|null $callback
     * @throws GuzzleException
     */
    public function stream(callable $callback = null)
    {
        $this->getAndStream($this->buildUrl(), function($rawData) use ($callback) {
            $parsedObject = TransactionResponse::fromJson($rawData);
            $callback($parsedObject);
        });
    }
}