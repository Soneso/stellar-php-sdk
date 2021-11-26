<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\ClaimableBalances;

use Soneso\StellarSDK\Responses\Page\PageResponse;
use Soneso\StellarSDK\Responses\Page\PagingLinksResponse;

class ClaimableBalancesPageResponse extends PageResponse
{
    private PagingLinksResponse $links;
    private ClaimableBalancesResponse $claimableBalances;

    /**
     * @return PagingLinksResponse
     */
    public function getLinks(): PagingLinksResponse
    {
        return $this->links;
    }

    /**
     * @return ClaimableBalancesResponse
     */
    public function getClaimableBalances(): ClaimableBalancesResponse
    {
        return $this->claimableBalances;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['_links'])) $this->links = PagingLinksResponse::fromJson($json['_links']);
        if (isset($json['_embedded']['records'])) {
            $this->claimableBalances = new ClaimableBalancesResponse();
            foreach ($json['_embedded']['records'] as $jsonValue) {
                $claimableBalance = ClaimableBalanceResponse::fromJson($jsonValue);
                $this->claimableBalances->add($claimableBalance);
            }
        }
    }

    public static function fromJson(array $json) : ClaimableBalancesPageResponse
    {
        $result = new ClaimableBalancesPageResponse();
        $result->loadFromJson($json);
        return $result;
    }
}