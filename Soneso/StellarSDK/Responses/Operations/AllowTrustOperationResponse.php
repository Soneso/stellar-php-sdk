<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Operations;

use Soneso\StellarSDK\Asset;

/**
 * Represents an allow trust operation response from Horizon API
 *
 * This deprecated operation updates trustline authorization flags for an asset. The asset issuer
 * can authorize or deauthorize accounts to hold the asset, or allow them to maintain liabilities
 * only. This operation has been superseded by SetTrustlineFlagsOperation which provides more
 * granular control over trustline flags.
 *
 * @package Soneso\StellarSDK\Responses\Operations
 * @see OperationResponse Base operation response
 * @see https://developers.stellar.org Stellar developer docs Horizon Allow Trust Operation
 * @deprecated Use SetTrustlineFlagsOperation instead
 */
class AllowTrustOperationResponse extends OperationResponse
{
    private string $trustor;
    private string $trustee;
    private ?string $trusteeMuxed = null;
    private ?string $trusteeMuxedId = null;
    private string $assetType;
    private ?string $assetCode = null;
    private ?string $assetIssuer = null;
    private bool $authorize;
    private ?bool $authorizeToMaintainLiabilities = null;

    /**
     * Gets the account holding the trustline
     *
     * @return string The trustor account ID
     */
    public function getTrustor(): string
    {
        return $this->trustor;
    }

    /**
     * Gets the asset issuer account
     *
     * @return string The trustee (issuer) account ID
     */
    public function getTrustee(): string
    {
        return $this->trustee;
    }

    /**
     * Gets the multiplexed trustee account if applicable
     *
     * @return string|null The muxed trustee account address or null
     */
    public function getTrusteeMuxed(): ?string
    {
        return $this->trusteeMuxed;
    }

    /**
     * Gets the multiplexed trustee account ID if applicable
     *
     * @return string|null The muxed trustee account ID or null
     */
    public function getTrusteeMuxedId(): ?string
    {
        return $this->trusteeMuxedId;
    }

    /**
     * Gets the asset type
     *
     * @return string The asset type (native, credit_alphanum4, or credit_alphanum12)
     */
    public function getAssetType(): string
    {
        return $this->assetType;
    }

    /**
     * Gets the asset code
     *
     * @return string|null The asset code or null for native assets
     */
    public function getAssetCode(): ?string
    {
        return $this->assetCode;
    }

    /**
     * Gets the asset issuer
     *
     * @return string|null The asset issuer account ID or null for native assets
     */
    public function getAssetIssuer(): ?string
    {
        return $this->assetIssuer;
    }

    /**
     * Checks if the trustline is authorized
     *
     * @return bool True if authorized, false otherwise
     */
    public function isAuthorize(): bool
    {
        return $this->authorize;
    }

    /**
     * Checks if the trustline is authorized to maintain liabilities only
     *
     * @return bool|null True if authorized to maintain liabilities only, false if not, null if not set
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
        if (isset($json['asset_type'])) $this->assetType = $json['asset_type'];
        if (isset($json['asset_code'])) $this->assetCode = $json['asset_code'];
        if (isset($json['asset_issuer'])) $this->assetIssuer = $json['asset_issuer'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : AllowTrustOperationResponse {
        $result = new AllowTrustOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }

}