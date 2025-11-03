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
 * Utility for funding test accounts on the Stellar Futurenet
 *
 * FriendBot is a service that funds accounts on the Stellar Futurenet with test XLM.
 * Futurenet is used for testing upcoming protocol features before they reach testnet.
 *
 * @package Soneso\StellarSDK\Util
 * @see FriendBot For funding accounts on the regular test network
 * @see CustomFriendBot For using a custom FriendBot endpoint
 * @see https://developers.stellar.org/docs/tools/developer-tools#friendbot Documentation on FriendBot
 */
class FuturenetFriendBot
{
    /**
     * Funds a test account on the Stellar Futurenet
     *
     * @param string $accountId The Stellar account ID (public key) to fund
     * @return bool True if funding succeeded, false otherwise
     */
    static function fundTestAccount(string $accountId): bool
    {
        try {
            $httpClient = new Client();
            $url = "https://friendbot-futurenet.stellar.org?addr=" . $accountId;
            $request = new Request('GET', $url, RequestBuilder::HEADERS);
            $response = $httpClient->send($request);
            if ($response->getStatusCode() == NetworkConstants::HTTP_OK) {
                return true;
            }
        } catch (GuzzleException $e) {
            print($e->getTraceAsString());
        }
        return false;
    }
}
