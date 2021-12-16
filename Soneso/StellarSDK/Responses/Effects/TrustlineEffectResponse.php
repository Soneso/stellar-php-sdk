<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

class TrustlineEffectResponse extends EffectResponse
{
    private string $limit;
    private string $assetType;
    private ?string $assetCode = null;
    private ?string $assetIssuer = null;
    private ?string $liquidityPoolId = null;

    /**
     * @return string
     */
    public function getLimit(): string
    {
        return $this->limit;
    }

    /**
     * @return string
     */
    public function getAssetType(): string
    {
        return $this->assetType;
    }

    /**
     * @return string|null
     */
    public function getAssetCode(): ?string
    {
        return $this->assetCode;
    }

    /**
     * @return string|null
     */
    public function getAssetIssuer(): ?string
    {
        return $this->assetIssuer;
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
        if (isset($json['asset_type'])) $this->assetType = $json['asset_type'];
        if (isset($json['asset_code'])) $this->assetCode = $json['asset_code'];
        if (isset($json['asset_issuer'])) $this->assetIssuer = $json['asset_issuer'];
        if (isset($json['liquidity_pool_id'])) $this->liquidityPoolId = $json['liquidity_pool_id'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : TrustlineEffectResponse {
        $result = new TrustlineEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}