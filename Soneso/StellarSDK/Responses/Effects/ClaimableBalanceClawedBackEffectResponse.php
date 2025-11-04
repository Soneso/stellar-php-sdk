<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

use Soneso\StellarSDK\Asset;

/**
 * Represents an effect when a claimable balance is clawed back by the asset issuer
 *
 * This effect occurs when an asset issuer uses the ClawbackClaimableBalance operation
 * to revoke a claimable balance. Only assets with the AUTH_CLAWBACK_ENABLED flag can be clawed back.
 * Triggered by ClawbackClaimableBalance operations.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse
 * @see https://developers.stellar.org/docs/encyclopedia/claimable-balances
 * @see https://developers.stellar.org/api/resources/effects
 */
class ClaimableBalanceClawedBackEffectResponse extends EffectResponse
{
    private string $balanceId;

    /**
     * Gets the unique identifier of the clawed back claimable balance
     *
     * @return string The claimable balance ID
     */
    public function getBalanceId(): string
    {
        return $this->balanceId;
    }

    /**
     * Loads object data from JSON array
     *
     * @param array $json JSON data array
     * @return void
     */
    protected function loadFromJson(array $json) : void {
        if (isset($json['balance_id'])) $this->balanceId = $json['balance_id'];
        parent::loadFromJson($json);
    }

    /**
     * Creates an instance from JSON data
     *
     * @param array $jsonData JSON data array
     * @return ClaimableBalanceClawedBackEffectResponse
     */
    public static function fromJson(array $jsonData) : ClaimableBalanceClawedBackEffectResponse {
        $result = new ClaimableBalanceClawedBackEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}