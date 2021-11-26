<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

use Soneso\StellarSDK\Asset;

class ClaimableBalanceCreatedEffectResponse extends EffectResponse
{
    private string $balanceId;
    private Asset $asset;
    private string $amount;

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
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : ClaimableBalanceCreatedEffectResponse {
        $result = new ClaimableBalanceCreatedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}