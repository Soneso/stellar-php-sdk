<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuthForContracts;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Query;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\UriResolver;
use GuzzleHttp\Psr7\Utils as Psr7Utils;
use GuzzleHttp\RedirectMiddleware;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Requests\RequestBuilder;
use Soneso\StellarSDK\Requests\RequestType;
use Soneso\StellarSDK\Responses\ResponseHandler;
use UnexpectedValueException;

/**
 * Builder for constructing SEP-45 contract challenge request URLs and executing challenge requests.
 *
 * This builder follows the builder pattern to construct properly formatted GET requests to
 * the SEP-45 authentication endpoint for obtaining contract account authentication challenges.
 * It handles URL parameter encoding and provides a fluent interface for setting request parameters.
 *
 * Purpose:
 * Simplifies the construction of contract challenge requests by providing type-safe methods for
 * each parameter. Encodes the parameters for the URL and rejects an endpoint query that contains a
 * reserved request parameter before making requests to the authentication server. The caller
 * supplies valid account and domain values.
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
 * Account Requirement:
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
        foreach ($queryParameters as $key => $value) {
            $this->queryParameters[$key] = $value;
        }
        return $this;
    }

    /**
     * Builds the complete URL for the challenge request, merging query parameters
     * into any existing query string on the authentication endpoint.
     *
     * Rejects endpoints whose query string contains any reserved SEP-45 challenge request
     * parameter (account, home_domain, or client_domain). Other endpoint query fields whose
     * decoded names are not overridden by request parameters keep their raw encoding and order.
     * Request parameters are encoded with RFC 3986 encoding (omitting null values) and placed
     * before any URL fragment.
     *
     * @return string the complete URL with query parameters
     * @throws InvalidArgumentException if the endpoint query contains account, home_domain, or client_domain
     */
    public function buildUrl(): string
    {
        // Split any URL fragment before merging query parameters so added parameters precede '#'.
        $fragmentPos = strpos($this->authEndpoint, '#');
        if ($fragmentPos !== false) {
            $beforeFragment = substr($this->authEndpoint, 0, $fragmentPos);
            $fragment = substr($this->authEndpoint, $fragmentPos);
        } else {
            $beforeFragment = $this->authEndpoint;
            $fragment = '';
        }

        $queryPos = strpos($beforeFragment, '?');
        if ($queryPos !== false) {
            $base = substr($beforeFragment, 0, $queryPos);
            $existingQuery = substr($beforeFragment, $queryPos + 1);
        } else {
            $base = $beforeFragment;
            $existingQuery = '';
        }

        // Reject reserved SEP-45 challenge parameters (spec 222-226) in the endpoint query so
        // endpoint configuration cannot collide with or shadow caller-supplied request parameters.
        $existingParameters = Query::parse($existingQuery, PHP_QUERY_RFC3986);
        foreach (['account', 'home_domain', 'client_domain'] as $name) {
            if (array_key_exists($name, $existingParameters)) {
                throw new InvalidArgumentException("Endpoint query must not contain request parameter '$name'");
            }
        }

        $requestQuery = http_build_query($this->queryParameters, '', '&', PHP_QUERY_RFC3986);

        // Collect every parameter name supplied by the request, including null-filtered scalar keys
        // and any expanded names emitted by http_build_query() for array values, so matching fields
        // in the endpoint's existing query are dropped while unrelated fields keep their raw
        // encoding and order without being re-encoded.
        $overriddenNames = [];
        foreach ($this->queryParameters as $key => $value) {
            if ($value !== null) {
                $overriddenNames[(string) $key] = true;
            }
        }
        if ($requestQuery !== '') {
            foreach (explode('&', $requestQuery) as $field) {
                $rawName = explode('=', $field, 2)[0];
                $overriddenNames[rawurldecode($rawName)] = true;
            }
        }

        $queryParts = [];
        if ($existingQuery !== '') {
            foreach (explode('&', $existingQuery) as $field) {
                if ($field === '') {
                    continue;
                }
                $rawName = explode('=', $field, 2)[0];
                // Decode with RFC 3986 (matching Query::parse and http_build_query above) so a
                // literal '+' in an endpoint field name is not treated as a space.
                if (!isset($overriddenNames[rawurldecode($rawName)])) {
                    $queryParts[] = $field;
                }
            }
        }

        if ($requestQuery !== '') {
            $queryParts[] = $requestQuery;
        }

        $mergedQuery = implode('&', $queryParts);
        return $mergedQuery === '' ? $base . $fragment : $base . '?' . $mergedQuery . $fragment;
    }

    /**
     * Requests the specified URL and returns the challenge response.
     *
     * Succeeds only when the server responds with HTTP 200. For any other HTTP status code,
     * throws HorizonRequestException with the response attached; the exception message is the
     * non-empty JSON string "error" field from the response body when present, or
     * "Unexpected challenge response status: <code>" otherwise without copying the raw body.
     * When the effective request URI (resolved against the client's base_uri) uses HTTPS, any
     * enabled Guzzle allow_redirects policy on the HTTP client is narrowed to the https protocol
     * without enabling disabled redirects or widening other redirect settings.
     *
     * @param string $url the URL to request
     * @return ContractChallengeResponse the challenge response from the server
     * @throws HorizonRequestException if the request fails, returns a non-200 HTTP status, or cannot be parsed
     */
    public function request(string $url): ContractChallengeResponse
    {
        $response = null;
        try {
            $request = new Request('GET', $url, RequestBuilder::HEADERS);
            // Disable Guzzle HTTP error exceptions so every non-200 status flows through the
            // uniform status check below without copying arbitrary response bodies into messages.
            $options = [RequestOptions::HTTP_ERRORS => false];
            $redirectPolicy = $this->buildHttpsRedirectPolicy($request->getUri());
            if ($redirectPolicy !== null) {
                $options[RequestOptions::ALLOW_REDIRECTS] = $redirectPolicy;
            }
            $response = $this->httpClient->send($request, $options);
        } catch (GuzzleException $e) {
            $exceptionResponse = $e instanceof RequestException ? $e->getResponse() : null;
            // Route Guzzle exceptions carrying a non-200 response (such as refused or exceeded
            // redirects) through the status handling below instead of HorizonRequestException's
            // HorizonErrorResponse parser, which assumes Horizon-shaped JSON on BadResponseException.
            // Keep exceptions carrying an HTTP 200 response on the transport-failure path: Guzzle
            // can attach a 200 response to a failed transfer whose body may be incomplete, so the
            // exception is preserved and that response is not treated as a successful challenge.
            if ($exceptionResponse !== null && $exceptionResponse->getStatusCode() !== 200) {
                $response = $exceptionResponse;
            } else {
                throw HorizonRequestException::fromOtherException(
                    $url,
                    'GET',
                    $exceptionResponse !== null || !($e instanceof Exception)
                        ? new RuntimeException($e->getMessage(), 0, $e)
                        : $e,
                    $exceptionResponse
                );
            }
        }

        $statusCode = $response->getStatusCode();
        // SEP-45 (spec 238, 283-289) treats only HTTP 200 as success and uses a JSON "error"
        // string on failure; non-JSON, non-string, or empty error payloads fall back to a
        // status-only message so raw response bodies are never copied into exceptions.
        if ($statusCode !== 200) {
            $json = json_decode((string)$response->getBody(), true);
            $message = is_array($json) && is_string($json['error'] ?? null) && $json['error'] !== ''
                ? $json['error']
                : 'Unexpected challenge response status: ' . $statusCode;
            throw HorizonRequestException::fromOtherException(
                $url,
                'GET',
                new UnexpectedValueException($message, $statusCode),
                $response
            );
        }

        $responseHandler = new ResponseHandler();
        try {
            return $responseHandler->handleResponse($response, RequestType::CONTRACT_CHALLENGE, $this->httpClient);
        } catch (Exception $e) {
            throw HorizonRequestException::fromOtherException($url, 'GET', $e, $response);
        }
    }

    /**
     * Resolves the effective request URI against the client's base_uri and narrows any
     * enabled redirect policy to HTTPS when the effective scheme is https.
     *
     * Returns null when the effective scheme is not https or when redirects are disabled
     * on the HTTP client, leaving the client's configured policy untouched. Returns false
     * when the caller's protocols allowlist excludes https, disabling redirects instead
     * of passing an empty protocols list to Guzzle.
     *
     * @param UriInterface $uri Request URI before base_uri resolution
     * @return array<string, mixed>|false|null Narrowed allow_redirects settings, false to disable redirects, or null to keep client defaults
     */
    private function buildHttpsRedirectPolicy(UriInterface $uri): array|false|null
    {
        // Resolve against the client's base_uri first so relative URLs inherit their effective
        // scheme before checking whether HTTPS downgrade protection applies.
        $baseUri = $this->httpClient->getConfig('base_uri');
        if ($baseUri instanceof UriInterface || is_string($baseUri)) {
            $uri = UriResolver::resolve(Psr7Utils::uriFor($baseUri), $uri);
        }
        // Match Guzzle's schemeless authority handling: an authority URI without a scheme is HTTP.
        if ($uri->getScheme() === '' && $uri->getHost() !== '') {
            $uri = $uri->withScheme('http');
        }
        // Leave non-HTTPS endpoints (such as localhost HTTP) on the caller's redirect policy.
        if (strtolower($uri->getScheme()) !== 'https') {
            return null;
        }

        // Prevent HTTPS-to-HTTP downgrade redirects on challenge requests (Guzzle defaults allow
        // both http and https). Keep redirects disabled when the caller disabled them.
        $configured = $this->httpClient->getConfig(RequestOptions::ALLOW_REDIRECTS);
        if ($configured === true) {
            $policy = RedirectMiddleware::$defaultSettings;
            $policy['protocols'] = ['https'];
            return $policy;
        }
        if (!is_array($configured) || $configured === []) {
            return null;
        }

        // Narrow a custom protocol allowlist to https only when https is already permitted; never
        // widen a caller's protocol restriction by adding https when it was excluded. Disable
        // redirects instead of passing an empty protocols list, which Guzzle deprecates.
        $policy = $configured;
        if (!array_key_exists('protocols', $policy)) {
            $policy['protocols'] = ['https'];
        } elseif (is_array($policy['protocols'])) {
            if (!in_array('https', $policy['protocols'], true)) {
                return false;
            }
            $policy['protocols'] = ['https'];
        }

        return $policy;
    }

    /**
     * Builds and executes the challenge request.
     *
     * @return ContractChallengeResponse the challenge response from the server
     * @throws InvalidArgumentException if the endpoint query contains account, home_domain, or client_domain
     * @throws HorizonRequestException if the request fails, returns a non-200 HTTP status, or cannot be parsed
     */
    public function execute(): ContractChallengeResponse
    {
        return $this->request($this->buildUrl());
    }
}
