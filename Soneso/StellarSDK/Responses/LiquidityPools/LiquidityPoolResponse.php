<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\LiquidityPools;

use Soneso\StellarSDK\Responses\Account\AccountBalanceResponse;
use Soneso\StellarSDK\Responses\Account\AccountBalancesResponse;
use Soneso\StellarSDK\Responses\Response;

class LiquidityPoolResponse extends Response
{
    private string $poolId;
    private string $pagingToken;
    private int $fee; // TODO: Bigint
    private string $type;
    private string $totalTrustlines;
    private string $totalShares;
    private LiquidityPoolLinksResponse $links;
    private ReservesResponse $reserves;
    private int $lastModifiedLedger;
    private string $lastModifiedTime;


    /**
     * @return string
     */
    public function getPoolId(): string
    {
        return $this->poolId;
    }

    /**
     * @return string
     */
    public function getPagingToken(): string
    {
        return $this->pagingToken;
    }

    /**
     * @return int
     */
    public function getFee(): int
    {
        return $this->fee;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getTotalTrustlines(): string
    {
        return $this->totalTrustlines;
    }

    /**
     * @return string
     */
    public function getTotalShares(): string
    {
        return $this->totalShares;
    }

    /**
     * @return LiquidityPoolLinksResponse
     */
    public function getLinks(): LiquidityPoolLinksResponse
    {
        return $this->links;
    }

    /**
     * @return ReservesResponse
     */
    public function getReserves(): ReservesResponse
    {
        return $this->reserves;
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

    protected function loadFromJson(array $json) : void {
        if (isset($json['_links'])) $this->links = LiquidityPoolLinksResponse::fromJson($json['_links']);
        if (isset($json['id'])) $this->poolId = $json['id'];
        if (isset($json['paging_token'])) $this->pagingToken = $json['paging_token'];
        if (isset($json['fee_bp'])) $this->fee = $json['fee_bp'];
        if (isset($json['type'])) $this->type = $json['type'];
        if (isset($json['total_trustlines'])) $this->totalTrustlines = $json['total_trustlines'];
        if (isset($json['total_shares'])) $this->totalShares = $json['total_shares'];
        if (isset($json['last_modified_ledger'])) $this->lastModifiedLedger = $json['last_modified_ledger'];
        if (isset($json['last_modified_time'])) $this->lastModifiedTime = $json['last_modified_time'];

        if (isset($json['reserves'])) {
            $this->reserves = new ReservesResponse();
            foreach ($json['reserves'] as $jsonValue) {
                $value = ReserveResponse::fromJson($jsonValue);
                $this->reserves->add($value);
            }
        }
    }

    public static function fromJson(array $json) : LiquidityPoolResponse {
        $result = new LiquidityPoolResponse();
        $result->loadFromJson($json);
        return $result;
    }
}