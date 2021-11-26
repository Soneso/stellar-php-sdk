<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

class OfferCreatedEffectResponse extends EffectResponse
{
    public static function fromJson(array $jsonData) : OfferCreatedEffectResponse {
        $result = new OfferCreatedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}