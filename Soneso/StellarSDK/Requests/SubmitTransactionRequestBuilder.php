<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Requests;

use GuzzleHttp\Client;
use Soneso\StellarSDK\AbstractTransaction;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\Transaction\SubmitTransactionResponse;
use UnexpectedValueException;

/**
 * Builds requests for submitting transactions to Horizon
 *
 * This class provides methods to submit signed transactions to the Stellar network via
 * Horizon. Transactions are submitted synchronously, meaning the request will wait for
 * the transaction to be included in a ledger before returning a response.
 *
 * The builder accepts either a transaction object or a base64-encoded transaction envelope
 * XDR string. The transaction must be properly signed before submission.
 *
 * Usage Examples:
 *
 * // Submit a signed transaction object
 * $response = $sdk->submitTransaction()
 *     ->setTransaction($signedTransaction)
 *     ->execute();
 *
 * // Submit a transaction envelope XDR
 * $response = $sdk->submitTransaction()
 *     ->setTransactionEnvelopeXdrBase64($txXdr)
 *     ->execute();
 *
 * // Check submission result
 * if ($response->isSuccessful()) {
 *     echo "Transaction hash: " . $response->getHash() . PHP_EOL;
 * } else {
 *     echo "Error: " . $response->getExtras()->getResultCodes()->getTransactionResultCode() . PHP_EOL;
 * }
 *
 * @package Soneso\StellarSDK\Requests
 * @see SubmitTransactionResponse For the response format
 * @see https://developers.stellar.org Stellar developer docs Horizon API Submit Transaction
 */
class SubmitTransactionRequestBuilder extends RequestBuilder
{
    /**
     * Constructor
     *
     * @param Client $httpClient The HTTP client used for making requests to Horizon
     */
    public function __construct(Client $httpClient)
    {
        parent::__construct($httpClient, "transactions");
    }


    /**
     * Use this method to set the transaction object to be submitted.
     * It will be used to build a transaction envelope xdr base64 string that will be submitted to the network.
     * @param AbstractTransaction $transaction Transaction to be submitted to the network.
     * @return $this
     */
    public function setTransaction(AbstractTransaction $transaction): SubmitTransactionRequestBuilder {
        return $this->setTransactionEnvelopeXdrBase64($transaction->toEnvelopeXdrBase64());
    }

    /**
     * Use this method to set the base 64 encoded transaction envelope to be submitted to the network.
     * @param string $txEnvelopeXdrBase64 The base 64 encoded transaction envelope to be submitted to the network.
     * @return $this
     */
    public function setTransactionEnvelopeXdrBase64(string $txEnvelopeXdrBase64): SubmitTransactionRequestBuilder {
        $this->queryParameters["tx"] = $txEnvelopeXdrBase64;
        return $this;
    }

    /**
     * Execute the request.
     * @param string $url The URL of the request to be executed.
     * @return SubmitTransactionResponse The response from Horizon in case of success.
     * @throws HorizonRequestException If there was a problem. E.g. Horizon responded with an error response. The details of the problem can be found within the exception object.
     */
    public function request(string $url): SubmitTransactionResponse {
        $response = parent::executeRequest($url, RequestType::SUBMIT_TRANSACTION, "POST");
        if (!$response instanceof SubmitTransactionResponse) {
            throw new UnexpectedValueException('Expected SubmitTransactionResponse, got ' . get_class($response));
        }
        return $response;
    }

    /**
     *  Build and execute request.
     *  @throws HorizonRequestException
     */
    public function execute() : SubmitTransactionResponse {
        return $this->request($this->buildUrl());
    }
}