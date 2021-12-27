<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Requests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\Operations\AccountMergeOperationResponse;
use Soneso\StellarSDK\Responses\Operations\CreateAccountOperationResponse;
use Soneso\StellarSDK\Responses\Operations\OperationsPageResponse;
use Soneso\StellarSDK\Responses\Operations\PathPaymentStrictReceiveOperationResponse;
use Soneso\StellarSDK\Responses\Operations\PathPaymentStrictSendOperationResponse;
use Soneso\StellarSDK\Responses\Operations\PaymentOperationResponse;
use Soneso\StellarSDK\Responses\Payments\PaymentsPageResponse;

class PaymentsRequestBuilder extends RequestBuilder
{
    private const JOIN_PARAMETER_NAME = "join";

    public function __construct(Client $httpClient)
    {
        parent::__construct($httpClient, "payments");
    }

    /**
     * Builds request to <code>GET /accounts/{account}/payments</code>
     * @param string $accountId ID of the account for which to get payments.
     * @return PaymentsRequestBuilder
     * @see <a href="https://developers.stellar.org/api/resources/accounts/payments/">Payments for Account</a>
     */
    public function forAccount(string $accountId) : PaymentsRequestBuilder {
        $this->setSegments("accounts", $accountId, "payments");
        return $this;
    }

    /**
     * Builds request to <code>GET /ledgers/{ledgerSeq}/payments</code>
     * @param string $ledgerSeq Ledger for which to get payments.
     * @return PaymentsRequestBuilder
     * @see <a href="https://developers.stellar.org/api/resources/ledgers/payments/">Payments for Ledger</a>
     */
    public function forLedger(string $ledgerSeq) : PaymentsRequestBuilder {
        $this->setSegments("ledgers", $ledgerSeq, "payments");
        return $this;
    }

    /**
     * Builds request to <code>GET /transactions/{transactionId}/payments</code>
     * @param string $transactionId Transaction ID for which to get payments.
     * @return PaymentsRequestBuilder
     * @see <a href="https://developers.stellar.org/api/resources/ledgers/transactions/">Payments for Transaction</a>
     */
    public function forTransaction(string $transactionId) : PaymentsRequestBuilder {
        $this->setSegments("transactions", $transactionId, "payments");
        return $this;
    }

    /**
     * Adds a parameter defining whether to include transactions in the response. By default, transaction data
     * is not included.
     * @param bool $include  Set to <code>true</code> to include transaction data in the payment response.
     * @return PaymentsRequestBuilder
     */
    public function includeTransactions(bool $include) : PaymentsRequestBuilder {
        // TODO improve this to allow multiple, different joins as soon as needed.
        if ($include) {
            $this->queryParameters[PaymentsRequestBuilder::JOIN_PARAMETER_NAME] = "transactions";
        } else if (array_key_exists(PaymentsRequestBuilder::JOIN_PARAMETER_NAME, $this->queryParameters)){
            unset($this->queryParameters[PaymentsRequestBuilder::JOIN_PARAMETER_NAME]);
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
    public function cursor(string $cursor) : PaymentsRequestBuilder {
        return parent::cursor($cursor);
    }

    /**
     * Sets <code>limit</code> parameter on the request.
     * It defines maximum number of records to return.
     * For range and default values check documentation of the endpoint requested.
     * @param int number maximum number of records to return
     */
    public function limit(int $number) : PaymentsRequestBuilder {
        return parent::limit($number);
    }

    /**
     * Sets <code>order</code> parameter on the request.
     * @param string direction "asc" or "desc"
     */
    public function order(string $direction = "asc") : PaymentsRequestBuilder {
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
     * Streams Payment or CreateAccount objects to $callback
     *
     * $callback should have arguments:
     *  OperationResponse
     *
     * For example:
     *
     * $sdk = StellarSDK::getTestNetInstance();
     * $sdk->payments()->cursor("now")->stream(function(OperationResponse $payment) {
     * printf('Payment operation id %s' . PHP_EOL, $payment->getOperationId());
     * });
     *
     * @param callable|null $callback
     * @throws GuzzleException
     */
    public function stream(callable $callback = null)
    {
        $this->getAndStream($this->buildUrl(), function($rawData) use ($callback) {
            if (isset($rawData['type'])){
                $parsedObject = match ($rawData['type']) {
                    'create_account' => CreateAccountOperationResponse::fromJson($rawData),
                    'payment' => PaymentOperationResponse::fromJson($rawData),
                    'account_merge' => AccountMergeOperationResponse::fromJson($rawData),
                    'path_payment_strict_send' => PathPaymentStrictSendOperationResponse::fromJson($rawData),
                    'path_payment_strict_receive' => PathPaymentStrictReceiveOperationResponse::fromJson($rawData)
                };
                $callback($parsedObject);
            }
        });
    }

}