<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;

class AssetBalanceChangeResponse
{
    public string $assetType;
    public ?string $assetCode = null;
    public ?string $assetIssuer = null;
    public string $type;
    public ?string $from = null;
    public string $to;
    public string $amount;
    public ?string $destinationMuxedId = null; // a uint64

    protected function loadFromJson(array $json) : void {
        $this->assetType = $json['asset_type'];

        if (isset($json['asset_code'])) {
            $this->assetCode = $json['asset_code'];
        }
        if (isset($json['asset_issuer'])) {
            $this->assetIssuer = $json['asset_issuer'];
        }
        $this->type = $json['type'];
        if (isset($json['from'])) {
            $this->from = $json['from'];
        }
        $this->to = $json['to'];
        $this->amount = $json['amount'];
        if (isset($json['destination_muxed_id'])) {
            $this->destinationMuxedId = $json['destination_muxed_id'];
        }
    }

    public static function fromJson(array $json) : AssetBalanceChangeResponse {
        $result = new AssetBalanceChangeResponse();
        $result->loadFromJson($json);
        return $result;
    }

    /**
     * @return string
     */
    public function getAssetType(): string
    {
        return $this->assetType;
    }

    /**
     * @param string $assetType
     */
    public function setAssetType(string $assetType): void
    {
        $this->assetType = $assetType;
    }

    /**
     * @return string|null
     */
    public function getAssetCode(): ?string
    {
        return $this->assetCode;
    }

    /**
     * @param string|null $assetCode
     */
    public function setAssetCode(?string $assetCode): void
    {
        $this->assetCode = $assetCode;
    }

    /**
     * @return string|null
     */
    public function getAssetIssuer(): ?string
    {
        return $this->assetIssuer;
    }

    /**
     * @param string|null $assetIssuer
     */
    public function setAssetIssuer(?string $assetIssuer): void
    {
        $this->assetIssuer = $assetIssuer;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string|null
     */
    public function getFrom(): ?string
    {
        return $this->from;
    }

    /**
     * @param string|null $from
     */
    public function setFrom(?string $from): void
    {
        $this->from = $from;
    }

    /**
     * @return string
     */
    public function getTo(): string
    {
        return $this->to;
    }

    /**
     * @param string $to
     */
    public function setTo(string $to): void
    {
        $this->to = $to;
    }

    /**
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * @param string $amount
     */
    public function setAmount(string $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return string|null
     */
    public function getDestinationMuxedId(): ?string
    {
        return $this->destinationMuxedId;
    }

    /**
     * @param string|null $destinationMuxedId
     */
    public function setDestinationMuxedId(?string $destinationMuxedId): void
    {
        $this->destinationMuxedId = $destinationMuxedId;
    }

}