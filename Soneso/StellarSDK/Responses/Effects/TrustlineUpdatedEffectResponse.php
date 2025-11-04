<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

/**
 * Represents a trustline updated effect from the Stellar network
 *
 * This effect occurs when a trustline's properties (such as limit) are modified.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see TrustlineEffectResponse Base trustline effect class
 * @see https://developers.stellar.org/api/resources/effects Horizon Effects API
 * @since 1.0.0
 */
class TrustlineUpdatedEffectResponse extends TrustlineEffectResponse
{
    public static function fromJson(array $jsonData) : TrustlineUpdatedEffectResponse {
        $result = new TrustlineUpdatedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}