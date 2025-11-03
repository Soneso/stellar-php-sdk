<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\ClaimableBalances;

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Responses\Response;

/**
 * Represents a claimable balance on the Stellar network
 *
 * This response contains comprehensive claimable balance details including the balance ID,
 * asset type and amount, sponsor, claimants with predicates, and modification history.
 * Claimable balances are ledger entries that hold funds which can be claimed by specified
 * accounts when predicate conditions are met, enabling use cases like payment splitting,
 * airdrops, and conditional payments.
 *
 * Key fields:
 * - Unique balance ID for claiming
 * - Asset and amount available to claim
 * - Sponsor account funding the reserve
 * - List of claimants with claiming predicates
 * - Ledger modification history
 *
 * Returned by Horizon endpoints:
 * - GET /claimable_balances - All claimable balances
 * - GET /claimable_balances/{claimable_balance_id} - Specific balance details
 * - GET /claimants/{account_id}/claimable_balances - Balances claimable by an account
 *
 * @package Soneso\StellarSDK\Responses\ClaimableBalances
 * @see ClaimantsResponse For the list of claimants
 * @see ClaimantResponse For individual claimant details
 * @see ClaimableBalanceLinksResponse For related navigation links
 * @see https://developers.stellar.org/api/resources/claimablebalances Horizon Claimable Balances API
 * @since 1.0.0
 */
class ClaimableBalanceResponse extends Response
{

    private string $balanceId;
    private Asset $asset;
    private string $amount;
    private string $sponsor;
    private int $lastModifiedLedger;
    private ?string $lastModifiedTime = null;
    private string $pagingToken;
    private ClaimantsResponse $claimants;
    private ClaimableBalanceLinksResponse $links;

    /**
     * Gets the unique identifier for this claimable balance
     *
     * @return string The claimable balance ID used for claiming operations
     */
    public function getBalanceId(): string
    {
        return $this->balanceId;
    }

    /**
     * Gets the asset held in this claimable balance
     *
     * @return Asset The asset type and details
     */
    public function getAsset(): Asset
    {
        return $this->asset;
    }

    /**
     * Gets the amount of the asset available to claim
     *
     * @return string The claimable amount
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Gets the account sponsoring the reserves for this claimable balance
     *
     * @return string The sponsor account ID
     */
    public function getSponsor(): string
    {
        return $this->sponsor;
    }

    /**
     * Gets the ledger sequence number when this balance was last modified
     *
     * @return int The last modified ledger sequence
     */
    public function getLastModifiedLedger(): int
    {
        return $this->lastModifiedLedger;
    }

    /**
     * Gets the timestamp when this balance was last modified
     *
     * @return string|null The last modified time in ISO 8601 format, or null if not available
     */
    public function getLastModifiedTime(): ?string
    {
        return $this->lastModifiedTime;
    }

    /**
     * Gets the paging token for this claimable balance in list results
     *
     * @return string The paging token used for cursor-based pagination
     */
    public function getPagingToken(): string
    {
        return $this->pagingToken;
    }

    /**
     * Gets the list of claimants who can claim this balance
     *
     * @return ClaimantsResponse The claimants with their predicates
     */
    public function getClaimants(): ClaimantsResponse
    {
        return $this->claimants;
    }

    /**
     * Gets the links to related resources for this claimable balance
     *
     * @return ClaimableBalanceLinksResponse The navigation links
     */
    public function getLinks(): ClaimableBalanceLinksResponse
    {
        return $this->links;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['id'])) $this->balanceId = $json['id'];

        if (isset($json['asset'])) {
            $parsedAsset = Asset::createFromCanonicalForm($json['asset']);
            if ($parsedAsset != null) {
                $this->asset = $parsedAsset;
            }
        }

        if (isset($json['amount'])) $this->amount = $json['amount'];
        if (isset($json['sponsor'])) $this->sponsor = $json['sponsor'];
        if (isset($json['last_modified_ledger'])) $this->lastModifiedLedger = $json['last_modified_ledger'];
        if (isset($json['last_modified_time'])) $this->lastModifiedTime = $json['last_modified_time'];
        if (isset($json['paging_token'])) $this->pagingToken = $json['paging_token'];

        if (isset($json['claimants'])) {
            $this->claimants = new ClaimantsResponse();
            foreach ($json['claimants'] as $jsonClaimants) {
                $claimant = ClaimantResponse::fromJson($jsonClaimants);
                $this->claimants->add($claimant);
            }
        }

        if (isset($json['_links'])) $this->links = ClaimableBalanceLinksResponse::fromJson($json['_links']);
    }

    public static function fromJson(array $json) : ClaimableBalanceResponse
    {
        $result = new ClaimableBalanceResponse();
        $result->loadFromJson($json);
        return $result;
    }

}