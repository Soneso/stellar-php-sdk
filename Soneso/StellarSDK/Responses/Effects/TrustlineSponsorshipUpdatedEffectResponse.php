<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

class TrustlineSponsorshipUpdatedEffectResponse extends EffectResponse
{
    private string $newSponsor;
    private string $formerSponsor;
    private ?string $asset = null;
    private ?string $assetType = null;
    private ?string $liquidityPoolId = null;

    /**
     * @return string
     */
    public function getNewSponsor(): string
    {
        return $this->newSponsor;
    }

    /**
     * @return string
     */
    public function getFormerSponsor(): string
    {
        return $this->formerSponsor;
    }

    /**
     * @return string|null
     */
    public function getAsset(): ?string
    {
        return $this->asset;
    }

    /**
     * @return string|null
     */
    public function getAssetType(): ?string
    {
        return $this->assetType;
    }

    /**
     * @return string|null
     */
    public function getLiquidityPoolId(): ?string
    {
        return $this->liquidityPoolId;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['new_sponsor'])) $this->newSponsor = $json['new_sponsor'];
        if (isset($json['former_sponsor'])) $this->formerSponsor = $json['former_sponsor'];
        if (isset($json['asset'])) $this->asset = $json['asset'];
        if (isset($json['asset_type'])) $this->assetType = $json['asset_type'];
        if (isset($json['liquidity_pool_id'])) $this->liquidityPoolId = $json['liquidity_pool_id'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : TrustlineSponsorshipUpdatedEffectResponse {
        $result = new TrustlineSponsorshipUpdatedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}