<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

/**
 * Represents an account created effect response from Horizon API
 *
 * This effect occurs when a new account is created and funded on the Stellar network.
 * Contains the starting balance provided to the new account.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse Base effect response
 * @see https://developers.stellar.org Stellar developer docs Horizon Effects API
 * @since 1.0.0
 */
class AccountCreatedEffectResponse extends EffectResponse
{
    private string $startingBalance;

    /**
     * Gets the initial balance provided to the new account
     *
     * @return string The starting balance in lumens
     */
    public function getStartingBalance(): string {
        return $this->startingBalance;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['starting_balance'])) $this->startingBalance = $json['starting_balance'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : AccountCreatedEffectResponse {
        $result = new AccountCreatedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}