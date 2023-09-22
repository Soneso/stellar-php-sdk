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
    public string $from;
    public string $to;
    public string $amount;

    protected function loadFromJson(array $json) : void {
        $this->assetType = $json['asset_type'];

        if (isset($json['asset_code'])) {
            $this->assetCode = $json['asset_code'];
        }
        if (isset($json['asset_issuer'])) {
            $this->assetIssuer = $json['asset_issuer'];
        }
        $this->type = $json['type'];
        $this->from = $json['from'];
        $this->to = $json['to'];
        $this->amount = $json['amount'];
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
     * @return string
     */
    public function getFrom(): string
    {
        return $this->from;
    }

    /**
     * @param string $from
     */
    public function setFrom(string $from): void
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

}