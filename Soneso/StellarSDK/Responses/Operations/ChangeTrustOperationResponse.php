<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Operations;

use Soneso\StellarSDK\Asset;

class ChangeTrustOperationResponse extends OperationResponse
{
    private string $trustor;
    private ?string $trustorMuxed = null;
    private ?string $trustorMuxedId = null;
    private ?string $trustee = null;
    private Asset $asset;
    private ?string $limit = null;
    private ?string $liquidityPoolId = null;

    /**
     * @return string
     */
    public function getTrustor(): string
    {
        return $this->trustor;
    }

    /**
     * @return string|null
     */
    public function getTrustorMuxed(): ?string
    {
        return $this->trustorMuxed;
    }

    /**
     * @return string|null
     */
    public function getTrustorMuxedId(): ?string
    {
        return $this->trustorMuxedId;
    }

    /**
     * @return string|null
     */
    public function getTrustee(): ?string
    {
        return $this->trustee;
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
    public function getLimit(): ?string
    {
        return $this->limit;
    }

    /**
     * @return string|null
     */
    public function getLiquidityPoolId(): ?string
    {
        return $this->liquidityPoolId;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['trustor'])) $this->trustor = $json['trustor'];
        if (isset($json['trustor_muxed'])) $this->trustorMuxed = $json['trustor_muxed'];
        if (isset($json['trustor_muxed_id'])) $this->trustorMuxedId = $json['trustor_muxed_id'];
        if (isset($json['trustee'])) $this->trustee = $json['trustee'];
        if (isset($json['limit'])) $this->limit = $json['limit'];
        if (isset($json['liquidity_pool_id'])) $this->liquidityPoolId = $json['liquidity_pool_id'];

        if (isset($json['asset_type'])) {
            $assetCode = $json['asset_code'] ?? null;
            $assetIssuer = $json['asset_issuer'] ?? null;
            $this->asset = Asset::create($json['asset_type'], $assetCode, $assetIssuer);
        }


        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : ChangeTrustOperationResponse {
        $result = new ChangeTrustOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}