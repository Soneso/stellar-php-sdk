<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Recovery;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Soneso\StellarSDK\Requests\RequestBuilder;

/**
 * Implements SEP-0030 - Account Recovery: multi-party recovery of Stellar accounts.
 * See <a href="https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0030.md" target="_blank">Account Recovery: multi-party recovery of Stellar accounts.</a>
 */
class RecoveryService
{
    private string $serviceAddress;
    private Client $httpClient;

    /**
     * @param string $serviceAddress
     * @param Client|null $httpClient
     */
    public function __construct(string $serviceAddress, ?Client $httpClient = null)
    {
        $this->serviceAddress = $serviceAddress;
        if ($httpClient != null) {
            $this->httpClient = $httpClient;
        } else {
            $this->httpClient = new Client([
                'base_uri' => $this->serviceAddress,
                'exceptions' => false,
            ]);
        }
    }

    /**
     * This endpoint registers an account.
     * See: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0030.md#post-accountsaddress
     * @param string $address
     * @param SEP30Request $request
     * @param string $jwt
     * @return SEP30AccountResponse
     * @throws GuzzleException
     * @throws SEP30BadRequestResponseException
     * @throws SEP30ConflictResponseException
     * @throws SEP30NotFoundResponseException
     * @throws SEP30UnauthorizedResponseException
     * @throws SEP30UnknownResponseException
     */
    public function registerAccount(string $address, SEP30Request $request, string $jwt) : SEP30AccountResponse {

        $url = $this->buildServiceUrl("accounts/" . $address);

        $response = $this->httpClient->post($url,
            [RequestOptions::JSON => $request->toJson(),
            RequestOptions::HEADERS => $this->buildHeaders($jwt)]);

        $statusCode = $response->getStatusCode();
        $content = $response->getBody()->__toString();
        $jsonData = @json_decode($content, true);
        if (200 === $statusCode) {
            return SEP30AccountResponse::fromJson($jsonData);
        } else {
            $errorMsg = $content;
            if (null !== $jsonData && isset($jsonData['error'])) {
                $errorMsg = $jsonData['error'];
            }
            if (400 === $statusCode) {
                throw new SEP30BadRequestResponseException($errorMsg, $statusCode);
            } else if (401 === $statusCode) {
                throw new SEP30UnauthorizedResponseException($errorMsg, $statusCode);
            } else if (404 === $statusCode) {
                throw new SEP30NotFoundResponseException($errorMsg, $statusCode);
            } else if (409 === $statusCode) {
                throw new SEP30ConflictResponseException($errorMsg, $statusCode);
            } else {
                throw new SEP30UnknownResponseException($errorMsg, $statusCode);
            }
        }
    }

    /**
     * This endpoint updates the identities for the account.
     * The identities should be entirely replaced with the identities provided in the request, and not merged. Either owner or other or both should be set. If one is currently set and the request does not include it, it is removed.
     * See: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0030.md#put-accountsaddress
     * @param string $address
     * @param SEP30Request $request
     * @param string $jwt
     * @return SEP30AccountResponse
     * @throws GuzzleException
     * @throws SEP30BadRequestResponseException
     * @throws SEP30ConflictResponseException
     * @throws SEP30NotFoundResponseException
     * @throws SEP30UnauthorizedResponseException
     * @throws SEP30UnknownResponseException
     */
    public function updateIdentitiesForAccount(string $address, SEP30Request $request, string $jwt) : SEP30AccountResponse {

        $url = $this->buildServiceUrl("accounts/" . $address);

        $response = $this->httpClient->put($url,
            [RequestOptions::JSON => $request->toJson(),
                RequestOptions::HEADERS => $this->buildHeaders($jwt)]);

        $statusCode = $response->getStatusCode();
        $content = $response->getBody()->__toString();
        $jsonData = @json_decode($content, true);
        if (200 === $statusCode) {
            return SEP30AccountResponse::fromJson($jsonData);
        } else {
            $errorMsg = $content;
            if (null !== $jsonData && isset($jsonData['error'])) {
                $errorMsg = $jsonData['error'];
            }
            if (400 === $statusCode) {
                throw new SEP30BadRequestResponseException($errorMsg, $statusCode);
            } else if (401 === $statusCode) {
                throw new SEP30UnauthorizedResponseException($errorMsg, $statusCode);
            } else if (404 === $statusCode) {
                throw new SEP30NotFoundResponseException($errorMsg, $statusCode);
            } else if (409 === $statusCode) {
                throw new SEP30ConflictResponseException($errorMsg, $statusCode);
            } else {
                throw new SEP30UnknownResponseException($errorMsg, $statusCode);
            }
        }
    }

