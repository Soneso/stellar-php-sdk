<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\ClaimableBalances;

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Responses\Response;

class ClaimableBalanceResponse extends Response
{

    private string $balanceId;
    private Asset $asset;
    private string $amount;
    private string $sponsor;
    private int $lastModifiedLedger;
    private string $lastModifiedTime;
    private string $pagingToken;
    private ClaimantsResponse $claimants;
    private ClaimableBalanceLinksResponse $links;

    /**
     * @return string
     */
    public function getBalanceId(): string
    {
        return $this->balanceId;
    }

    /**
     * @return Asset
     */
    public function getAsset(): Asset
    {
        return $this->asset;
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
    public function getSponsor(): string
    {
        return $this->sponsor;
    }

    /**
     * @return int
     */
    public function getLastModifiedLedger(): int
    {
        return $this->lastModifiedLedger;
    }

    /**
     * @return string
     */
    public function getLastModifiedTime(): string
    {
        return $this->lastModifiedTime;
    }

    /**
     * @return string
     */
    public function getPagingToken(): string
    {
        return $this->pagingToken;
    }

    /**
     * @return ClaimantsResponse
     */
    public function getClaimants(): ClaimantsResponse
    {
        return $this->claimants;
    }

    /**
     * @return ClaimableBalanceLinksResponse
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