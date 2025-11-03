<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Operations;

use Soneso\StellarSDK\Asset;

/**
 * Represents a change trust operation response from Horizon API
 *
 * This operation creates, updates, or deletes a trustline from the source account to an asset
 * or liquidity pool. Contains trustline details including trustor, trustee, asset, and limit.
 *
 * @package Soneso\StellarSDK\Responses\Operations
 * @see OperationResponse Base operation response
 * @see https://developers.stellar.org/api/resources/operations/object/change-trust Horizon Change Trust
 * @since 1.0.0
 */
class ChangeTrustOperationResponse extends OperationResponse
{
    private string $trustor;
    private ?string $trustorMuxed = null;
    private ?string $trustorMuxedId = null;
    private ?string $trustee = null;
    private string $assetType;
    private ?string $assetCode = null;
    private ?string $assetIssuer = null;
    private ?string $limit = null;
    private ?string $liquidityPoolId = null;

    /**
     * Gets the account creating or modifying the trustline
     *
     * @return string The trustor account ID
     */
    public function getTrustor(): string
    {
        return $this->trustor;
    }

    /**
     * Gets the multiplexed trustor account if applicable
     *
     * @return string|null The muxed trustor account address or null
     */
    public function getTrustorMuxed(): ?string
    {
        return $this->trustorMuxed;
    }

    /**
     * Gets the multiplexed trustor account ID if applicable
     *
     * @return string|null The muxed trustor account ID or null
     */
    public function getTrustorMuxedId(): ?string
    {
        return $this->trustorMuxedId;
    }

    /**
     * Gets the account or pool being trusted
     *
     * @return string|null The trustee account ID or null for liquidity pools
     */
    public function getTrustee(): ?string
    {
        return $this->trustee;
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
     * Gets the asset code if applicable
     *
     * @return string|null The asset code or null for native or pools
     */
    public function getAssetCode(): ?string
    {
        return $this->assetCode;
    }

    /**
     * Gets the asset issuer if applicable
     *
     * @return string|null The issuer account ID or null for native or pools
     */
    public function getAssetIssuer(): ?string
    {
        return $this->assetIssuer;
    }

    /**
     * Gets the trust limit
     *
     * @return string|null The maximum amount that can be held, or null for unlimited
     */
    public function getLimit(): ?string
    {
        return $this->limit;
    }

    /**
     * Gets the liquidity pool ID if trusting a pool
     *
     * @return string|null The liquidity pool ID or null if trusting an asset
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
        if (isset($json['asset_type'])) $this->assetType = $json['asset_type'];
        if (isset($json['asset_code'])) $this->assetCode = $json['asset_code'];
        if (isset($json['asset_issuer'])) $this->assetIssuer = $json['asset_issuer'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : ChangeTrustOperationResponse {
        $result = new ChangeTrustOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}