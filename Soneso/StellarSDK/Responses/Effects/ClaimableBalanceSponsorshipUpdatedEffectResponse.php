<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

/**
 * Represents an effect when sponsorship is transferred for a claimable balance
 *
 * This effect occurs when sponsorship of a claimable balance's base reserve is transferred
 * from one account to another. The new sponsor assumes responsibility for the reserve
 * requirement. Triggered by operations that change sponsorship.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse
 * @see https://developers.stellar.org/docs/encyclopedia/sponsored-reserves
 * @see https://developers.stellar.org/api/resources/effects
 */
class ClaimableBalanceSponsorshipUpdatedEffectResponse extends EffectResponse
{
    private string $newSponsor;
    private string $formerSponsor;

    /**
     * Gets the account ID of the new sponsor
     *
     * @return string The new sponsor's account ID
     */
    public function getNewSponsor(): string
    {
        return $this->newSponsor;
    }

    /**
     * Gets the account ID of the former sponsor
     *
     * @return string The former sponsor's account ID
     */
    public function getFormerSponsor(): string
    {
        return $this->formerSponsor;
    }

    /**
     * Loads object data from JSON array
     *
     * @param array $json JSON data array
     * @return void
     */
    protected function loadFromJson(array $json) : void {

        if (isset($json['new_sponsor'])) $this->newSponsor = $json['new_sponsor'];
        if (isset($json['former_sponsor'])) $this->formerSponsor = $json['former_sponsor'];
        parent::loadFromJson($json);
    }

    /**
     * Creates an instance from JSON data
     *
     * @param array $jsonData JSON data array
     * @return ClaimableBalanceSponsorshipUpdatedEffectResponse
     */
    public static function fromJson(array $jsonData) : ClaimableBalanceSponsorshipUpdatedEffectResponse {
        $result = new ClaimableBalanceSponsorshipUpdatedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}