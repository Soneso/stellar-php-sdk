<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

use Soneso\StellarSDK\Asset;

/**
 * Represents an effect when a trade is executed on the Stellar DEX
 *
 * This effect occurs when an offer is matched with a counter-offer on the order book,
 * or when trading through a liquidity pool. Contains details about the assets and amounts
 * exchanged. Triggered by trading operations including ManageBuyOffer, ManageSellOffer,
 * and PathPayment operations.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse
 * @see https://developers.stellar.org Stellar developer docs
 */
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
     * Gets the seller's account ID
     *
     * @return string The seller's account ID
     */
    public function getSeller(): string
    {
        return $this->seller;
    }

    /**
     * Gets the seller's muxed account address if multiplexed
     *
     * @return string|null The muxed address, or null if not multiplexed
     */
    public function getSellerMuxed(): ?string
    {
        return $this->sellerMuxed;
    }

    /**
     * Gets the seller's muxed account ID if multiplexed
     *
     * @return string|null The muxed ID, or null if not multiplexed
     */
    public function getSellerMuxedId(): ?string
    {
        return $this->sellerMuxedId;
    }

    /**
     * Gets the offer ID that was executed
     *
     * @return string The offer ID
     */
    public function getOfferId(): string
    {
        return $this->offerId;
    }

    /**
     * Gets the amount of asset sold in the trade
     *
     * @return string The sold amount
     */
    public function getSoldAmount(): string
    {
        return $this->soldAmount;
    }

    /**
     * Gets the asset that was sold
     *
     * @return Asset The sold asset
     */
    public function getSoldAsset(): Asset
    {
        return $this->soldAsset;
    }

    /**
     * Gets the amount of asset bought in the trade
     *
     * @return string The bought amount
     */
    public function getBoughtAmount(): string
    {
        return $this->boughtAmount;
    }

    /**
     * Gets the asset that was bought
     *
     * @return Asset The bought asset
     */
    public function getBoughtAsset(): Asset
    {
        return $this->boughtAsset;
    }


    /**
     * Loads object data from JSON array
     *
     * @param array $json JSON data array
     * @return void
     */
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

    /**
     * Creates an instance from JSON data
     *
     * @param array $jsonData JSON data array
     * @return TradeEffectResponse
     */
    public static function fromJson(array $jsonData) : TradeEffectResponse {
        $result = new TradeEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}
