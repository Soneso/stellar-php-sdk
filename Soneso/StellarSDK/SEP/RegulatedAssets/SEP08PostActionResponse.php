<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\RegulatedAssets;

abstract class SEP08PostActionResponse
{
    /**
     * @throws SEP08InvalidPostActionResponse
     */
    public static function fromJson(array $json) : SEP08PostActionResponse
    {
        if (!isset($json['result'])) {
            throw new SEP08InvalidPostActionResponse("Missing result in response");
        }
        $result = $json['result'];
        if ('no_further_action_required' === $result) {
            return new SEP08PostActionDone();
        } else if ('follow_next_url' === $result) {
            if (!isset($json['next_url'])) {
                throw new SEP08InvalidPostActionResponse("Missing next_url in response");
            }
            $message = $json['message'] ?? null;
            return new SEP08PostActionNextUrl(nextUrl: $json['next_url'], message: $message);
        } else {
            throw new SEP08InvalidPostActionResponse("Unknown result: " . $result. " in response");
        }
    }
}