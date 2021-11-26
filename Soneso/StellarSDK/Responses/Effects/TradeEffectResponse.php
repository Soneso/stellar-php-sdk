<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

use Soneso\StellarSDK\Asset;

class TradeEffectResponse extends EffectResponse
{
    private string $seller;
    private ?string $sellerMuxed= null;
    private ?string $sellerMuxedId = null;
    private string $offerId;
    private string $soldAmount;
    private Asset $soldAsset;
    private string $boughtAmount;
    private Asset $boughtAsset;

    /**
     * @return string
     */
    public function getSeller(): string
    {
        return $this->seller;
    }

    /**
     * @return string|null
     */
    public function getSellerMuxed(): ?string
    {
        return $this->sellerMuxed;
    }

    /**
     * @return string|null
     */
    public function getSellerMuxedId(): ?string
    {
        return $this->sellerMuxedId;
    }

    /**
     * @return string
     */
    public function getOfferId(): string
    {
        return $this->offerId;
    }

    /**
     * @return string
     */
    public function getSoldAmount(): string
    {
        return $this->soldAmount;
    }

    /**
     * @return Asset
     */
    public function getSoldAsset(): Asset
    {
        return $this->soldAsset;
    }

    /**
     * @return string
     */
    public function getBoughtAmount(): string
    {
        return $this->boughtAmount;
    }

    /**
     * @return Asset
     */
    public function getBoughtAsset(): Asset
    {
        return $this->boughtAsset;
    }


    protected function loadFromJson(array $json) : void {

        if (isset($json['seller'])) $this->seller = $json['seller'];
        if (isset($json['seller_muxed'])) $this->sellerMuxed = $json['seller_muxed'];
        if (isset($json['seller_muxed_id'])) $this->sellerMuxedId = $json['seller_muxed_id'];
        if (isset($json['offer_id'])) $this->offerId = $json['offer_id'];

        if (isset($json['sold_amount'])) $this->soldAmount = $json['sold_amount'];
        if (isset($json['sold_asset_type'])) {
            $assetCode = $json['sold_asset_code'] ?? null;
            $assetIssuer = $json['sold_asset_issuer'] ?? null;
            $this->soldAsset = Asset::create($json['sold_asset_type'], $assetCode, $assetIssuer);
        }

        if (isset($json['bought_amount'])) $this->boughtAmount = $json['bought_amount'];
        if (isset($json['bought_asset_type'])) {
            $assetCode = $json['bought_asset_code'] ?? null;
            $assetIssuer = $json['bought_asset_issuer'] ?? null;
            $this->boughtAsset = Asset::create($json['bought_asset_type'], $assetCode, $assetIssuer);
        }

        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : TradeEffectResponse {
        $result = new TradeEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}