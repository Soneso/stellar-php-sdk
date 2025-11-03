<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Asset;

use Soneso\StellarSDK\Responses\Response;

/**
 * Represents an asset issued on the Stellar network
 *
 * This response contains comprehensive asset details including asset type, code, and issuer,
 * account statistics, balance distributions, flags, and liquidity pool information. Assets can
 * be native (XLM) or issued by specific accounts. The response includes usage metrics across
 * accounts, claimable balances, liquidity pools, and smart contracts.
 *
 * Key fields:
 * - Asset type, code, and issuer identification
 * - Number of accounts holding the asset
 * - Total balances and distribution statistics
 * - Asset authorization flags
 * - Claimable balance and liquidity pool amounts
 * - Smart contract integration metrics
 *
 * Returned by Horizon endpoints:
 * - GET /assets - List of all assets
 * - GET /assets?asset_code={code}&asset_issuer={issuer} - Specific asset details
 *
 * @package Soneso\StellarSDK\Responses\Asset
 * @see AssetFlagsResponse For asset authorization flags
 * @see AssetAccountsResponse For account statistics
 * @see AssetBalancesResponse For balance distribution
 * @see https://developers.stellar.org/api/resources/assets Horizon Assets API
 * @since 1.0.0
 */
class AssetResponse extends Response
{
    private string $assetType;
    private ?string $assetCode = null;
    private ?string $assetIssuer = null;
    private string $pagingToken;
    private AssetAccountsResponse $accounts;
    private AssetBalancesResponse $balances;
    private string $claimableBalancesAmount;
    private string $liquidityPoolsAmount;
    private int $numClaimableBalances;
    private int $numLiquidityPools;
    private ?int $numContracts = null;
    private ?string $contractsAmount = null;
    private AssetFlagsResponse $flags;
    private AssetLinksResponse $links;

    /**
     * Gets the asset type (native, credit_alphanum4, or credit_alphanum12)
     *
     * @return string The asset type
     */
    public function getAssetType(): string
    {
        return $this->assetType;
    }

    /**
     * Gets the asset code for non-native assets
     *
     * @return string|null The asset code, or null for native assets
     */
    public function getAssetCode(): ?string
    {
        return $this->assetCode;
    }

    /**
     * Gets the account address of the asset issuer
     *
     * @return string|null The issuer account ID, or null for native assets
     */
    public function getAssetIssuer(): ?string
    {
        return $this->assetIssuer;
    }

    /**
     * Gets the paging token for this asset in list results
     *
     * @return string The paging token used for cursor-based pagination
     */
    public function getPagingToken(): string
    {
        return $this->pagingToken;
    }

    /**
     * Gets statistics about accounts holding this asset
     *
     * @return AssetAccountsResponse The account statistics including authorized and unauthorized counts
     */
    public function getAccounts(): AssetAccountsResponse
    {
        return $this->accounts;
    }

    /**
     * Gets the balance distribution statistics for this asset
     *
     * @return AssetBalancesResponse The balance distribution across different holder tiers
     */
    public function getBalances(): AssetBalancesResponse
    {
        return $this->balances;
    }

    /**
     * Gets the total amount of this asset in claimable balances
     *
     * @return string The claimable balances amount
     */
    public function getClaimableBalancesAmount(): string
    {
        return $this->claimableBalancesAmount;
    }

    /**
     * Gets the total amount of this asset in liquidity pools
     *
     * @return string The liquidity pools amount
     */
    public function getLiquidityPoolsAmount(): string
    {
        return $this->liquidityPoolsAmount;
    }

    /**
     * Gets the number of claimable balances holding this asset
     *
     * @return int The count of claimable balances
     */
    public function getNumClaimableBalances(): int
    {
        return $this->numClaimableBalances;
    }

    /**
     * Gets the number of liquidity pools containing this asset
     *
     * @return int The count of liquidity pools
     */
    public function getNumLiquidityPools(): int
    {
        return $this->numLiquidityPools;
    }

    /**
     * Gets the authorization flags for this asset
     *
     * @return AssetFlagsResponse The asset flags indicating authorization requirements
     */
    public function getFlags(): AssetFlagsResponse
    {
        return $this->flags;
    }

    /**
     * Gets the links to related resources for this asset
     *
     * @return AssetLinksResponse The navigation links
     */
    public function getLinks(): AssetLinksResponse
    {
        return $this->links;
    }

    /**
     * Gets the number of smart contracts holding this asset
     *
     * @return int|null The count of contracts, or null if not available
     */
    public function getNumContracts(): ?int
    {
        return $this->numContracts;
    }

    /**
     * Sets the number of smart contracts holding this asset
     *
     * @param int|null $numContracts The count of contracts
     */
    public function setNumContracts(?int $numContracts): void
    {
        $this->numContracts = $numContracts;
    }

    /**
     * Gets the total amount of this asset held in smart contracts
     *
     * @return string|null The contracts amount, or null if not available
     */
    public function getContractsAmount(): ?string
    {
        return $this->contractsAmount;
    }

    /**
     * Sets the total amount of this asset held in smart contracts
     *
     * @param string|null $contractsAmount The contracts amount
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
        if (isset($json['claimable_balances_amount'])) $this->claimableBalancesAmount = $json['claimable_balances_amount'];
        if (isset($json['liquidity_pools_amount'])) $this->liquidityPoolsAmount = $json['liquidity_pools_amount'];
        if (isset($json['contracts_amount'])) $this->contractsAmount = $json['contracts_amount'];
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