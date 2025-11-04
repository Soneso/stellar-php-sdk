<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

/**
 * Represents a signer sponsorship created effect from the Stellar network
 *
 * This effect occurs when sponsorship for a signer's base reserve is established.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse Base effect class
 * @see https://developers.stellar.org/api/resources/effects Horizon Effects API
 * @since 1.0.0
 */
class SignerSponsorshipCreatedEffectResponse extends EffectResponse
{
    private string $signer;
    private string $sponsor;

    /**
     * Gets the signer's public key
     *
     * @return string The signer's public key
     */
    public function getSigner(): string
    {
        return $this->signer;
    }

    /**
     * Gets the account ID of the sponsor
     *
     * @return string The sponsor's account ID
     */
    public function getSponsor(): string
    {
        return $this->sponsor;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['sponsor'])) $this->sponsor = $json['sponsor'];
        if (isset($json['signer'])) $this->signer = $json['signer'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : SignerSponsorshipCreatedEffectResponse {
        $result = new SignerSponsorshipCreatedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}