<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

use Soneso\StellarSDK\Asset;

class TrustlineEffectResponse extends EffectResponse
{
    private string $limit;
    private Asset $asset;
    private ?string $trustor = null;
    private ?string $liquidityPoolId = null;

    /**
     * @return string
     */
    public function getLimit(): string
    {
        return $this->limit;
    }

    /**
     * @return Asset
     */
    public function getAsset(): Asset
    {
        return $this->asset;
    }

    /**
     * @return string|null
     */
    public function getTrustor(): ?string
    {
        return $this->trustor;
    }

    /**
     * @return string|null
     */
    public function getLiquidityPoolId(): ?string
    {
        return $this->liquidityPoolId;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['limit'])) $this->limit = $json['limit'];
        if (isset($json['asset_type'])) {
            $assetCode = $json['asset_code'] ?? null;
            $assetIssuer = $json['asset_issuer'] ?? null;
            $this->asset = Asset::create($json['asset_type'], $assetCode, $assetIssuer);
        }
        if (isset($json['trustor'])) $this->trustor = $json['trustor'];
        if (isset($json['liquidity_pool_id'])) $this->liquidityPoolId = $json['liquidity_pool_id'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : TrustlineEffectResponse {
        $result = new TrustlineEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}