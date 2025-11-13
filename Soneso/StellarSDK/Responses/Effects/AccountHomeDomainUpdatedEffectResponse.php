<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

/**
 * Represents an account home domain updated effect from the Stellar network
 *
 * This effect occurs when an account's home domain is modified.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse Base effect class
 * @see https://developers.stellar.org Stellar developer docs Horizon Effects API
 * @since 1.0.0
 */
class AccountHomeDomainUpdatedEffectResponse extends EffectResponse
{
    private string $homeDomain;

    /**
     * Gets the new home domain for the account
     *
     * @return string The home domain value
     */
    public function getHomeDomain(): string {
        return $this->homeDomain;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['home_domain'])) $this->homeDomain = $json['home_domain'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : AccountHomeDomainUpdatedEffectResponse {
        $result = new AccountHomeDomainUpdatedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}