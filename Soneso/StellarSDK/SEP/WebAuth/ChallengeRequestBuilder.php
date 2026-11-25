<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuth;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Query;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use GuzzleHttp\Psr7\Utils;
use InvalidArgumentException;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Requests\RequestBuilder;
use Soneso\StellarSDK\Requests\RequestType;
use Soneso\StellarSDK\Responses\ResponseHandler;

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

    /**
     * @param string $accountId Valid G... or M... client account ID.
     * @return ChallengeRequestBuilder
     * @throws InvalidArgumentException If the account is invalid. The value is not included in the error.
     */
    public function forAccountId(string $accountId) : ChallengeRequestBuilder {
        self::validateAccount($accountId);
        $this->queryParameters["account"] = $accountId;
        return $this;
    }

    public function forHomeDomain(string $homeDomain) : ChallengeRequestBuilder {
        $this->queryParameters["home_domain"] = $homeDomain;
        return $this;
    }

    public function forMemo(int|string $memo) : ChallengeRequestBuilder {
        try {
            $this->queryParameters["memo"] = Memo::id($memo)->getIdAsString();
        } catch (InvalidArgumentException $e) {
            // Use the same diagnostic as generic parameters without exposing the supplied memo.
            throw new InvalidArgumentException('Invalid memo id: expected an unsigned 64-bit integer', 0, $e);
        }
        return $this;
    }

    /**
     * @param string $clientDomain Non-empty domain to request, including "0".
     * @return ChallengeRequestBuilder
     * @throws InvalidArgumentException If the domain is empty.
     */
    public function forClientDomain(string $clientDomain) : ChallengeRequestBuilder {
        // SDK policy rejects an empty discovery host, while non-null "0" still requests domain verification.
        if ($clientDomain === '') {
            throw new InvalidArgumentException('Client domain must not be empty');
        }
        $this->queryParameters["client_domain"] = $clientDomain;
        return $this;
    }

    /**
     * @param array<string, mixed> $queryParameters Request parameters to merge with existing builder parameters.
     * @return ChallengeRequestBuilder
     * @throws InvalidArgumentException If a supplied account is invalid or client_domain is empty.
     */
    public function forQueryParameters(array $queryParameters) : ChallengeRequestBuilder {
        if (($queryParameters['client_domain'] ?? null) === '') {
            throw new InvalidArgumentException('Client domain must not be empty');
        }
        if (array_key_exists('account', $queryParameters)) {
            self::validateAccount($queryParameters['account']);
        }
        $this->queryParameters = array_merge($this->queryParameters, $queryParameters);
        return $this;
    }

    /**
     * @return string Endpoint URL with the effective challenge query.
     * @throws InvalidArgumentException If the URL, account, memo or client domain is invalid, or the endpoint query contains a reserved request parameter.
     */
    public function buildUrl() : string {
        try {
            $uri = new Uri($this->authEndpoint);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException('Invalid challenge request URL');
        }
        $query = $uri->getQuery();
        $parameters = Query::parse($query, PHP_QUERY_RFC3986);
        // Reject reserved endpoint keys to avoid ambiguous account/domain selection; preserve unrelated query bytes.
        foreach (['account', 'memo', 'home_domain', 'client_domain'] as $name) {
            if (array_key_exists($name, $parameters)) {
                throw new InvalidArgumentException("Endpoint query must not contain request parameter '$name'");
            }
        }
        self::validateParameters($this->queryParameters);
        // Null and empty arrays emit nothing; scalar values such as "0" are sent.
        // Array values are sent with PHP's bracketed keys (extra[0]=a), the form callers of this builder already rely on.
        $requestQuery = http_build_query($this->queryParameters, '', '&', PHP_QUERY_RFC3986);
        if ($requestQuery !== '') {
            $query .= ($query === '' ? '' : '&') . $requestQuery;
        }
        return (string)$uri->withQuery($query);
    }

    /**
     * Requests the given URL without applying builder parameters. Relative URLs resolve against the HTTP client base URI.
     * @param string $url Challenge request URL, including its query parameters; may be relative to the HTTP client base URI.
     * @param string|null $authorizationToken Optional non-empty Bearer token containing only valid HTTP header field bytes,
     * checked before sending this request and overriding client authentication. Null preserves client authentication.
     * @return ChallengeResponse The HTTP 200 challenge response.
     * @throws InvalidArgumentException If the URL, account, memo, client domain or authorization header value is invalid.
     * @throws HorizonRequestException On request or parsing failure, or any non-200 response.
     * The server's string error and HTTP status are retained when supplied.
     */
    public function request(string $url, ?string $authorizationToken = null) : ChallengeResponse {
        // Validate at the public transport boundary so all callers get a safe error without sending a request.
        // SEP-10 lines 188-194 put this token in Authorization; empty values are unusable and prohibited control bytes
        // can inject headers. Error messages must not expose the token.
        if ($authorizationToken !== null) {
            // HTTP header normalization removes spaces and tabs, so these alone cannot carry a token.
            if (trim($authorizationToken, " \t") === '') {
                throw new InvalidArgumentException('Authorization signer must return a non-empty string');
            }
            if (preg_match('/\A[\x20\x09\x21-\x7E\x80-\xFF]+\z/', $authorizationToken) !== 1) {
                throw new InvalidArgumentException('Authorization signer returned an invalid header value');
            }
        }
        // Resolve relative URLs for validation and redirect policy; transport still receives the caller's original URL.
        // This general-purpose builder leaves scheme eligibility to its caller; WebAuth validates its own endpoint.
        try {
            $uri = new Uri($url);
            $baseUri = $this->httpClient->getConfig('base_uri');
            if ($baseUri !== null) {
                $uri = UriResolver::resolve(Utils::uriFor($baseUri), $uri);
            }
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException('Invalid challenge request URL');
        }
        $parameters = Query::parse($uri->getQuery(), PHP_QUERY_RFC3986);
        // Bare query keys are transmitted, unlike null builder parameters.
        foreach (['memo', 'client_domain'] as $name) {
            if (array_key_exists($name, $parameters) && $parameters[$name] === null) {
                $parameters[$name] = '';
            }
        }
        self::validateParameters($parameters);
        // Evaluate HTTP statuses here so Guzzle cannot bypass SEP-10 response handling (lines 280-282, 332).
        $options = ['http_errors' => false];
        if ($authorizationToken !== null) {
            $options['headers'] = ['Authorization' => 'Bearer ' . $authorizationToken];
            // Disable per-client auth for this request so Guzzle cannot overwrite the supplied Bearer header.
            $options['auth'] = null;
        }
        $redirects = $this->httpClient->getConfig('allow_redirects');
        // Prevent HTTPS downgrade without enabling redirects the caller disabled or loosening its protocol restrictions.
        if (($redirects === true || (is_array($redirects) && $redirects !== [])) && $uri->getScheme() === 'https') {
            $redirectOptions = is_array($redirects) ? $redirects : [];
            $redirectOptions['protocols'] = array_values(array_intersect($redirectOptions['protocols'] ?? ['https'], ['https']));
            // No permitted protocol means no redirect, so redirects are disabled.
            $options['allow_redirects'] = $redirectOptions['protocols'] === [] ? false : $redirectOptions;
        }
        try {
            $response = $this->httpClient->send(new Request('GET', $url, self::HEADERS), $options);
        } catch (TransferException $e) {
            // A refused redirect still has a response: apply SEP-10 status handling (line 332), not Horizon parsing.
            $response = $e instanceof RequestException ? $e->getResponse() : null;
            if ($response === null) {
                // Without a response there is no status to evaluate; preserve transport failure handling.
                throw HorizonRequestException::fromOtherException($url, 'GET', $e);
            }
            if ($response->getStatusCode() === 200) {
                // A failed transfer can leave an incomplete HTTP 200 body; preserve the failure without Horizon parsing.
                throw HorizonRequestException::fromOtherException($url, 'GET',
                    new \RuntimeException($e->getMessage(), 0, $e), $response);
            }
        }
        if ($response->getStatusCode() !== 200) {
            // Only 200 succeeds (SEP-10 lines 280-282, 332); a string error of "0" is still the server's error text.
            $json = json_decode((string)$response->getBody(), true);
            $message = is_array($json) && is_string($json['error'] ?? null)
                ? $json['error'] : 'Unexpected challenge response status: ' . $response->getStatusCode();
            throw HorizonRequestException::fromOtherException($url, 'GET',
                new \UnexpectedValueException($message, $response->getStatusCode()), $response);
        }
        try {
            return (new ResponseHandler())->handleResponse($response, RequestType::CHALLENGE, $this->httpClient);
        } catch (\Exception $e) {
            throw HorizonRequestException::fromOtherException($url, 'GET', $e, $response);
        }
    }

    /** @param mixed $account Client account input, never echoed in an error. */
    private static function validateAccount(mixed $account): void {
        // Reject non-account input before URL construction without exposing a mistakenly supplied secret seed.
        if (!is_string($account) || (!StrKey::isValidAccountId($account) && !StrKey::isValidMuxedAccountId($account))) {
            throw new InvalidArgumentException('Client account must be a valid G... or M... account ID');
        }
    }

    /** @param array<string, mixed> $parameters Effective request parameters. */
    private static function validateParameters(array $parameters): void {
        self::validateAccount($parameters['account'] ?? null);
        $memo = $parameters['memo'] ?? null;
        if ($memo !== null) {
            // Muxed accounts already identify the pooled-account user; an additional memo is forbidden (SEP-10 line 266).
            if (str_starts_with($parameters['account'], 'M')) {
                throw new InvalidArgumentException('memo cannot be used if accountId is a muxed account');
            }
            if (!is_int($memo) && !is_string($memo)) {
                throw new InvalidArgumentException('Invalid memo id: expected an unsigned decimal integer');
            }
            try {
                Memo::id($memo);
            } catch (InvalidArgumentException $e) {
                throw new InvalidArgumentException('Invalid memo id: expected an unsigned 64-bit integer');
            }
        }
        if (($parameters['client_domain'] ?? null) === '') {
            // Direct URLs and generic parameters enforce the same non-empty domain policy as forClientDomain().
            throw new InvalidArgumentException('Client domain must not be empty');
        }
    }

    /**
     *  Build and execute the configured endpoint request.
     *  @return ChallengeResponse The HTTP 200 challenge response.
     *  @throws InvalidArgumentException If the URL, account, memo or client domain is invalid, or the endpoint query contains a reserved request parameter.
     *  @throws HorizonRequestException On request or parsing failure, or any non-200 response.
     */
    public function execute() : ChallengeResponse {
        return $this->request($this->buildUrl());
    }
}
