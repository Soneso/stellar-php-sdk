<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

/**
 * Represents a signer created effect from the Stellar network
 *
 * This effect occurs when a new signer is added to an account.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see SignerEffectResponse Base signer effect class
 * @see https://developers.stellar.org/api/resources/effects Horizon Effects API
 * @since 1.0.0
 */
class SignerCreatedEffectResponse extends SignerEffectResponse
{
    public static function fromJson(array $jsonData) : SignerCreatedEffectResponse {
        $result = new SignerCreatedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}