    /**
     * This endpoint signs a transaction.
     * See https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0030.md#post-accountsaddresssignsigning-address
     * @param string $address
     * @param string $signingAddress
     * @param string $transaction
     * @param string $jwt
     * @return SEP30SignatureResponse
     * @throws GuzzleException
     * @throws SEP30BadRequestResponseException
     * @throws SEP30ConflictResponseException
     * @throws SEP30NotFoundResponseException
     * @throws SEP30UnauthorizedResponseException
     * @throws SEP30UnknownResponseException
     */
    public function signTransaction(string $address, string $signingAddress, string $transaction, string $jwt) : SEP30SignatureResponse {

        $url = $this->buildServiceUrl("accounts/" . $address . "/sign/" . $signingAddress);

        $response = $this->httpClient->post($url,
            [RequestOptions::JSON => ['transaction' => $transaction],
                RequestOptions::HEADERS => $this->buildHeaders($jwt)]);

        $statusCode = $response->getStatusCode();
        $content = $response->getBody()->__toString();
        $jsonData = @json_decode($content, true);
        if (200 === $statusCode) {
            return SEP30SignatureResponse::fromJson($jsonData);
        } else {
            $errorMsg = $content;
            if (null !== $jsonData && isset($jsonData['error'])) {
                $errorMsg = $jsonData['error'];
            }
            if (400 === $statusCode) {
                throw new SEP30BadRequestResponseException($errorMsg, $statusCode);
            } else if (401 === $statusCode) {
                throw new SEP30UnauthorizedResponseException($errorMsg, $statusCode);
            } else if (404 === $statusCode) {
                throw new SEP30NotFoundResponseException($errorMsg, $statusCode);
            } else if (409 === $statusCode) {
                throw new SEP30ConflictResponseException($errorMsg, $statusCode);
            } else {
                throw new SEP30UnknownResponseException($errorMsg, $statusCode);
            }
        }
    }

    /**
     * This endpoint returns the registered account’s details.
     * See: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0030.md#get-accountsaddress
     * @param string $address
     * @param string $jwt
     * @return SEP30AccountResponse
     * @throws GuzzleException
     * @throws SEP30BadRequestResponseException
     * @throws SEP30ConflictResponseException
     * @throws SEP30NotFoundResponseException
     * @throws SEP30UnauthorizedResponseException
     * @throws SEP30UnknownResponseException
     */
    public function accountDetails(string $address, string $jwt) : SEP30AccountResponse {

        $url = $this->buildServiceUrl("accounts/" . $address);

        $response = $this->httpClient->get($url,
            [RequestOptions::HEADERS => $this->buildHeaders($jwt)]);

        $statusCode = $response->getStatusCode();
        $content = $response->getBody()->__toString();
        $jsonData = @json_decode($content, true);
        if (200 === $statusCode) {
            return SEP30AccountResponse::fromJson($jsonData);
        } else {
            $errorMsg = $content;
            if (null !== $jsonData && isset($jsonData['error'])) {
                $errorMsg = $jsonData['error'];
            }
            if (400 === $statusCode) {
                throw new SEP30BadRequestResponseException($errorMsg, $statusCode);
            } else if (401 === $statusCode) {
                throw new SEP30UnauthorizedResponseException($errorMsg, $statusCode);
            } else if (404 === $statusCode) {
                throw new SEP30NotFoundResponseException($errorMsg, $statusCode);
            } else if (409 === $statusCode) {
                throw new SEP30ConflictResponseException($errorMsg, $statusCode);
            } else {
                throw new SEP30UnknownResponseException($errorMsg, $statusCode);
            }
        }
    }

