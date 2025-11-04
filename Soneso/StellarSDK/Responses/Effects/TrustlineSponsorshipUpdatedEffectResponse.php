<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

/**
 * Represents a trustline sponsorship updated effect from the Stellar network
 *
 * This effect occurs when a trustline's sponsorship is transferred from one sponsor to another.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse Base effect class
 * @see https://developers.stellar.org/api/resources/effects Horizon Effects API
 * @since 1.0.0
 */
class TrustlineSponsorshipUpdatedEffectResponse extends EffectResponse
{
    private string $newSponsor;
    private string $formerSponsor;
    private ?string $asset = null;
    private ?string $assetType = null;
    private ?string $liquidityPoolId = null;

    /**
     * Gets the account ID of the new sponsor
     *
     * @return string The new sponsor's account ID
     */
    public function getNewSponsor(): string
    {
        return $this->newSponsor;
    }

    /**
     * Gets the account ID of the former sponsor
     *
     * @return string The former sponsor's account ID
     */
    public function getFormerSponsor(): string
    {
        return $this->formerSponsor;
    }

    /**
     * Gets the asset identifier
     *
     * @return string|null The asset identifier, or null if not set
     */
    public function getAsset(): ?string
    {
        return $this->asset;
    }

    /**
     * Gets the asset type
     *
     * @return string|null The asset type, or null if not set
     */
    public function getAssetType(): ?string
    {
        return $this->assetType;
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