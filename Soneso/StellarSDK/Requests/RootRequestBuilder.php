<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Requests;

use GuzzleHttp\Client;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\Response;
use Soneso\StellarSDK\Responses\Root\RootResponse;

/**
 * Builds requests for the root endpoint in Horizon
 *
 * This class provides access to the Horizon root endpoint, which returns general
 * information about the Horizon instance, including its version, network information,
 * and links to other API endpoints.
 *
 * The root endpoint is typically the first request made to discover the capabilities
 * and configuration of a Horizon server. It includes the network passphrase, which
 * identifies whether the instance serves testnet, pubnet, or a custom network.
 *
 * Usage Example:
 *
 * // Get Horizon root information
 * $root = $sdk->root()->getRoot("https://horizon.stellar.org");
 * echo "Horizon version: " . $root->getHorizonVersion() . PHP_EOL;
 * echo "Network passphrase: " . $root->getNetworkPassphrase() . PHP_EOL;
 *
 * @package Soneso\StellarSDK\Requests
 * @see RootResponse For the response format
 * @see https://developers.stellar.org/api/introduction Horizon API Root endpoint
 */
class RootRequestBuilder extends RequestBuilder
{
    /**
     * Constructor
     *
     * @param Client $httpClient The HTTP client used for making requests to Horizon
     */
    public function __construct(Client $httpClient) {
        parent::__construct($httpClient);
    }

    /**
     * Retrieve root information from a Horizon server
     *
     * Returns general information about the Horizon instance including version,
     * network configuration, and available endpoints.
     *
     * @param string $url The full URL to the Horizon root endpoint
     * @return RootResponse The root information
     * @throws HorizonRequestException When the request fails
     */
    public function getRoot(string $url) : RootResponse {
        return parent::executeRequest($url,RequestType::ROOT);
    }

    /**
     *  Build and execute request.
     *  @throws HorizonRequestException
     */
    public function execute() : Response {
        throw new HorizonRequestException("not supported");
    }
}