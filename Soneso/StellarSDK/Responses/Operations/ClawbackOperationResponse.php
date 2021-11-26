<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Operations;

use Soneso\StellarSDK\Asset;

class ClawbackOperationResponse extends OperationResponse
{
    private string $amount;
    private string $from;
    private ?string $fromMuxed = null;
    private ?string $fromMuxedId = null;
    private Asset $asset;

    /**
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getFrom(): string
    {
        return $this->from;
    }

    /**
     * @return string|null
     */
    public function getFromMuxed(): ?string
    {
        return $this->fromMuxed;
    }

    /**
     * @return string|null
     */
    public function getFromMuxedId(): ?string
    {
        return $this->fromMuxedId;
    }

    /**
     * @return Asset
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