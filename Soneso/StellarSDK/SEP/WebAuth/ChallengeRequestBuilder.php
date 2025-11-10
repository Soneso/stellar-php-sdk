<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuth;

use GuzzleHttp\Client;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Requests\RequestBuilder;
use Soneso\StellarSDK\Requests\RequestType;

/**
 * Builder for constructing SEP-10 challenge request URLs and executing challenge requests.
 *
 * This builder follows the builder pattern to construct properly formatted GET requests to
 * the SEP-10 authentication endpoint for obtaining challenge transactions. It handles URL
 * parameter encoding and provides a fluent interface for setting request parameters.
 *
 * Purpose:
 * Simplifies the construction of challenge requests by providing type-safe methods for each
 * parameter. Ensures proper URL encoding and parameter validation before making requests to
 * the authentication server.
 *
 * Usage Pattern:
 * ```php
 * $builder = new ChallengeRequestBuilder($authEndpoint, $httpClient);
 * $response = $builder
 *     ->forAccountId("GCXXX...")
 *     ->forMemo(12345)                    // Optional: for shared accounts
 *     ->forHomeDomain("example.com")       // Optional: when server serves multiple domains
 *     ->forClientDomain("wallet.com")      // Optional: for client domain verification
 *     ->execute();
 * ```
 *
 * SEP-10 Parameters:
 * - account: The client account ID (G... or M... address) requesting authentication
 * - memo: Optional ID memo for identifying users within shared/pooled accounts
 * - home_domain: Optional domain when server serves multiple home domains
 * - client_domain: Optional domain for client domain verification (non-custodial wallets)
 *
 * The builder constructs URLs like:
 * https://auth.example.com?account=GCXXX...&memo=12345&client_domain=wallet.com
 *
 * @package Soneso\StellarSDK\SEP\WebAuth
 * @see https://github.com/stellar/stellar-protocol/blob/v3.4.1/ecosystem/sep-0010.md#challenge SEP-10 Challenge Request
 * @see WebAuth For the high-level authentication flow
 */
class ChallengeRequestBuilder extends RequestBuilder
{
    private string $authEndpoint;

    /**
     * @param string $authEndpoint
     */
    public function __construct(string $authEndpoint, Client $httpClient)
    {
        $this->authEndpoint = $authEndpoint;
        parent::__construct($httpClient);
    }

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
        return $this->authEndpoint . "?" . http_build_query($this->queryParameters);
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