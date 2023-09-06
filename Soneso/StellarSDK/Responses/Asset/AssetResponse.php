<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Asset;

use Soneso\StellarSDK\Responses\Response;

class AssetResponse extends Response
{
    private string $assetType;
    private ?string $assetCode = null;
    private ?string $assetIssuer = null;
    private string $pagingToken;
    private AssetAccountsResponse $accounts;
    private AssetBalancesResponse $balances;
    private string $amount;
    private string $claimableBalancesAmount;
    private string $liquidityPoolsAmount;
    private int $numAccounts;
    private int $numClaimableBalances;
    private int $numLiquidityPools;
    private ?int $numContracts = null;
    private ?string $contractsAmount = null;
    private AssetFlagsResponse $flags;
    private AssetLinksResponse $links;

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
     * @return string
     */
    public function getPagingToken(): string
    {
        return $this->pagingToken;
    }

    /**
     * @return AssetAccountsResponse
     */
    public function getAccounts(): AssetAccountsResponse
    {
        return $this->accounts;
    }

    /**
     * @return AssetBalancesResponse
     */
    public function getBalances(): AssetBalancesResponse
    {
        return $this->balances;
    }

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
    public function getClaimableBalancesAmount(): string
    {
        return $this->claimableBalancesAmount;
    }

    /**
     * @return string
     */
    public function getLiquidityPoolsAmount(): string
    {
        return $this->liquidityPoolsAmount;
    }

    /**
     * @return int
     */
    public function getNumAccounts(): int
    {
        return $this->numAccounts;
    }

    /**
     * @return int
     */
    public function getNumClaimableBalances(): int
    {
        return $this->numClaimableBalances;
    }

    /**
     * @return int
     */
    public function getNumLiquidityPools(): int
    {
        return $this->numLiquidityPools;
    }

    /**
     * @return AssetFlagsResponse
     */
    public function getFlags(): AssetFlagsResponse
    {
        return $this->flags;
    }

    /**
     * @return AssetLinksResponse
     */
    public function getLinks(): AssetLinksResponse
    {
        return $this->links;
    }

    /**
     * @return int|null
     */
    public function getNumContracts(): ?int
    {
        return $this->numContracts;
    }

    /**
     * @param int|null $numContracts
     */
    public function setNumContracts(?int $numContracts): void
    {
        $this->numContracts = $numContracts;
    }

    /**
     * @return string|null
     */
    public function getContractsAmount(): ?string
    {
        return $this->contractsAmount;
    }

    /**
     * @param string|null $contractsAmount
     */
    public function setContractsAmount(?string $contractsAmount): void
    {
        $this->contractsAmount = $contractsAmount;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['asset_type'])) $this->assetType = $json['asset_type'];
        if (isset($json['asset_code'])) $this->assetCode = $json['asset_code'];
        if (isset($json['asset_issuer'])) $this->assetIssuer = $json['asset_issuer'];
        if (isset($json['paging_token'])) $this->pagingToken = $json['paging_token'];
        if (isset($json['accounts'])) $this->accounts = AssetAccountsResponse::fromJson($json['accounts']);
        if (isset($json['balances'])) $this->balances = AssetBalancesResponse::fromJson($json['balances']);
        if (isset($json['amount'])) $this->amount = $json['amount'];
        if (isset($json['claimable_balances_amount'])) $this->claimableBalancesAmount = $json['claimable_balances_amount'];
        if (isset($json['liquidity_pools_amount'])) $this->liquidityPoolsAmount = $json['liquidity_pools_amount'];
        if (isset($json['contracts_amount'])) $this->contractsAmount = $json['contracts_amount'];
        if (isset($json['num_accounts'])) $this->numAccounts = $json['num_accounts'];
        if (isset($json['num_claimable_balances'])) $this->numClaimableBalances = $json['num_claimable_balances'];
        if (isset($json['num_liquidity_pools'])) $this->numLiquidityPools = $json['num_liquidity_pools'];
        if (isset($json['num_contracts'])) $this->numContracts = $json['num_contracts'];
        if (isset($json['flags'])) $this->flags = AssetFlagsResponse::fromJson($json['flags']);
        if (isset($json['_links'])) $this->links = AssetLinksResponse::fromJson($json['_links']);
    }

    public static function fromJson(array $json) : AssetResponse
    {
        $result = new AssetResponse();
        $result->loadFromJson($json);
        return $result;
    }
}