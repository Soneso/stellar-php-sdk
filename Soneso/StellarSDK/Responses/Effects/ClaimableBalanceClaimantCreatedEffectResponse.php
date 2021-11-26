<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Responses\ClaimableBalances\ClaimantPredicateResponse;

class ClaimableBalanceClaimantCreatedEffectResponse extends EffectResponse
{
    private string $balanceId;
    private Asset $asset;
    private string $amount;
    private ClaimantPredicateResponse $predicate;

    /**
     * @return ClaimantPredicateResponse
     */
    public function getPredicate(): ClaimantPredicateResponse
    {
        return $this->predicate;
    }

    /**
     * @return string
     */
    public function getBalanceId(): string
    {
        return $this->balanceId;
    }

    /**
     * @return Asset
     */
    public function getAsset(): Asset
    {
        return $this->asset;
    }

    /**
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['balance_id'])) $this->balanceId = $json['balance_id'];
        if (isset($json['asset'])) {
            $parsedAsset = Asset::createFromCanonicalForm($json['asset']);
            if ($parsedAsset != null) {
                $this->asset = $parsedAsset;
            }
        }
        if (isset($json['amount'])) $this->amount = $json['amount'];
        if (isset($json['predicate'])) $this->predicate = ClaimantPredicateResponse::fromJson($json['predicate']);
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : ClaimableBalanceClaimantCreatedEffectResponse {
        $result = new ClaimableBalanceClaimantCreatedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}