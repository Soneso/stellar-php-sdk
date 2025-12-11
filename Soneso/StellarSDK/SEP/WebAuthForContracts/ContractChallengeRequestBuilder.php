<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuthForContracts;

use GuzzleHttp\Client;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Requests\RequestBuilder;
use Soneso\StellarSDK\Requests\RequestType;

/**
 * Builder for constructing SEP-45 contract challenge request URLs and executing challenge requests.
 *
 * This builder follows the builder pattern to construct properly formatted GET requests to
 * the SEP-45 authentication endpoint for obtaining contract account authentication challenges.
 * It handles URL parameter encoding and provides a fluent interface for setting request parameters.
 *
 * Purpose:
 * Simplifies the construction of contract challenge requests by providing type-safe methods for
 * each parameter. Ensures proper URL encoding and parameter validation before making requests to
 * the authentication server.
 *
 * Usage Pattern:
 * ```php
 * $builder = new ContractChallengeRequestBuilder($authEndpoint, $httpClient);
 * $response = $builder
 *     ->forAccountId("CCXXX...")                  // Required: contract account
 *     ->forHomeDomain("example.com")              // Required: home domain
 *     ->forClientDomain("wallet.com")             // Optional: client domain verification
 *     ->execute();
 * ```
 *
 * SEP-45 Parameters:
 * - account: The client contract account (C... address) requesting authentication (required)
 * - home_domain: The home domain of the service (required)
 * - client_domain: Optional domain for client domain verification (non-custodial wallets)
 *
 * The builder constructs URLs like:
 * https://auth.example.com?account=CCXXX...&home_domain=example.com&client_domain=wallet.com
 *
 * Account Validation:
 * The account parameter must be a valid contract address (C... prefix). Other address types
 * (G... or M...) are not supported for SEP-45 and should use SEP-10 instead.
 *
 * @package Soneso\StellarSDK\SEP\WebAuthForContracts
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0045.md SEP-45 Challenge Request
 * @see WebAuthForContracts For the high-level authentication flow
 */
class ContractChallengeRequestBuilder extends RequestBuilder
{
    private string $authEndpoint;

    /**
     * Creates a new contract challenge request builder.
     *
     * @param string $authEndpoint the WEB_AUTH_FOR_CONTRACTS_ENDPOINT from stellar.toml
     * @param Client $httpClient the HTTP client to use for requests
     */
    public function __construct(string $authEndpoint, Client $httpClient)
    {
        $this->authEndpoint = $authEndpoint;
        parent::__construct($httpClient);
    }

    /**
     * Sets the contract account ID to authenticate.
     *
     * @param string $accountId the contract account address (C...) requesting authentication
     * @return ContractChallengeRequestBuilder this builder for method chaining
     */
    public function forAccountId(string $accountId): ContractChallengeRequestBuilder
    {
        $this->queryParameters["account"] = $accountId;
        return $this;
    }

    /**
     * Sets the home domain for the challenge request.
     *
     * @param string $homeDomain the home domain of the service
     * @return ContractChallengeRequestBuilder this builder for method chaining
     */
    public function forHomeDomain(string $homeDomain): ContractChallengeRequestBuilder
    {
        $this->queryParameters["home_domain"] = $homeDomain;
        return $this;
    }

    /**
     * Sets the client domain for verification.
     *
     * When provided, the server may include an additional authorization entry for the client
     * domain that must be signed by the client domain's signing key. This proves the client
     * is associated with the specified domain.
     *
     * @param string $clientDomain the client's domain for verification
     * @return ContractChallengeRequestBuilder this builder for method chaining
     */
    public function forClientDomain(string $clientDomain): ContractChallengeRequestBuilder
    {
        $this->queryParameters["client_domain"] = $clientDomain;
        return $this;
    }

    /**
     * Sets additional custom query parameters.
     *
     * @param array $queryParameters additional query parameters to include
     * @return ContractChallengeRequestBuilder this builder for method chaining
     */
    public function forQueryParameters(array $queryParameters): ContractChallengeRequestBuilder
    {
        $this->queryParameters = array_merge($this->queryParameters, $queryParameters);
        return $this;
    }

    /**
     * Builds the complete URL for the challenge request.
     *
     * @return string the complete URL with query parameters
     */
    public function buildUrl(): string
    {
        return $this->authEndpoint . "?" . http_build_query($this->queryParameters);
    }

    /**
     * Requests the specified URL and returns the challenge response.
     *
     * @param string $url the URL to request
     * @return ContractChallengeResponse the challenge response from the server
     * @throws HorizonRequestException if the request fails
     * @throws \GuzzleHttp\Exception\GuzzleException if HTTP request fails
     */
    public function request(string $url): ContractChallengeResponse
    {
        return parent::executeRequest($url, RequestType::CONTRACT_CHALLENGE);
    }

    /**
     * Builds and executes the challenge request.
     *
     * @return ContractChallengeResponse the challenge response from the server
     * @throws HorizonRequestException if the request fails
     * @throws \GuzzleHttp\Exception\GuzzleException if HTTP request fails
     */
    public function execute(): ContractChallengeResponse
    {
        return $this->request($this->buildUrl());
    }
}
