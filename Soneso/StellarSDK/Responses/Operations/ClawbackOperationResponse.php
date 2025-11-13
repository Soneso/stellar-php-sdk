<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Operations;

use Soneso\StellarSDK\Asset;

/**
 * Represents a clawback operation response from Horizon API
 *
 * This operation claws back a specified amount of an asset from a holding account, burning the asset
 * and reducing the total supply. Only the asset issuer can perform clawbacks, and only if the
 * AUTH_CLAWBACK_ENABLED flag is set on the asset. This is used to retrieve assets from accounts
 * for regulatory compliance or asset management purposes.
 *
 * @package Soneso\StellarSDK\Responses\Operations
 * @see OperationResponse Base operation response
 * @see https://developers.stellar.org Stellar developer docs Horizon Clawback Operation
 */
class ClawbackOperationResponse extends OperationResponse
{
    private string $amount;
    private string $from;
    private ?string $fromMuxed = null;
    private ?string $fromMuxedId = null;
    private Asset $asset;

    /**
     * Gets the amount of the asset being clawed back
     *
     * @return string The amount as a string to preserve precision
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Gets the account from which the asset is being clawed back
     *
     * @return string The source account ID
     */
    public function getFrom(): string
    {
        return $this->from;
    }

    /**
     * Gets the multiplexed source account if applicable
     *
     * @return string|null The muxed source account address or null
     */
    public function getFromMuxed(): ?string
    {
        return $this->fromMuxed;
    }

    /**
     * Gets the multiplexed source account ID if applicable
     *
     * @return string|null The muxed source account ID or null
     */
    public function getFromMuxedId(): ?string
    {
        return $this->fromMuxedId;
    }

    /**
     * Gets the asset being clawed back
     *
     * @return Asset The asset details (type, code, issuer)
     */
    public function getAsset(): Asset
    {
        return $this->asset;
    }


    protected function loadFromJson(array $json) : void {

        if (isset($json['amount'])) $this->amount = $json['amount'];
        if (isset($json['from'])) $this->from = $json['from'];
        if (isset($json['from_muxed'])) $this->fromMuxed = $json['from_muxed'];
        if (isset($json['from_muxed_id'])) $this->fromMuxedId = $json['from_muxed_id'];

        if (isset($json['asset_type'])) {
            $assetCode = $json['asset_code'] ?? null;
            $assetIssuer = $json['asset_issuer'] ?? null;
            $this->asset = Asset::create($json['asset_type'], $assetCode, $assetIssuer);
        }

        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : ClawbackOperationResponse {
        $result = new ClawbackOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }

}