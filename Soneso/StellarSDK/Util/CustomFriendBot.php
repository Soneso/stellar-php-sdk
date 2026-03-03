<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Util;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Soneso\StellarSDK\Constants\NetworkConstants;
use Soneso\StellarSDK\Requests\RequestBuilder;

/**
 * Utility for funding accounts using a custom FriendBot endpoint
 *
 * This class allows you to use a custom FriendBot service, useful for local development
 * with standalone Stellar networks or private test networks.
 *
 * Warning: This service should only be used with test networks.
 * Never attempt to use FriendBot with mainnet accounts.
 *
 * Example:
 * ```php
 * $bot = new CustomFriendBot("http://localhost:8000/friendbot");
 * $success = $bot->fundAccount("GABC...XYZ");
 * if ($success) {
 *     echo "Account funded successfully";
 * }
 * ```
 *
 * @package Soneso\StellarSDK\Util
 * @see FriendBot For funding accounts on the official test network
 * @see FuturenetFriendBot For funding accounts on Futurenet
 */
class CustomFriendBot
{
    /**
     * CustomFriendBot constructor
     *
     * @param string $friendBotUrl The URL of the custom FriendBot service endpoint (e.g., "http://localhost:8000/friendbot")
     */
    public function __construct(
        public string $friendBotUrl,
    ) {
    }

    /**
     * Funds an account using the custom FriendBot endpoint
     *
     * @param string $accountId The Stellar account ID (56-character public key starting with 'G') to fund
     * @return bool True if funding succeeded, false otherwise
     * @throws GuzzleException If the HTTP request fails
     */
    public function fundAccount(string $accountId, ?Client $httpClient = null): bool
    {
        $httpClient = $httpClient ?? new Client();
        $url = $this->friendBotUrl . "?addr=" . urlencode($accountId);
        $request = new Request('GET', $url, RequestBuilder::HEADERS);
        $response = $httpClient->send($request);
        if ($response->getStatusCode() == NetworkConstants::HTTP_OK) {
            return true;
        }
        return false;
    }

    /**
     * Gets the configured FriendBot URL
     *
     * @return string The FriendBot endpoint URL
     */
    public function getFriendBotUrl(): string
    {
        return $this->friendBotUrl;
    }

    /**
     * Sets a new FriendBot URL
     *
     * @param string $friendBotUrl The FriendBot endpoint URL
     * @return void
     */
    public function setFriendBotUrl(string $friendBotUrl): void
    {
        $this->friendBotUrl = $friendBotUrl;
    }


}