    /**
     * This endpoint will delete the record for an account. This should be irrecoverable.
     * See: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0030.md#delete-accountsaddress
     * @param string $address
     * @param string $jwt
     * @return SEP30AccountResponse
     * @throws GuzzleException
     * @throws SEP30BadRequestResponseException
     * @throws SEP30ConflictResponseException
     * @throws SEP30NotFoundResponseException
     * @throws SEP30UnauthorizedResponseException
     * @throws SEP30UnknownResponseException
     */
    public function deleteAccount(string $address, string $jwt) : SEP30AccountResponse {

        $url = $this->buildServiceUrl("accounts/" . $address);

        $response = $this->httpClient->delete($url,
            [RequestOptions::HEADERS => $this->buildHeaders($jwt)]);

        $statusCode = $response->getStatusCode();
        $content = $response->getBody()->__toString();
        $jsonData = @json_decode($content, true);
        if (200 === $statusCode) {
            return SEP30AccountResponse::fromJson($jsonData);
        } else {
            $errorMsg = $content;
            if (null !== $jsonData && isset($jsonData['error'])) {
                $errorMsg = $jsonData['error'];
            }
            if (400 === $statusCode) {
                throw new SEP30BadRequestResponseException($errorMsg, $statusCode);
            } else if (401 === $statusCode) {
                throw new SEP30UnauthorizedResponseException($errorMsg, $statusCode);
            } else if (404 === $statusCode) {
                throw new SEP30NotFoundResponseException($errorMsg, $statusCode);
            } else if (409 === $statusCode) {
                throw new SEP30ConflictResponseException($errorMsg, $statusCode);
            } else {
                throw new SEP30UnknownResponseException($errorMsg, $statusCode);
            }
        }
    }

    /**
     * This endpoint will return a list of accounts that the JWT allows access to.
     * See: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0030.md#get-accounts
     * @param string $jwt
     * @param string|null $after
     * @return SEP30AccountsResponse
     * @throws GuzzleException
     * @throws SEP30BadRequestResponseException
     * @throws SEP30ConflictResponseException
     * @throws SEP30NotFoundResponseException
     * @throws SEP30UnauthorizedResponseException
     * @throws SEP30UnknownResponseException
     */
    public function accounts(string $jwt, ?string $after = null) : SEP30AccountsResponse {

        $url = $this->buildServiceUrl("accounts");
        if ($after != null) {
            $url .= "?after=" . $after;
        }

        $response = $this->httpClient->get($url,
            [RequestOptions::HEADERS => $this->buildHeaders($jwt)]);

        $statusCode = $response->getStatusCode();
        $content = $response->getBody()->__toString();
        $jsonData = @json_decode($content, true);
        if (200 === $statusCode) {
            return SEP30AccountsResponse::fromJson($jsonData);
        } else {
            $errorMsg = $content;
            if (null !== $jsonData && isset($jsonData['error'])) {
                $errorMsg = $jsonData['error'];
            }
            if (400 === $statusCode) {
                throw new SEP30BadRequestResponseException($errorMsg, $statusCode);
            } else if (401 === $statusCode) {
                throw new SEP30UnauthorizedResponseException($errorMsg, $statusCode);
            } else if (404 === $statusCode) {
                throw new SEP30NotFoundResponseException($errorMsg, $statusCode);
            } else if (409 === $statusCode) {
                throw new SEP30ConflictResponseException($errorMsg, $statusCode);
            } else {
                throw new SEP30UnknownResponseException($errorMsg, $statusCode);
            }
        }
    }

    private function buildHeaders(string $jwt) : array {
        $headers = array();
        $headers = array_merge($headers, RequestBuilder::HEADERS);
        return array_merge($headers, ['Authorization' => "Bearer ". $jwt]);
    }

    private function buildServiceUrl(string $segment): string
    {

        if (str_ends_with($this->serviceAddress, "/")) {
            return $this->serviceAddress . $segment;
        } else {
            return $this->serviceAddress . "/" . $segment;
        }
    }
}