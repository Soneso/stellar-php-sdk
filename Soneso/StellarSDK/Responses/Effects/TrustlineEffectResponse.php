<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

/**
 * Base class for trustline-related effects from the Stellar network
 *
 * This represents effects that involve changes to trustlines between accounts and assets.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse Base effect class
 * @see TrustlineCreatedEffectResponse When a trustline is created
 * @see TrustlineUpdatedEffectResponse When a trustline is modified
 * @see TrustlineRemovedEffectResponse When a trustline is removed
 * @see https://developers.stellar.org Stellar developer docs Horizon Effects API
 * @since 1.0.0
 */
class TrustlineEffectResponse extends EffectResponse
{
    private string $limit;
    private string $assetType;
    private ?string $assetCode = null;
    private ?string $assetIssuer = null;
    private ?string $liquidityPoolId = null;

    /**
     * Gets the trustline limit
     *
     * @return string The maximum amount of the asset the account trusts
     */
    public function getLimit(): string
    {
        return $this->limit;
    }

    /**
     * Gets the asset type
     *
     * @return string The asset type (native, credit_alphanum4, credit_alphanum12, or liquidity_pool_shares)
     */
    public function getAssetType(): string
    {
        return $this->assetType;
    }

    /**
     * Gets the asset code
     *
     * @return string|null The asset code, or null for native or liquidity pool assets
     */
    public function getAssetCode(): ?string
    {
        return $this->assetCode;
    }

    /**
     * Gets the asset issuer account ID
     *
     * @return string|null The issuer's account ID, or null for native or liquidity pool assets
     */
    public function getAssetIssuer(): ?string
    {
        return $this->assetIssuer;
    }

    /**
     * Gets the liquidity pool ID
     *
     * @return string|null The liquidity pool ID, or null if not a liquidity pool trustline
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