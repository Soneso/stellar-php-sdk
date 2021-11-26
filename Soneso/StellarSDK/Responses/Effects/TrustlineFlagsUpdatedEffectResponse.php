<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

use Soneso\StellarSDK\Asset;

class TrustlineFlagsUpdatedEffectResponse extends EffectResponse
{

    private Asset $asset;
    private ?string $trustor = null;
    private ?bool $authorizedFlag;
    private ?bool $authorizedToMaintainLiabilitiesFlag;
    private ?bool $clawbackEnabledFlag;

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
     * @return bool|null
     */
    public function getAuthorizedFlag(): ?bool
    {
        return $this->authorizedFlag;
    }

    /**
     * @return bool|null
     */
    public function getAuthorizedToMaintainLiabilitiesFlag(): ?bool
    {
        return $this->authorizedToMaintainLiabilitiesFlag;
    }

    /**
     * @return bool|null
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
        if (isset($json['authorized_to_maintain_liabilites_flag'])) $this->authorizedToMaintainLiabilitiesFlag = $json['authorized_to_maintain_liabilites_flag'];
        if (isset($json['clawback_enabled_flag'])) $this->clawbackEnabledFlag = $json['clawback_enabled_flag'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : TrustlineFlagsUpdatedEffectResponse {
        $result = new TrustlineFlagsUpdatedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}