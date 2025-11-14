<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

/**
 * Represents an account inflation destination updated effect from the Stellar network
 *
 * This effect occurs when an account's inflation destination is modified.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse Base effect class
 * @see https://developers.stellar.org Stellar developer docs Horizon Effects API
 * @since 1.0.0
 */
class AccountInflationDestinationUpdatedEffectResponse extends EffectResponse
{
    public static function fromJson(array $jsonData) : AccountInflationDestinationUpdatedEffectResponse {
        $result = new AccountInflationDestinationUpdatedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}