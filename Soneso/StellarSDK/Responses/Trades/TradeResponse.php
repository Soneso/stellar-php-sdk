<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Trades;

class TradeResponse
{
    private string $id;
    private string $pagingToken;
    private string $ledgerCloseTime; // todo date
    private string $tradeType;
    private ?string $offerId = null;  // if type is "orderbook"
    private ?string $baseOfferId = null;  // if type is "orderbook"
    private ?string $baseLiquidityPoolId = null; // if type is "liquidity_pool"
    private ?int $liquidityPoolFeeBp = null; // if type is "liquidity_pool" todo bigint
    private ?string $baseAccount = null;
    private string $baseAmount;
    private string $baseAssetType;
    private ?string $baseAssetCode = null;
    private ?string $baseAssetIssuer = null;
    private ?string $counterOfferId = null; // if type is "orderbook"
    private ?string $counterAccount = null;
    private string $counterAmount;
    private string $counterAssetType;
    private ?string $counterAssetCode = null;
    private ?string $counterAssetIssuer = null;
    private ?string $counterLiquidityPoolId = null;
    private TradePriceResponse $price;
    private bool $baseIsSeller;
    private TradeLinksResponse $links;

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getPagingToken(): string
    {
        return $this->pagingToken;
    }

    /**
     * @return string
     */
    public function getLedgerCloseTime(): string
    {
        return $this->ledgerCloseTime;
    }

    /**
     * @return string
     */
    public function getTradeType(): string
    {
        return $this->tradeType;
    }

    /**
     * @return string|null
     */
    public function getOfferId(): ?string
    {
        return $this->offerId;
    }

    /**
     * @return string|null
     */
    public function getBaseOfferId(): ?string
    {
        return $this->baseOfferId;
    }

    /**
     * @return string|null
     */
    public function getBaseLiquidityPoolId(): ?string
    {
        return $this->baseLiquidityPoolId;
    }

    /**
     * @return int|null
     */
    public function getLiquidityPoolFeeBp(): ?int
    {
        return $this->liquidityPoolFeeBp;
    }

    /**
     * @return string|null
     */
    public function getBaseAccount(): ?string
    {
        return $this->baseAccount;
    }

    /**
     * @return string
     */
    public function getBaseAmount(): string
    {
        return $this->baseAmount;
    }

    /**
     * @return string
     */
    public function getBaseAssetType(): string
    {
        return $this->baseAssetType;
    }

    /**
     * @return string|null
     */
    public function getBaseAssetCode(): ?string
    {
        return $this->baseAssetCode;
    }

    /**
     * @return string|null
     */
    public function getBaseAssetIssuer(): ?string
    {
        return $this->baseAssetIssuer;
    }

    /**
     * @return string|null
     */
    public function getCounterOfferId(): ?string
    {
        return $this->counterOfferId;
    }

    /**
     * @return string|null
     */
    public function getCounterAccount(): ?string
    {
        return $this->counterAccount;
    }

    /**
     * @return string
     */
    public function getCounterAmount(): string
    {
        return $this->counterAmount;
    }

    /**
     * @return string
     */
    public function getCounterAssetType(): string
    {
        return $this->counterAssetType;
    }

    /**
     * @return string|null
     */
    public function getCounterAssetCode(): ?string
    {
        return $this->counterAssetCode;
    }

    /**
     * @return string|null
     */
    public function getCounterAssetIssuer(): ?string
    {
        return $this->counterAssetIssuer;
    }

    /**
     * @return string|null
     */
    public function getCounterLiquidityPoolId(): ?string
    {
        return $this->counterLiquidityPoolId;
    }

    /**
     * @return TradePriceResponse
     */
    public function getPrice(): TradePriceResponse
    {
        return $this->price;
    }

    /**
     * @return bool
     */
    public function isBaseIsSeller(): bool
    {
        return $this->baseIsSeller;
    }

    /**
     * @return TradeLinksResponse
     */
    public function getLinks(): TradeLinksResponse
    {
        return $this->links;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['id'])) $this->id = $json['id'];
        if (isset($json['paging_token'])) $this->pagingToken = $json['paging_token'];
        if (isset($json['ledger_close_time'])) $this->ledgerCloseTime = $json['ledger_close_time'];
        if (isset($json['trade_type'])) $this->tradeType = $json['trade_type'];
        if (isset($json['offer_id'])) $this->offerId = $json['offer_id'];
        if (isset($json['base_offer_id'])) $this->baseOfferId = $json['base_offer_id'];
        if (isset($json['base_liquidity_pool_id'])) $this->baseLiquidityPoolId = $json['base_liquidity_pool_id'];
        if (isset($json['liquidity_pool_fee_bp'])) $this->liquidityPoolFeeBp = $json['liquidity_pool_fee_bp'];
        if (isset($json['base_account'])) $this->baseAccount = $json['base_account'];
        if (isset($json['base_amount'])) $this->baseAmount = $json['base_amount'];
        if (isset($json['base_asset_type'])) $this->baseAssetType = $json['base_asset_type'];
        if (isset($json['base_asset_code'])) $this->baseAssetCode = $json['base_asset_code'];
        if (isset($json['base_asset_issuer'])) $this->baseAssetIssuer = $json['base_asset_issuer'];
        if (isset($json['counter_account'])) $this->counterAccount = $json['counter_account'];
        if (isset($json['counter_offer_id'])) $this->counterOfferId = $json['counter_offer_id'];
        if (isset($json['counter_amount'])) $this->counterAmount = $json['counter_amount'];
        if (isset($json['counter_asset_type'])) $this->counterAssetType = $json['counter_asset_type'];
        if (isset($json['counter_asset_code'])) $this->counterAssetCode = $json['counter_asset_code'];
        if (isset($json['counter_asset_issuer'])) $this->counterAssetIssuer = $json['counter_asset_issuer'];
        if (isset($json['counter_liquidity_pool_id'])) $this->counterLiquidityPoolId = $json['counter_liquidity_pool_id'];
        if (isset($json['price'])) $this->price = TradePriceResponse::fromJson($json['price']);
        if (isset($json['base_is_seller'])) $this->baseIsSeller = $json['base_is_seller'];
        if (isset($json['_links'])) $this->links = TradeLinksResponse::fromJson($json['_links']);
    }

    public static function fromJson(array $json) : TradeResponse {
        $result = new TradeResponse();
        $result->loadFromJson($json);
        return $result;
    }

}