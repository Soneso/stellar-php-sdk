<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Util;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Soneso\StellarSDK\Requests\RequestBuilder;

class FriendBot
{
    static function fundTestAccount(string $accountId) : bool {
        try {
            $httpClient = new Client(['exceptions' => false]);
            $url = "https://friendbot.stellar.org/?addr=" . $accountId;
            $request = new Request('GET', $url, RequestBuilder::HEADERS);
            $response = $httpClient->send($request);
            if ($response->getStatusCode() == 200) {
                return true;
            }
        }
        catch (GuzzleException $e) {
            print($e->getTraceAsString());
        }
        return false;
    }
}