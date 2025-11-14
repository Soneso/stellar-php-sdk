<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

/**
 * Represents an account sponsorship created effect from the Stellar network
 *
 * This effect occurs when sponsorship for an account's base reserve is established.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse Base effect class
 * @see https://developers.stellar.org Stellar developer docs Horizon Effects API
 * @since 1.0.0
 */
class AccountSponsorshipCreatedEffectResponse extends EffectResponse
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

    protected function loadFromJson(array $json) : void {
        if (isset($json['sponsor'])) $this->sponsor = $json['sponsor'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : AccountSponsorshipCreatedEffectResponse {
        $result = new AccountSponsorshipCreatedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}