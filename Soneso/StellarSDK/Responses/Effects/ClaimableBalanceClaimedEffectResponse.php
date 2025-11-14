<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

use Soneso\StellarSDK\Asset;

/**
 * Represents an effect when a claimable balance is claimed by a claimant
 *
 * This effect occurs when a claimant successfully claims a claimable balance.
 * The balance is removed from the ledger and transferred to the claimant's account.
 * Triggered by ClaimClaimableBalance operations.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse
 * @see https://developers.stellar.org Stellar developer docs
 */
class ClaimableBalanceClaimedEffectResponse extends EffectResponse
{
    private string $balanceId;
    private Asset $asset;
    private string $amount;

    /**
     * Gets the unique identifier of the claimed claimable balance
     *
     * @return string The claimable balance ID
     */
    public function getBalanceId(): string
    {
        return $this->balanceId;
    }

    /**
     * Gets the asset that was claimed
     *
     * @return Asset The asset
     */
    public function getAsset(): Asset
    {
        return $this->asset;
    }

    /**
     * Gets the amount of the asset that was claimed
     *
     * @return string The amount
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Loads object data from JSON array
     *
     * @param array $json JSON data array
     * @return void
     */
    protected function loadFromJson(array $json) : void {
        if (isset($json['balance_id'])) $this->balanceId = $json['balance_id'];
        if (isset($json['asset'])) {
            $parsedAsset = Asset::createFromCanonicalForm($json['asset']);
            if ($parsedAsset != null) {
                $this->asset = $parsedAsset;
            }
        }
        if (isset($json['amount'])) $this->amount = $json['amount'];
        parent::loadFromJson($json);
    }

    /**
     * Creates an instance from JSON data
     *
     * @param array $jsonData JSON data array
     * @return ClaimableBalanceClaimedEffectResponse
     */
    public static function fromJson(array $jsonData) : ClaimableBalanceClaimedEffectResponse {
        $result = new ClaimableBalanceClaimedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}
