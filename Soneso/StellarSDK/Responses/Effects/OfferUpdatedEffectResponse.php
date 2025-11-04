<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

/**
 * Represents an effect when an existing offer is modified on the DEX
 *
 * This effect occurs when an account updates the price or amount of an existing
 * offer on the order book. Triggered by ManageBuyOffer or ManageSellOffer operations
 * that modify existing offers.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse
 * @see https://developers.stellar.org/docs/encyclopedia/liquidity-on-stellar-sdex-liquidity-pools
 * @see https://developers.stellar.org/api/resources/effects
 */
class OfferUpdatedEffectResponse extends EffectResponse
{
    /**
     * Creates an instance from JSON data
     *
     * @param array $jsonData JSON data array
     * @return OfferUpdatedEffectResponse
     */
    public static function fromJson(array $jsonData) : OfferUpdatedEffectResponse {
        $result = new OfferUpdatedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}
