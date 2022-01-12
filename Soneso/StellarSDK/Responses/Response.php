<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses;

use GuzzleHttp\Client;

abstract class Response
{
    protected ?int $rateLimitLimit = null;
    protected ?int $rateLimitRemaining = null;
    protected ?int $rateLimitReset = null;
    protected Client $httpClient;

    public function setHeaders(array $headers) : void {
        
        if (array_key_exists("X-Ratelimit-Limit", $headers)) {
            $this->rateLimitLimit = (int)$headers["X-Ratelimit-Limit"];
        }
        if (array_key_exists("X-Ratelimit-Remaining", $headers)) {
            $this->rateLimitRemaining = (int)$headers["X-Ratelimit-Remaining"];
        }
        if (array_key_exists("X-Ratelimit-Reset", $headers)) {
            $this->rateLimitReset = (int)$headers["X-Ratelimit-Reset"];
        }
    }
    
    /**
     * Returns X-RateLimit-Limit header from the response.
     * This number represents the he maximum number of requests that the current client can
     * make in one hour.
     * @see <a href="https://developers.stellar.org/api/introduction/rate-limiting/" target="_blank">Rate Limiting</a>
     */
    public function getRateLimitLimit() : ?int {
        return $this->rateLimitLimit;
    }
    
    /**
     * Returns X-RateLimit-Remaining header from the response.
     * The number of remaining requests for the current window.
     * @see <a href="https://developers.stellar.org/api/introduction/rate-limiting/" target="_blank">Rate Limiting</a>
     */
    public function getRateLimitRemaining() : ?int {
        return $this->rateLimitRemaining;
    }
    
   /**
   * Returns X-RateLimit-Reset header from the response. Seconds until a new window starts.
   * @see <a href="https://developers.stellar.org/api/introduction/rate-limiting/" target="_blank">Rate Limiting</a>
   */
    public function getRateLimitReset() : ?int {
        return $this->rateLimitReset;
    }
    
    protected function loadFromJson(array $json) : void {
        if (isset($json['rateLimitLimit'])) $this->rateLimitLimit = $json['rateLimitLimit'];
        if (isset($json['rateLimitRemaining'])) $this->rateLimitRemaining = $json['rateLimitRemaining'];
        if (isset($json['rateLimitReset'])) $this->rateLimitReset = $json['rateLimitReset'];
    }

    public function setHttpClient(Client $httpClient) : void {
        $this->httpClient = $httpClient;
    }

    /**
     * @return Client
     */
    public function getHttpClient(): Client
    {
        return $this->httpClient;
    }
}


