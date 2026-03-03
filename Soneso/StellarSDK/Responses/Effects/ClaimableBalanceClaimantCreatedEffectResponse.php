<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Responses\ClaimableBalances\ClaimantPredicateResponse;

/**
 * Represents an effect when a claimant is added to a claimable balance
 *
 * This effect occurs when a claimable balance is created with a specific claimant.
 * Each claimant has associated predicates that define when they can claim the balance.
 * Triggered by CreateClaimableBalance operations.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse
 * @see https://developers.stellar.org Stellar developer docs
 */
class ClaimableBalanceClaimantCreatedEffectResponse extends EffectResponse
{
    private string $balanceId;
    private Asset $asset;
    private string $amount;
    private ClaimantPredicateResponse $predicate;

    /**
     * Gets the predicates that must be satisfied for this claimant to claim the balance
     *
     * @return ClaimantPredicateResponse The claimant predicates
     */
    public function getPredicate(): ClaimantPredicateResponse
    {
        return $this->predicate;
    }

    /**
     * Gets the unique identifier of the claimable balance
     *
     * @return string The claimable balance ID
     */
    public function getBalanceId(): string
    {
        return $this->balanceId;
    }

    /**
     * Gets the asset held in the claimable balance
     *
     * @return Asset The asset
     */
    public function getAsset(): Asset
    {
        return $this->asset;
    }

    /**
     * Gets the amount of the asset in the claimable balance
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
        if (isset($json['predicate'])) $this->predicate = ClaimantPredicateResponse::fromJson($json['predicate']);
        parent::loadFromJson($json);
    }

    /**
     * Creates an instance from JSON data
     *
     * @param array $jsonData JSON data array
     * @return ClaimableBalanceClaimantCreatedEffectResponse
     */
    public static function fromJson(array $jsonData) : ClaimableBalanceClaimantCreatedEffectResponse {
        $result = new ClaimableBalanceClaimantCreatedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}
