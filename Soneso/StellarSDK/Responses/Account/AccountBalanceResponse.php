<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Account;

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
     * @return string
     */
    public function getBalance(): string
    {
        return $this->balance;
    }

    /**
     * @return string
     */
    public function getAssetType(): string
    {
        return $this->assetType;
    }

    /**
     * @return string|null
     */
    public function getAssetCode(): ?string
    {
        return $this->assetCode;
    }

    /**
     * @return string|null
     */
    public function getAssetIssuer(): ?string
    {
        return $this->assetIssuer;
    }

    /**
     * @return string|null
     */
    public function getLiquidityPoolId(): ?string
    {
        return $this->liquidityPoolId;
    }

    /**
     * @return string|null
     */
    public function getBuyingLiabilities(): ?string
    {
        return $this->buyingLiabilities;
    }

    /**
     * @return string|null
     */
    public function getSellingLiabilities(): ?string
    {
        return $this->sellingLiabilities;
    }

    /**
     * @return string|null
     */
    public function getLimit(): ?string
    {
        return $this->limit;
    }


    /**
     * @return string|null
     */
    public function getSponsor(): ?string
    {
        return $this->sponsor;
    }

    /**
     * @return bool|null
     */
    public function getIsAuthorized(): ?bool
    {
        return $this->isAuthorized;
    }

    /**
     * @return bool|null
     */
    public function getIsAuthorizedToMaintainLiabilities(): ?bool
    {
        return $this->isAuthorizedToMaintainLiabilities;
    }

    /**
     * @return bool|null
     */
    public function getIsClawbackEnabled(): ?bool
    {
        return $this->isClawbackEnabled;
    }

    /**
     * @return int|null
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
    
    public static function fromJson(array $json) : AccountBalanceResponse {
        $result = new AccountBalanceResponse();
        $result->loadFromJson($json);
        return $result;
    }
   
}

