<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

/**
 * Represents an effect when sponsorship is removed from a claimable balance
 *
 * This effect occurs when a sponsor relinquishes responsibility for covering the base reserve
 * of a claimable balance. The ledger entry becomes unsponsored and reserve requirements
 * are transferred. Triggered by RevokeSponsorship operations.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse
 * @see https://developers.stellar.org Stellar developer docs
 */
class ClaimableBalanceSponsorshipRemovedEffectResponse extends EffectResponse
{
    private string $formerSponsor;

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
        if (isset($json['former_sponsor'])) $this->formerSponsor = $json['former_sponsor'];
        parent::loadFromJson($json);
    }

    /**
     * Creates an instance from JSON data
     *
     * @param array $jsonData JSON data array
     * @return ClaimableBalanceSponsorshipRemovedEffectResponse
     */
    public static function fromJson(array $jsonData) : ClaimableBalanceSponsorshipRemovedEffectResponse {
        $result = new ClaimableBalanceSponsorshipRemovedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}
