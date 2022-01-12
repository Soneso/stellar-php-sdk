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
use Soneso\StellarSDK\SEP\Toml\StellarToml;

class Federation {

    /**
     * Resolves a stellar address such as bob*soneso.com.
     * @param string $address
     * @return FederationResponse
     * @throws Exception
     */
    public static function resolveStellarAddress(string $address) : FederationResponse {
        if (!str_contains($address, "*")) {
            throw new InvalidArgumentException("Invalid federation address: " . $address);
        }

        $array = explode("*",$address);
        $domain = $array[count($array) - 1];
        $stellarToml = StellarToml::fromDomain($domain);
        $federationServer = $stellarToml->getGeneralInformation()->federationServer;

        if (!$federationServer) {
            throw new Exception("no federation server found for domain: " . $domain);
        }

        $httpClient = new Client([
            'base_uri' => $federationServer,
            'exceptions' => false,
        ]);

        $requestBuilder = (new FederationRequestBuilder($httpClient))->forStringToLookUp($address)->forType("name");
        return $requestBuilder->execute();
    }

    /**
     * @throws Exception
     */
    public static function resolveStellarAccountId(string $accountId, string $federationServerUrl) : FederationResponse {
        $httpClient = new Client([
            'base_uri' => $federationServerUrl,
            'exceptions' => false,
        ]);
        $requestBuilder = (new FederationRequestBuilder($httpClient))->forStringToLookUp($accountId)->forType("id");
        return $requestBuilder->execute();
    }

    /**
     * @throws Exception
     */
    public static function resolveStellarTransactionId(string $accountId, string $federationServerUrl) : FederationResponse {
        $httpClient = new Client([
            'base_uri' => $federationServerUrl,
            'exceptions' => false,
        ]);
        $requestBuilder = (new FederationRequestBuilder($httpClient))->forStringToLookUp($accountId)->forType("txid");
        return $requestBuilder->execute();
    }

    /**
     * Resolves a stellar forward.
     * The url of the federation server and the forward query parameters have to be provided.
     * @throws Exception
     */
    public static function resolveForward(array $queryParameters, string $federationServerUrl) : FederationResponse {
        $httpClient = new Client([
            'base_uri' => $federationServerUrl,
            'exceptions' => false,
        ]);
        $requestBuilder = (new FederationRequestBuilder($httpClient))->forType("forward")->forQueryParameters($queryParameters);
        return $requestBuilder->execute();
    }

}