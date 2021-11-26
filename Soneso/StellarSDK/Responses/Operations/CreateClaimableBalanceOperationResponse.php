<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Operations;

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Responses\ClaimableBalances\ClaimantResponse;
use Soneso\StellarSDK\Responses\ClaimableBalances\ClaimantsResponse;

class CreateClaimableBalanceOperationResponse extends OperationResponse
{
    private string $sponsor;
    private string $amount;
    private Asset $asset;
    private ClaimantsResponse $claimants;

    /**
     * @return string
     */
    public function getSponsor(): string
    {
        return $this->sponsor;
    }

    /**
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * @return Asset
     */
    public function getAsset(): Asset
    {
        return $this->asset;
    }

    /**
     * @return ClaimantsResponse
     */
    public function getClaimants(): ClaimantsResponse
    {
        return $this->claimants;
    }

    protected function loadFromJson(array $json): void
    {
        if (isset($json['sponsor'])) $this->sponsor = $json['sponsor'];
        if (isset($json['amount'])) $this->amount = $json['amount'];
        if (isset($json['asset'])) {
            $parsedAsset = Asset::createFromCanonicalForm($json['asset']);
            if ($parsedAsset != null) {
                $this->asset = $parsedAsset;
            }
        }

        if (isset($json['claimants'])) {
            $this->claimants = new ClaimantsResponse();
            foreach ($json['claimants'] as $jsonClaimants) {
                $claimant = ClaimantResponse::fromJson($jsonClaimants);
                $this->claimants->add($claimant);
            }
        }

        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData): CreateClaimableBalanceOperationResponse
    {
        $result = new CreateClaimableBalanceOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}