<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

/**
 * Represents an effect when sponsorship is created for a claimable balance
 *
 * This effect occurs when an account agrees to sponsor the base reserve for a claimable balance,
 * covering the ledger entry cost. The sponsor pays the reserve requirement instead of the
 * claimable balance creator. Triggered by operations with sponsorship.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse
 * @see https://developers.stellar.org/docs/encyclopedia/sponsored-reserves
 * @see https://developers.stellar.org/api/resources/effects
 */
class ClaimableBalanceSponsorshipCreatedEffectResponse extends EffectResponse
{
    private string $sponsor;

    /**
     * Gets the account ID of the sponsor
     *
     * @return string The sponsor's account ID
     */
    public function getSponsor(): string
    {
        return $this->sponsor;
    }

    /**
     * Loads object data from JSON array
     *
     * @param array $json JSON data array
     * @return void
     */
    protected function loadFromJson(array $json) : void {

        if (isset($json['sponsor'])) $this->sponsor = $json['sponsor'];
        parent::loadFromJson($json);
    }

    /**
     * Creates an instance from JSON data
     *
     * @param array $jsonData JSON data array
     * @return ClaimableBalanceSponsorshipCreatedEffectResponse
     */
    public static function fromJson(array $jsonData) : ClaimableBalanceSponsorshipCreatedEffectResponse {
        $result = new ClaimableBalanceSponsorshipCreatedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}