<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Trades;

/**
 * Represents a completed trade on the Stellar distributed exchange
 *
 * This response contains comprehensive trade execution details including the trading parties,
 * assets exchanged, amounts, price, and trade type (orderbook or liquidity pool). Trades occur
 * when offers match or when liquidity pools facilitate exchanges. The response distinguishes
 * between orderbook trades (between two accounts with offers) and liquidity pool trades
 * (between an account and an AMM pool).
 *
 * Key fields:
 * - Trade ID and timestamp
 * - Trade type (orderbook or liquidity_pool)
 * - Base and counter accounts/offers
 * - Assets and amounts exchanged
 * - Trade price and direction
 * - Liquidity pool details (if applicable)
 *
 * Returned by Horizon endpoints:
 * - GET /trades - All trades
 * - GET /accounts/{account_id}/trades - Trades for an account
 * - GET /offers/{offer_id}/trades - Trades for a specific offer
 * - GET /liquidity_pools/{liquidity_pool_id}/trades - Trades involving a liquidity pool
 *
 * @package Soneso\StellarSDK\Responses\Trades
 * @see TradePriceResponse For trade price representation
 * @see TradeLinksResponse For related navigation links
 * @see https://developers.stellar.org Stellar developer docs Horizon Trades API
 * @since 1.0.0
 */
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
     * Gets the unique identifier for this trade
     *
     * @return string The trade ID
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Gets the paging token for this trade in list results
     *
     * @return string The paging token used for cursor-based pagination
     */
    public function getPagingToken(): string
    {
        return $this->pagingToken;
    }

    /**
     * Gets the timestamp when the ledger containing this trade was closed
     *
     * @return string The ledger close time in ISO 8601 format
     */
    public function getLedgerCloseTime(): string
    {
        return $this->ledgerCloseTime;
    }

    /**
     * Gets the type of trade (orderbook or liquidity_pool)
     *
     * @return string The trade type
     */
    public function getTradeType(): string
    {
        return $this->tradeType;
    }

    /**
     * Gets the offer ID for orderbook trades (deprecated, use base/counter offer IDs)
     *
     * @return string|null The offer ID, or null if not an orderbook trade
     */
    public function getOfferId(): ?string
    {
        return $this->offerId;
    }

    /**
     * Gets the base offer ID for orderbook trades
     *
     * @return string|null The base offer ID, or null if not an orderbook trade
     */
    public function getBaseOfferId(): ?string
    {
        return $this->baseOfferId;
    }

    /**
     * Gets the liquidity pool ID if the base side is a liquidity pool
     *
     * @return string|null The base liquidity pool ID, or null if not a pool trade
     */
    public function getBaseLiquidityPoolId(): ?string
    {
        return $this->baseLiquidityPoolId;
    }

    /**
     * Gets the liquidity pool fee in basis points
     *
     * @return int|null The fee in basis points, or null if not a pool trade
     */
    public function getLiquidityPoolFeeBp(): ?int
    {
        return $this->liquidityPoolFeeBp;
    }

    /**
     * Gets the account address on the base side of the trade
     *
     * @return string|null The base account ID, or null if base is a liquidity pool
     */
    public function getBaseAccount(): ?string
    {
        return $this->baseAccount;
    }

    /**
     * Gets the amount of the base asset traded
     *
     * @return string The base amount
     */
    public function getBaseAmount(): string
    {
        return $this->baseAmount;
    }

    /**
     * Gets the asset type of the base asset
     *
     * @return string The base asset type
     */
    public function getBaseAssetType(): string
    {
        return $this->baseAssetType;
    }

    /**
     * Gets the asset code of the base asset
     *
     * @return string|null The base asset code, or null for native assets
     */
    public function getBaseAssetCode(): ?string
    {
        return $this->baseAssetCode;
    }

    /**
     * Gets the issuer of the base asset
     *
     * @return string|null The base asset issuer, or null for native assets
     */
    public function getBaseAssetIssuer(): ?string
    {
        return $this->baseAssetIssuer;
    }

    /**
     * Gets the counter offer ID for orderbook trades
     *
     * @return string|null The counter offer ID, or null if not an orderbook trade
     */
    public function getCounterOfferId(): ?string
    {
        return $this->counterOfferId;
    }

    /**
     * Gets the account address on the counter side of the trade
     *
     * @return string|null The counter account ID, or null if counter is a liquidity pool
     */
    public function getCounterAccount(): ?string
    {
        return $this->counterAccount;
    }

    /**
     * Gets the amount of the counter asset traded
     *
     * @return string The counter amount
     */
    public function getCounterAmount(): string
    {
        return $this->counterAmount;
    }

    /**
     * Gets the asset type of the counter asset
     *
     * @return string The counter asset type
     */
    public function getCounterAssetType(): string
    {
        return $this->counterAssetType;
    }

    /**
     * Gets the asset code of the counter asset
     *
     * @return string|null The counter asset code, or null for native assets
     */
    public function getCounterAssetCode(): ?string
    {
        return $this->counterAssetCode;
    }

    /**
     * Gets the issuer of the counter asset
     *
     * @return string|null The counter asset issuer, or null for native assets
     */
    public function getCounterAssetIssuer(): ?string
    {
        return $this->counterAssetIssuer;
    }

    /**
     * Gets the liquidity pool ID if the counter side is a liquidity pool
     *
     * @return string|null The counter liquidity pool ID, or null if not a pool trade
     */
    public function getCounterLiquidityPoolId(): ?string
    {
        return $this->counterLiquidityPoolId;
    }

    /**
     * Gets the price of the trade as a rational number
     *
     * @return TradePriceResponse The trade price (counter units per base unit)
     */
    public function getPrice(): TradePriceResponse
    {
        return $this->price;
    }

    /**
     * Indicates whether the base account was selling in this trade
     *
     * @return bool True if the base side was selling, false if buying
     */
    public function isBaseIsSeller(): bool
    {
        return $this->baseIsSeller;
    }

    /**
     * Gets the links to related resources for this trade
     *
     * @return TradeLinksResponse The navigation links
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