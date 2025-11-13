<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

use Soneso\StellarSDK\Asset;

/**
 * Represents a trustline flags updated effect from the Stellar network
 *
 * This effect occurs when a trustline's authorization flags are modified by the asset issuer.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse Base effect class
 * @see https://developers.stellar.org Stellar developer docs Horizon Effects API
 * @since 1.0.0
 */
class TrustlineFlagsUpdatedEffectResponse extends EffectResponse
{

    private Asset $asset;
    private ?string $trustor = null;
    private ?bool $authorizedFlag;
    private ?bool $authorizedToMaintainLiabilitiesFlag;
    private ?bool $clawbackEnabledFlag;

    /**
     * Gets the asset for this trustline
     *
     * @return Asset The asset
     */
    public function getAsset(): Asset
    {
        return $this->asset;
    }

    /**
     * Gets the trustor account ID
     *
     * @return string|null The trustor's account ID
     */
    public function getTrustor(): ?string
    {
        return $this->trustor;
    }

    /**
     * Gets the authorized flag state
     *
     * @return bool|null True if trustline is authorized, false otherwise, or null if not set
     */
    public function getAuthorizedFlag(): ?bool
    {
        return $this->authorizedFlag;
    }

    /**
     * Gets the authorized to maintain liabilities flag state
     *
     * @return bool|null True if authorized to maintain liabilities, false otherwise, or null if not set
     */
    public function getAuthorizedToMaintainLiabilitiesFlag(): ?bool
    {
        return $this->authorizedToMaintainLiabilitiesFlag;
    }

    /**
     * Gets the clawback enabled flag state
     *
     * @return bool|null True if clawback is enabled, false otherwise, or null if not set
     */
    public function getClawbackEnabledFlag(): ?bool
    {
        return $this->clawbackEnabledFlag;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['asset_type'])) {
            $assetCode = $json['asset_code'] ?? null;
            $assetIssuer = $json['asset_issuer'] ?? null;
            $this->asset = Asset::create($json['asset_type'], $assetCode, $assetIssuer);
        }
        if (isset($json['trustor'])) $this->trustor = $json['trustor'];
        if (isset($json['authorized_flag'])) $this->authorizedFlag = $json['authorized_flag'];
        if (isset($json['authorized_to_maintain_liabilities_flag'])) $this->authorizedToMaintainLiabilitiesFlag = $json['authorized_to_maintain_liabilities_flag'];
        if (isset($json['clawback_enabled_flag'])) $this->clawbackEnabledFlag = $json['clawback_enabled_flag'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : TrustlineFlagsUpdatedEffectResponse {
        $result = new TrustlineFlagsUpdatedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}