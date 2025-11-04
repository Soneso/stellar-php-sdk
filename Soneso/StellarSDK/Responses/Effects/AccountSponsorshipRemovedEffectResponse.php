<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

/**
 * Represents an account sponsorship removed effect from the Stellar network
 *
 * This effect occurs when sponsorship for an account's base reserve is removed.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse Base effect class
 * @see https://developers.stellar.org/api/resources/effects Horizon Effects API
 * @since 1.0.0
 */
class AccountSponsorshipRemovedEffectResponse extends EffectResponse
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

    protected function loadFromJson(array $json) : void {
        if (isset($json['former_sponsor'])) $this->formerSponsor = $json['former_sponsor'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : AccountSponsorshipRemovedEffectResponse {
        $result = new AccountSponsorshipRemovedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}