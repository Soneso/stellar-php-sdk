<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Responses\Offers\OfferPriceResponse;

class CreatePassiveSellOfferResponse extends OperationResponse
{
    private string $amount;
    private string $price;
    private OfferPriceResponse $priceR;
    private Asset $buyingAsset;
    private Asset $sellingAsset;

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
    public function getPrice(): string
    {
        return $this->price;
    }

    /**
     * @return Asset
     */
    public function getBuyingAsset(): Asset
    {
        return $this->buyingAsset;
    }

    /**
     * @return Asset
     */
    public function getSellingAsset(): Asset
    {
        return $this->sellingAsset;
    }

    /**
     * @return OfferPriceResponse
     */
    public function getPriceR(): OfferPriceResponse
    {
        return $this->priceR;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['amount'])) $this->amount = $json['amount'];
        if (isset($json['price'])) $this->price = $json['price'];
        if (isset($json['price_r'])) $this->priceR = OfferPriceResponse::fromJson($json['price_r']);
        if (isset($json['buying_asset_type'])) {
            $assetCode = $json['buying_asset_code'] ?? null;
            $assetIssuer = $json['buying_asset_issuer'] ?? null;
            $this->buyingAsset = Asset::create($json['buying_asset_type'], $assetCode, $assetIssuer);
        }
        if (isset($json['selling_asset_type'])) {
            $assetCode = $json['selling_asset_code'] ?? null;
            $assetIssuer = $json['selling_asset_issuer'] ?? null;
            $this->sellingAsset = Asset::create($json['selling_asset_type'], $assetCode, $assetIssuer);
        }
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : CreatePassiveSellOfferResponse {
        $result = new CreatePassiveSellOfferResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}