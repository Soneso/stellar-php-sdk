<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuth;

use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Requests\RequestBuilder;
use Soneso\StellarSDK\Requests\RequestType;
use Soneso\StellarSDK\SEP\Federation\FederationResponse;

class ChallengeRequestBuilder extends RequestBuilder
{
    public function forAccountId(string $accountId) : ChallengeRequestBuilder {
        $this->queryParameters["account"] = $accountId;
        return $this;
    }

    public function forHomeDomain(string $homeDomain) : ChallengeRequestBuilder {
        $this->queryParameters["home_domain"] = $homeDomain;
        return $this;
    }

    public function forMemo(int $memo) : ChallengeRequestBuilder {
        $this->queryParameters["memo"] = strval($memo);
        return $this;
    }

    public function forClientDomain(string $clientDomain) : ChallengeRequestBuilder {
        $this->queryParameters["client_domain"] = $clientDomain;
        return $this;
    }

    public function forQueryParameters(array $queryParameters) : ChallengeRequestBuilder {
        $this->queryParameters = array_merge($this->queryParameters, $queryParameters);
        return $this;
    }

    public function buildUrl() : string {
        return "?" . http_build_query($this->queryParameters);
    }

    /**
     * Requests specific <code>url</code> and returns {@link ChallengeResponse}.
     * @throws HorizonRequestException
     */
    public function request(string $url) : ChallengeResponse {
        return parent::executeRequest($url,RequestType::CHALLENGE);
    }

    /**
     *  Build and execute request.
     *  @throws HorizonRequestException
     */
    public function execute() : ChallengeResponse {
        return $this->request($this->buildUrl());
    }
}