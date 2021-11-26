<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Operations;

use Soneso\StellarSDK\Asset;

class AllowTrustOperationResponse extends OperationResponse
{
    private string $trustor;
    private string $trustee;
    private ?string $trusteeMuxed = null;
    private ?string $trusteeMuxedId = null;
    private Asset $asset;
    private bool $authorize;
    private ?bool $authorizeToMaintainLiabilities = null;

    /**
     * @return string
     */
    public function getTrustor(): string
    {
        return $this->trustor;
    }

    /**
     * @return string
     */
    public function getTrustee(): string
    {
        return $this->trustee;
    }

    /**
     * @return string|null
     */
    public function getTrusteeMuxed(): ?string
    {
        return $this->trusteeMuxed;
    }

    /**
     * @return string|null
     */
    public function getTrusteeMuxedId(): ?string
    {
        return $this->trusteeMuxedId;
    }

    /**
     * @return Asset
     */
    public function getAsset(): Asset
    {
        return $this->asset;
    }

    /**
     * @return bool
     */
    public function isAuthorize(): bool
    {
        return $this->authorize;
    }

    /**
     * @return bool|null
     */
    public function getAuthorizeToMaintainLiabilities(): ?bool
    {
        return $this->authorizeToMaintainLiabilities;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['trustor'])) $this->trustor = $json['trustor'];
        if (isset($json['trustee'])) $this->trustee = $json['trustee'];
        if (isset($json['trustee_muxed'])) $this->trusteeMuxed = $json['trustee_muxed'];
        if (isset($json['trustee_muxed_id'])) $this->trusteeMuxedId = $json['trustee_muxed_id'];
         if (isset($json['authorize'])) $this->authorize = $json['authorize'];
        if (isset($json['authorize_to_maintain_liabilities'])) $this->authorizeToMaintainLiabilities = $json['authorize_to_maintain_liabilities'];

        if (isset($json['asset_type'])) {
            $assetCode = $json['asset_code'] ?? null;
            $assetIssuer = $json['asset_issuer'] ?? null;
            $this->asset = Asset::create($json['asset_type'], $assetCode, $assetIssuer);
        }


        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : AllowTrustOperationResponse {
        $result = new AllowTrustOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }

}