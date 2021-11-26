<?php

namespace Soneso\StellarSDK\Requests;

use GuzzleHttp\Client;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\Transaction\TransactionResponse;
use Soneso\StellarSDK\Responses\Transaction\TransactionsPageResponse;

class TransactionsRequestBuilder extends RequestBuilder
{
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
}