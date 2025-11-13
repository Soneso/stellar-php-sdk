<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Requests;

use GuzzleHttp\Client;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\Health\HealthResponse;
use Soneso\StellarSDK\Responses\Response;

/**
 * Builds requests for the health endpoint in Horizon
 *
 * This class provides access to the Horizon health check endpoint, which indicates
 * whether the Horizon server is healthy and able to serve requests. The health
 * endpoint is useful for monitoring and load balancing.
 *
 * A healthy Horizon instance returns HTTP 200 with status information. If the
 * instance is unhealthy or unable to sync with the Stellar network, it returns
 * an error status.
 *
 * Usage Example:
 *
 * // Check Horizon server health
 * $health = $sdk->health()->getHealth();
 * echo "Status: " . $health->getStatus() . PHP_EOL;
 *
 * @package Soneso\StellarSDK\Requests
 * @see HealthResponse For the response format
 * @see https://developers.stellar.org Stellar developer docs Horizon API Health Check
 */
class HealthRequestBuilder extends RequestBuilder
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
     * Retrieve the health status of the Horizon server
     *
     * Returns information about the operational status of the Horizon instance,
     * including database connectivity and ledger sync status.
     *
     * @return HealthResponse The health status information
     * @throws HorizonRequestException When the request fails or server is unhealthy
     */
    public function getHealth(): HealthResponse {
        $this->setSegments("health");
        return parent::executeRequest($this->buildUrl(), RequestType::HEALTH);
    }

    /**
     * Build and execute request.
     * @throws HorizonRequestException
     */
    public function execute(): Response {
        throw new HorizonRequestException("not supported");
    }
}