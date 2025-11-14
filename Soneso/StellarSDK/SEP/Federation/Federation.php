<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Federation;

use Exception;
use GuzzleHttp\Client;
use InvalidArgumentException;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\SEP\Toml\StellarToml;

/**
 * Federation protocol implementation for resolving Stellar addresses.
 *
 * This class implements SEP-0002 Federation Protocol, which provides a way to
 * resolve human-readable addresses like "bob*example.com" into Stellar account
 * IDs and memo information. It enables user-friendly payment addressing.
 *
 * @package Soneso\StellarSDK\SEP\Federation
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0002.md
 * @see FederationRequestBuilder
 * @see FederationResponse
 */
class Federation {

    /**
     * Resolves a Stellar address to account ID and memo.
     *
     * @param string $address Stellar address in format "user*domain.com". The username
     *                        portion must not contain the characters '*' or '>'.
     * @param Client|null $httpClient Optional HTTP client. Default is Guzzle.
     * @return FederationResponse Response containing account ID and optional memo.
     * @throws Exception If address format is invalid or federation server not found.
     * @throws HorizonRequestException If federation request fails.
     */
    public static function resolveStellarAddress(string $address, ?Client $httpClient = null) : FederationResponse {
        if (!str_contains($address, "*")) {
            throw new InvalidArgumentException("Invalid federation address: " . $address);
        }

        $array = explode("*",$address);
        $domain = $array[count($array) - 1];
        $stellarToml = StellarToml::fromDomain($domain);
        $federationServerUrl = $stellarToml->getGeneralInformation()->federationServer;

        if (!$federationServerUrl) {
            throw new Exception("no federation server found for domain: " . $domain);
        }

        $client = $httpClient != null ? $httpClient : new Client();

        $requestBuilder = (new FederationRequestBuilder($client, $federationServerUrl))->forStringToLookUp($address)->forType("name");
        return $requestBuilder->execute();
    }

    /**
     * Performs reverse federation lookup for an account ID.
     *
     * @param string $accountId Stellar account ID to lookup.
     * @param string $federationServerUrl URL of the federation server.
     * @param Client|null $httpClient Optional HTTP client. Default is Guzzle.
     * @return FederationResponse Response containing Stellar address if found.
     * @throws HorizonRequestException If federation request fails.
     */
    public static function resolveStellarAccountId(string $accountId, string $federationServerUrl, ?Client $httpClient = null) : FederationResponse {
        $client = $httpClient != null ? $httpClient : new Client();
        $requestBuilder = (new FederationRequestBuilder($client, $federationServerUrl))->forStringToLookUp($accountId)->forType("id");
        return $requestBuilder->execute();
    }

    /**
     * Resolves a transaction ID to federation information.
     *
     * @param string $txId Transaction ID to lookup.
     * @param string $federationServerUrl URL of the federation server.
     * @param Client|null $httpClient Optional HTTP client. Default is Guzzle.
     * @return FederationResponse Response containing federation information.
     * @throws HorizonRequestException If federation request fails.
     */
    public static function resolveStellarTransactionId(string $txId, string $federationServerUrl, ?Client $httpClient = null) : FederationResponse {
        $client = $httpClient != null ? $httpClient : new Client();
        $requestBuilder = (new FederationRequestBuilder($client, $federationServerUrl))->forStringToLookUp($txId)->forType("txid");
        return $requestBuilder->execute();
    }

    /**
     * Resolves forward federation requests with custom parameters.
     *
     * Used for forwarding payments to different networks or financial institutions.
     * The query parameters vary based on the destination institution type. Example
     * parameters: ['forward_type' => 'bank_account', 'swift' => 'BOPBPHMM', 'acct' => '2382376']
     * or ['forward_type' => 'remittance_center', 'first_name' => 'John', 'last_name' => 'Doe',
     * 'address' => '123 Main St', 'city' => 'City', 'postal_code' => '12345', 'country' => 'US'].
     *
     * @param array<array-key, mixed> $queryParameters Custom query parameters for forward request.
     * @param string $federationServerUrl URL of the federation server.
     * @param Client|null $httpClient Optional HTTP client. Default is Guzzle.
     * @return FederationResponse Response containing federation information.
     * @throws HorizonRequestException If federation request fails.
     */
    public static function resolveForward(array $queryParameters, string $federationServerUrl, ?Client $httpClient = null) : FederationResponse {
        $client = $httpClient != null ? $httpClient : new Client();
        $requestBuilder = (new FederationRequestBuilder($client, $federationServerUrl))->forType("forward")->forQueryParameters($queryParameters);
        return $requestBuilder->execute();
    }

}