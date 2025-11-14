<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses;

use GuzzleHttp\Client;

/**
 * Base class for all Horizon API response objects
 *
 * This abstract class provides common functionality for all Horizon response types,
 * including rate limiting information and HTTP client management. Every response
 * from the Horizon API extends this class.
 *
 * Rate Limiting:
 * Horizon enforces rate limits on API requests. This class captures rate limit
 * headers from responses, allowing clients to monitor their usage and implement
 * appropriate throttling strategies.
 *
 * Pagination:
 * Many Horizon endpoints return paginated results. Paginated responses include
 * navigation links (next, prev, self) and typically contain an array of records.
 * Use the cursor parameter with request builders to navigate through pages.
 *
 * @package Soneso\StellarSDK\Responses
 * @see https://developers.stellar.org Stellar developer docs Rate limiting documentation
 * @see https://developers.stellar.org Stellar developer docs Pagination documentation
 */
abstract class Response
{
    protected ?int $rateLimitLimit = null;
    protected ?int $rateLimitRemaining = null;
    protected ?int $rateLimitReset = null;
    protected ?Client $httpClient = null;

    /**
     * Extracts and sets rate limiting information from HTTP response headers
     *
     * This method processes the X-Ratelimit-* headers from the HTTP response
     * and stores them for client access.
     *
     * @param array $headers Associative array of HTTP headers from the response
     * @return void
     */
    public function setHeaders(array $headers) : void {
        // PSR-7 getHeaders() returns headers as arrays of values (e.g., ['100'] not '100')
        // We need to extract the first value from the array before converting to int

        if (array_key_exists("X-Ratelimit-Limit", $headers)) {
            $value = $headers["X-Ratelimit-Limit"];
            $this->rateLimitLimit = (int)(is_array($value) ? ($value[0] ?? null) : $value);
        }
        if (array_key_exists("X-Ratelimit-Remaining", $headers)) {
            $value = $headers["X-Ratelimit-Remaining"];
            $this->rateLimitRemaining = (int)(is_array($value) ? ($value[0] ?? null) : $value);
        }
        if (array_key_exists("X-Ratelimit-Reset", $headers)) {
            $value = $headers["X-Ratelimit-Reset"];
            $this->rateLimitReset = (int)(is_array($value) ? ($value[0] ?? null) : $value);
        }
    }
    
    /**
     * Returns X-RateLimit-Limit header from the response.
     * This number represents the he maximum number of requests that the current client can
     * make in one hour.
     * @see https://developers.stellar.org Stellar developer docs Rate limiting documentation
     */
    public function getRateLimitLimit() : ?int {
        return $this->rateLimitLimit;
    }
    
    /**
     * Returns X-RateLimit-Remaining header from the response.
     * The number of remaining requests for the current window.
     * @see https://developers.stellar.org Stellar developer docs Rate limiting documentation
     */
    public function getRateLimitRemaining() : ?int {
        return $this->rateLimitRemaining;
    }
    
   /**
   * Returns X-RateLimit-Reset header from the response. Seconds until a new window starts.
   * @see https://developers.stellar.org Stellar developer docs Rate limiting documentation
   */
    public function getRateLimitReset() : ?int {
        return $this->rateLimitReset;
    }
    
    /**
     * Loads response data from a JSON array
     *
     * This method is used internally to populate response objects from parsed JSON.
     * Subclasses override this to extract their specific data fields.
     *
     * @param array $json Associative array of parsed JSON data
     * @return void
     */
    protected function loadFromJson(array $json) : void {
        if (isset($json['rateLimitLimit'])) $this->rateLimitLimit = $json['rateLimitLimit'];
        if (isset($json['rateLimitRemaining'])) $this->rateLimitRemaining = $json['rateLimitRemaining'];
        if (isset($json['rateLimitReset'])) $this->rateLimitReset = $json['rateLimitReset'];
    }

    /**
     * Sets the HTTP client for making follow-up requests
     *
     * Paginated responses contain links to next/previous pages. This HTTP client
     * is used when following those links to fetch additional pages.
     *
     * @param Client|null $httpClient The Guzzle HTTP client to use for pagination
     * @return void
     */
    public function setHttpClient(?Client $httpClient = null) : void {
        $this->httpClient = $httpClient;
    }

    /**
     * Gets the HTTP client used for pagination requests
     *
     * @return Client|null The HTTP client instance, or null if not set
     */
    public function getHttpClient(): ?Client
    {
        return $this->httpClient;
    }
}


