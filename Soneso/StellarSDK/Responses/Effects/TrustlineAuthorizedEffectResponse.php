<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

/**
 * Represents a trustline authorized effect from the Stellar network
 *
 * This effect occurs when an asset issuer authorizes a trustline.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see TrustlineEffectResponse Base trustline effect class
 * @see https://developers.stellar.org Stellar developer docs Horizon Effects API
 * @since 1.0.0
 */
class TrustlineAuthorizedEffectResponse extends TrustlineEffectResponse
{
    public static function fromJson(array $jsonData) : TrustlineAuthorizedEffectResponse {
        $result = new TrustlineAuthorizedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}