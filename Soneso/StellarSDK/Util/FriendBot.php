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
 * Utility for funding test accounts on the Stellar test network
 *
 * FriendBot is a service that funds accounts on the Stellar test network with test XLM.
 * This is useful for development and testing purposes. Do not use on the public network.
 *
 * Warning: This service should only be used with test networks.
 * Never attempt to use FriendBot with mainnet accounts.
 *
 * Example:
 * ```php
 * $accountId = "GABC...XYZ";
 * if (FriendBot::fundTestAccount($accountId)) {
 *     echo "Test account funded successfully";
 * }
 * ```
 *
 * @package Soneso\StellarSDK\Util
 * @see FuturenetFriendBot For funding accounts on Futurenet
 * @see CustomFriendBot For using a custom FriendBot endpoint
 * @see https://developers.stellar.org Stellar developer docs Documentation on FriendBot
 */
class FriendBot
{
    /**
     * Funds a test account on the Stellar test network
     *
     * @static
     * @param string $accountId The Stellar account ID (56-character public key starting with 'G') to fund
     * @return bool True if funding succeeded, false otherwise
     * @throws GuzzleException If the HTTP request fails
     */
    static function fundTestAccount(string $accountId) : bool {
        $httpClient = new Client();
        $url = "https://friendbot.stellar.org/?addr=" . urlencode($accountId);
        $request = new Request('GET', $url, RequestBuilder::HEADERS);
        $response = $httpClient->send($request);
        if ($response->getStatusCode() == NetworkConstants::HTTP_OK) {
            return true;
        }
        return false;
    }
}