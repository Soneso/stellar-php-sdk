<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

/**
 * Represents an effect when an offer is created on the DEX
 *
 * This effect occurs when an account creates a new buy or sell offer on the
 * Stellar decentralized exchange. Triggered by ManageBuyOffer or ManageSellOffer
 * operations.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse
 * @see https://developers.stellar.org/docs/encyclopedia/liquidity-on-stellar-sdex-liquidity-pools
 * @see https://developers.stellar.org/api/resources/effects
 */
class OfferCreatedEffectResponse extends EffectResponse
{
    /**
     * Creates an instance from JSON data
     *
     * @param array $jsonData JSON data array
     * @return OfferCreatedEffectResponse
     */
    public static function fromJson(array $jsonData) : OfferCreatedEffectResponse {
        $result = new OfferCreatedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}
