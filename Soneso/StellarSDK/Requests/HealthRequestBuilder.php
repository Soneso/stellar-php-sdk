<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Requests;

use GuzzleHttp\Client;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\Health\HealthResponse;
use Soneso\StellarSDK\Responses\Response;

class HealthRequestBuilder extends RequestBuilder
{

    public function __construct(Client $httpClient) {
        parent::__construct($httpClient);
    }

    /**
     * @return HealthResponse
     * @throws HorizonRequestException
     */
    public function getHealth(): HealthResponse {
        $this->setSegments("health");
        return parent::executeRequest($this->buildUrl(), RequestType::HEALTH);
    }

    /**
     * Build and execute request.
     * @throws HorizonRequestException
     */
    public function execute(): Response {
        throw new HorizonRequestException("not supported");
    }
}