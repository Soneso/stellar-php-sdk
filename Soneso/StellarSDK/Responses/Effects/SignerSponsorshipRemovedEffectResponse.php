<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

/**
 * Represents a signer sponsorship removed effect from the Stellar network
 *
 * This effect occurs when sponsorship for a signer's base reserve is removed.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse Base effect class
 * @see https://developers.stellar.org Stellar developer docs Horizon Effects API
 * @since 1.0.0
 */
class SignerSponsorshipRemovedEffectResponse extends EffectResponse
{
    private string $formerSponsor;
    private string $signer;

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
     * Gets the signer's public key
     *
     * @return string The signer's public key
     */
    public function getSigner(): string
    {
        return $this->signer;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['former_sponsor'])) $this->formerSponsor = $json['former_sponsor'];
        if (isset($json['signer'])) $this->signer = $json['signer'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : SignerSponsorshipRemovedEffectResponse {
        $result = new SignerSponsorshipRemovedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}