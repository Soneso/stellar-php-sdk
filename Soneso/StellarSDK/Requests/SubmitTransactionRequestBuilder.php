<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Requests;

use GuzzleHttp\Client;
use Soneso\StellarSDK\AbstractTransaction;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\Transaction\SubmitTransactionResponse;

class SubmitTransactionRequestBuilder extends RequestBuilder
{
    /**
     * Constructor.
     * @param Client $httpClient the http client to be used for to submit the request.
     */
    public function __construct(Client $httpClient)
    {
        parent::__construct($httpClient, "transactions");
    }


    /**
     * Use this method to set the transaction object to be submitted.
     * It will be used to build a transaction envelope xdr base64 string that will be submitted to the network.
     * @param AbstractTransaction $transaction transaction to be submitted to the network.
     * @return $this
     */
    public function setTransaction(AbstractTransaction $transaction): SubmitTransactionRequestBuilder {
        return $this->setTransactionEnvelopeXdrBase64($transaction->toEnvelopeXdrBase64());
    }

    /**
     * Use this method to set the base 64 encoded transaction envelope to be submitted to the network.
     * @param string $txEnvelopeXdrBase64 the base 64 encoded transaction envelope to be submitted to the network.
     * @return $this
     */
    public function setTransactionEnvelopeXdrBase64(string $txEnvelopeXdrBase64): SubmitTransactionRequestBuilder {
        $this->queryParameters["tx"] = $txEnvelopeXdrBase64;
        return $this;
    }

    /**
     * Execute the request.
     * @param string $url The url of the request to be executed.
     * @return SubmitTransactionResponse the response from Horizon in case of success.
     * @throws HorizonRequestException If there was a problem. E.g. Horizon responded with an error response. The details of the problem can be found within the exception object.
     */
    public function request(string $url): SubmitTransactionResponse {
        $response = parent::executeRequest($url, RequestType::SUBMIT_TRANSACTION, "POST");
        assert($response instanceof SubmitTransactionResponse);
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