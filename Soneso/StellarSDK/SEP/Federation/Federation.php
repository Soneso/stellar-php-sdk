<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Federation;

/// Implements Federation protocol.
/// See <a href="https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0002.md" target="_blank">Federation Protocol</a>
use Exception;
use GuzzleHttp\Client;
use InvalidArgumentException;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\SEP\Toml\StellarToml;

class Federation {

    /**
     * Resolves a stellar address such as bob*soneso.com.
     * @param string $address
     * @return FederationResponse
     * @throws Exception
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
     * @return FederationResponse in case of success.
     * @throws HorizonRequestException on any problem. The details of the problem can be found in the exception object.
     */
    public static function resolveStellarAccountId(string $accountId, string $federationServerUrl, ?Client $httpClient = null) : FederationResponse {
        $client = $httpClient != null ? $httpClient : new Client();
        $requestBuilder = (new FederationRequestBuilder($client, $federationServerUrl))->forStringToLookUp($accountId)->forType("id");
        return $requestBuilder->execute();
    }

    /**
     * @return FederationResponse in case of success.
     * @throws HorizonRequestException on any problem. The details of the problem can be found in the exception object.
     */
    public static function resolveStellarTransactionId(string $txId, string $federationServerUrl, ?Client $httpClient = null) : FederationResponse {
        $client = $httpClient != null ? $httpClient : new Client();
        $requestBuilder = (new FederationRequestBuilder($client, $federationServerUrl))->forStringToLookUp($txId)->forType("txid");
        return $requestBuilder->execute();
    }

    /**
     * Resolves a stellar forward.
     * The url of the federation server and the forward query parameters have to be provided.
     *
     * @return FederationResponse in case of success.
     * @throws HorizonRequestException on any problem. The details of the problem can be found in the exception object.
     * /
     */
    public static function resolveForward(array $queryParameters, string $federationServerUrl, ?Client $httpClient = null) : FederationResponse {
        $client = $httpClient != null ? $httpClient : new Client();
        $requestBuilder = (new FederationRequestBuilder($client, $federationServerUrl))->forType("forward")->forQueryParameters($queryParameters);
        return $requestBuilder->execute();
    }

}