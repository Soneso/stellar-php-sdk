<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Account;

/**
 * Represents a single asset balance held by an account
 *
 * This response contains detailed information about an asset balance including the amount,
 * asset details, liabilities, authorization flags, and sponsorship information. Balance entries
 * can represent native XLM, issued assets, or liquidity pool shares.
 *
 * Key fields:
 * - Balance amount and asset identification
 * - Buying and selling liabilities from open offers
 * - Authorization status for issued assets
 * - Trustline limit for non-native assets
 * - Clawback enabled flag
 * - Sponsorship information
 *
 * This response is included in AccountResponse as part of the balances array.
 *
 * @package Soneso\StellarSDK\Responses\Account
 * @see AccountResponse For the parent account details
 * @see https://developers.stellar.org/api/resources/accounts Horizon Accounts API
 * @since 1.0.0
 */
class AccountBalanceResponse
{
    private string $balance;
    private string $assetType;
    private ?string $assetCode = null;
    private ?string $assetIssuer = null;
    private ?string $liquidityPoolId = null;
    private ?string $buyingLiabilities = null;
    private ?string $sellingLiabilities = null;
    private ?string $limit = null;
    private ?string $sponsor = null;
    private ?bool $isAuthorized = null;
    private ?bool $isAuthorizedToMaintainLiabilities = null;
    private ?bool $isClawbackEnabled = null;
    private ?int $lastModifiedLedger = null;

    /**
     * Gets the balance amount for this asset
     *
     * @return string The balance amount as a string to preserve precision
     */
    public function getBalance(): string
    {
        return $this->balance;
    }

    /**
     * Gets the asset type
     *
     * Can be "native", "credit_alphanum4", "credit_alphanum12", or "liquidity_pool_shares".
     *
     * @return string The asset type
     */
    public function getAssetType(): string
    {
        return $this->assetType;
    }

    /**
     * Gets the asset code for issued assets
     *
     * Only present for credit_alphanum4 and credit_alphanum12 assets.
     *
     * @return string|null The asset code, or null for native assets
     */
    public function getAssetCode(): ?string
    {
        return $this->assetCode;
    }

    /**
     * Gets the asset issuer account ID for issued assets
     *
     * Only present for credit_alphanum4 and credit_alphanum12 assets.
     *
     * @return string|null The issuer account ID, or null for native assets
     */
    public function getAssetIssuer(): ?string
    {
        return $this->assetIssuer;
    }

    /**
     * Gets the liquidity pool ID for liquidity pool shares
     *
     * Only present when asset type is liquidity_pool_shares.
     *
     * @return string|null The liquidity pool ID, or null for other asset types
     */
    public function getLiquidityPoolId(): ?string
    {
        return $this->liquidityPoolId;
    }

    /**
     * Gets the buying liabilities for this asset from open offers
     *
     * The amount reserved for buying other assets.
     *
     * @return string|null The buying liabilities amount, or null if not applicable
     */
    public function getBuyingLiabilities(): ?string
    {
        return $this->buyingLiabilities;
    }

    /**
     * Gets the selling liabilities for this asset from open offers
     *
     * The amount reserved for selling this asset.
     *
     * @return string|null The selling liabilities amount, or null if not applicable
     */
    public function getSellingLiabilities(): ?string
    {
        return $this->sellingLiabilities;
    }

    /**
     * Gets the maximum balance limit for this trustline
     *
     * Only present for issued assets. Native assets have no limit.
     *
     * @return string|null The trustline limit, or null for native assets
     */
    public function getLimit(): ?string
    {
        return $this->limit;
    }


    /**
     * Gets the sponsor account ID for this balance entry
     *
     * @return string|null The sponsor account ID, or null if not sponsored
     */
    public function getSponsor(): ?string
    {
        return $this->sponsor;
    }

    /**
     * Gets whether this trustline is fully authorized by the issuer
     *
     * Only relevant for issued assets with authorization required.
     *
     * @return bool|null True if authorized, false if not, null if not applicable
     */
    public function getIsAuthorized(): ?bool
    {
        return $this->isAuthorized;
    }

    /**
     * Gets whether this trustline is authorized to maintain liabilities
     *
     * When true, the account can maintain existing offers but not receive new funds.
     *
     * @return bool|null True if authorized to maintain liabilities, null if not applicable
     */
    public function getIsAuthorizedToMaintainLiabilities(): ?bool
    {
        return $this->isAuthorizedToMaintainLiabilities;
    }

    /**
     * Gets whether clawback is enabled for this asset
     *
     * When true, the issuer can clawback this asset from the account.
     *
     * @return bool|null True if clawback enabled, null if not applicable
     */
    public function getIsClawbackEnabled(): ?bool
    {
        return $this->isClawbackEnabled;
    }

    /**
     * Gets the ledger sequence number when this balance was last modified
     *
     * @return int|null The ledger sequence number, or null if not available
     */
    public function getLastModifiedLedger(): ?int
    {
        return $this->lastModifiedLedger;
    }

    
    protected function loadFromJson(array $json) : void {
        if (isset($json['balance'])) $this->balance = $json['balance'];
        if (isset($json['asset_type'])) $this->assetType = $json['asset_type'];
        if (isset($json['asset_code'])) $this->assetCode = $json['asset_code'];
        if (isset($json['asset_issuer'])) $this->assetIssuer = $json['asset_issuer'];
        if (isset($json['liquidity_pool_id'])) $this->liquidityPoolId = $json['liquidity_pool_id'];
        if (isset($json['buying_liabilities'])) $this->buyingLiabilities = $json['buying_liabilities'];
        if (isset($json['selling_liabilities'])) $this->sellingLiabilities = $json['selling_liabilities'];
        if (isset($json['sponsor'])) $this->sponsor = $json['sponsor'];
        if (isset($json['limit'])) $this->limit = $json['limit'];
        if (isset($json['is_authorized'])) $this->isAuthorized = $json['is_authorized'];
        if (isset($json['is_authorized_to_maintain_liabilities'])) $this->isAuthorizedToMaintainLiabilities = $json['is_authorized_to_maintain_liabilities'];
        if (isset($json['is_clawback_enabled'])) $this->isClawbackEnabled = $json['is_clawback_enabled'];
        if (isset($json['last_modified_ledger'])) $this->lastModifiedLedger = $json['last_modified_ledger'];
    }

    /**
     * Creates an AccountBalanceResponse instance from JSON data
     *
     * @param array $json The JSON array containing balance data from Horizon
     * @return AccountBalanceResponse The parsed balance response
     */
    public static function fromJson(array $json) : AccountBalanceResponse {
        $result = new AccountBalanceResponse();
        $result->loadFromJson($json);
        return $result;
    }

}

