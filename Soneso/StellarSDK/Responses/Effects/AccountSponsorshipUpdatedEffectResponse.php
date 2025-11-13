<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

/**
 * Represents an account sponsorship updated effect from the Stellar network
 *
 * This effect occurs when an account's sponsorship is transferred from one sponsor to another.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse Base effect class
 * @see https://developers.stellar.org Stellar developer docs Horizon Effects API
 * @since 1.0.0
 */
class AccountSponsorshipUpdatedEffectResponse extends EffectResponse
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

    protected function loadFromJson(array $json) : void {
        if (isset($json['new_sponsor'])) $this->newSponsor = $json['new_sponsor'];
        if (isset($json['former_sponsor'])) $this->formerSponsor = $json['former_sponsor'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : AccountSponsorshipUpdatedEffectResponse {
        $result = new AccountSponsorshipUpdatedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}