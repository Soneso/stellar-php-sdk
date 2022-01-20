<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Federation;

use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Requests\RequestBuilder;
use Soneso\StellarSDK\Requests\RequestType;

class FederationRequestBuilder extends RequestBuilder
{

    public function forType(string $type) : FederationRequestBuilder {
        $this->queryParameters["type"] = $type;
        return $this;
    }

    public function forStringToLookUp(string $stringToLookUp) : FederationRequestBuilder {
        $this->queryParameters["q"] = $stringToLookUp;
        return $this;
    }

    public function forQueryParameters(array $queryParameters) : FederationRequestBuilder {
        $this->queryParameters = array_merge($this->queryParameters, $queryParameters);
        return $this;
    }

    public function buildUrl() : string {
        return "?" . http_build_query($this->queryParameters);
    }

    /**
     * Requests specific <code>url</code> and returns {@link FederationResponse}.
     * @throws HorizonRequestException
     */
    public function request(string $url) : FederationResponse {
        return parent::executeRequest($url,RequestType::FEDERATION);
    }

    /**
     *  Build and execute request.
     *  @throws HorizonRequestException
     */
    public function execute() : FederationResponse {
        return $this->request($this->buildUrl());
    }
}