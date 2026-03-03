<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

use Soneso\StellarSDK\Asset;

/**
 * Represents an effect when a claimable balance is created
 *
 * This effect occurs when a claimable balance is initially created on the ledger.
 * Claimable balances allow sponsors to deposit funds that claimants can claim later
 * based on defined predicates. Triggered by CreateClaimableBalance operations.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse
 * @see https://developers.stellar.org Stellar developer docs
 */
class ClaimableBalanceCreatedEffectResponse extends EffectResponse
{
    private string $balanceId;
    private Asset $asset;
    private string $amount;

    /**
     * Gets the unique identifier of the created claimable balance
     *
     * @return string The claimable balance ID
     */
    public function getBalanceId(): string
    {
        return $this->balanceId;
    }

    /**
     * Gets the asset deposited in the claimable balance
     *
     * @return Asset The asset
     */
    public function getAsset(): Asset
    {
        return $this->asset;
    }

    /**
     * Gets the amount of the asset deposited
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
            if ($parsedAsset !== null) {
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
     * @return ClaimableBalanceCreatedEffectResponse
     */
    public static function fromJson(array $jsonData) : ClaimableBalanceCreatedEffectResponse {
        $result = new ClaimableBalanceCreatedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}
