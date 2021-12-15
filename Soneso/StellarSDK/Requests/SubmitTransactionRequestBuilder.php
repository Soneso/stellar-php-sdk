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
    public function __construct(Client $httpClient)
    {
        parent::__construct($httpClient, "transactions");
    }


    public function setTransaction(AbstractTransaction $transaction): SubmitTransactionRequestBuilder {
        $this->queryParameters["tx"] = $transaction->toEnvelopeXdrBase64();
        return $this;
    }

    /**
     * @param string $url
     * @return SubmitTransactionResponse
     * @throws HorizonRequestException
     */
    public function request(string $url): SubmitTransactionResponse {
        return parent::executeRequest($url, RequestType::SUBMIT_TRANSACTION, "POST");
    }

    /**
     *  Build and execute request.
     *  @throws HorizonRequestException
     */
    public function execute() : SubmitTransactionResponse {
        return $this->request($this->buildUrl());
    }
}