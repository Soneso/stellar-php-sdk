<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Requests;

use GuzzleHttp\Client;
use Soneso\StellarSDK\AbstractTransaction;
use Soneso\StellarSDK\Constants\NetworkConstants;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\Transaction\SubmitAsyncTransactionResponse;

class SubmitAsyncTransactionRequestBuilder extends RequestBuilder
{

    /**
     * Constructor.
     * @param Client $httpClient the http client to be used for to submit the request.
     */
    public function __construct(Client $httpClient)
    {
        parent::__construct($httpClient, "transactions_async");
    }

    /**
     * Use this method to set the transaction object to be submitted.
     * It will be used to build a transaction envelope xdr base64 string that will be submitted to the network.
     * @param AbstractTransaction $transaction transaction to be submitted to the network.
     * @return $this
     */
    public function setTransaction(AbstractTransaction $transaction): SubmitAsyncTransactionRequestBuilder {
        return $this->setTransactionEnvelopeXdrBase64($transaction->toEnvelopeXdrBase64());
    }

    /**
     * Use this method to set the base 64 encoded transaction envelope to be submitted to the network.
     * @param string $txEnvelopeXdrBase64 the base 64 encoded transaction envelope to be submitted to the network.
     * @return $this
     */
    public function setTransactionEnvelopeXdrBase64(string $txEnvelopeXdrBase64): SubmitAsyncTransactionRequestBuilder {
        $this->queryParameters["tx"] = $txEnvelopeXdrBase64;
        return $this;
    }

    /**
     * Execute the request.
     * @param string $url The url of the request to be executed.
     * @return SubmitAsyncTransactionResponse the response from Horizon in case of success.
     * @throws HorizonRequestException If there was a problem. E.g. Horizon responded with an error response. The details of the problem can be found within the exception object.
     */
    public function request(string $url): SubmitAsyncTransactionResponse {
        try {
            $response = parent::executeRequest($url, RequestType::SUBMIT_ASYNC_TRANSACTION, "POST");
            assert($response instanceof SubmitAsyncTransactionResponse);
            return $response;
        } catch (HorizonRequestException $e) {
            $httpResponse = $e->getHttpResponse();
            if ($httpResponse != null && (
                $httpResponse->getStatusCode() === NetworkConstants::HTTP_BAD_REQUEST ||
                $httpResponse->getStatusCode() === NetworkConstants::HTTP_FORBIDDEN ||
                $httpResponse->getStatusCode() === NetworkConstants::HTTP_CONFLICT ||
                $httpResponse->getStatusCode() === NetworkConstants::HTTP_INTERNAL_SERVER_ERROR ||
                $httpResponse->getStatusCode() === NetworkConstants::HTTP_SERVICE_UNAVAILABLE
                )
            ) {
                $decoded = json_decode($httpResponse->getBody()->__toString(), true);
                if ($decoded !== null && isset($decoded["tx_status"])) {
                    return SubmitAsyncTransactionResponse::fromJson($decoded, $httpResponse->getStatusCode());
                }
            }
            throw $e;
        }
    }

    /**
     *  Build and execute request.
     *  @throws HorizonRequestException in case of a problem.
     */
    public function execute() : SubmitAsyncTransactionResponse {
        return $this->request($this->buildUrl());
    }
}