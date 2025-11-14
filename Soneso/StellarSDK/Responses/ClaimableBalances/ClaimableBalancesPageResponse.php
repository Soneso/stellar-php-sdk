<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\ClaimableBalances;

use Soneso\StellarSDK\Requests\RequestType;
use Soneso\StellarSDK\Responses\Page\PageResponse;

/**
 * Represents a paginated collection of claimable balances from Horizon
 *
 * @package Soneso\StellarSDK\Responses\ClaimableBalances
 * @see PageResponse For pagination functionality
 * @see ClaimableBalanceResponse For individual claimable balance details
 * @see https://developers.stellar.org Stellar developer docs Horizon Claimable Balances API
 * @since 1.0.0
 */
class ClaimableBalancesPageResponse extends PageResponse
{
    private ClaimableBalancesResponse $claimableBalances;

    /**
     * Gets the collection of claimable balances in this page
     *
     * @return ClaimableBalancesResponse The claimable balances collection
     */
    public function getClaimableBalances(): ClaimableBalancesResponse {
        return $this->claimableBalances;
    }

    protected function loadFromJson(array $json) : void {
        parent::loadFromJson($json);
        if (isset($json['_embedded']['records'])) {
            $this->claimableBalances = new ClaimableBalancesResponse();
            foreach ($json['_embedded']['records'] as $jsonValue) {
                $claimableBalance = ClaimableBalanceResponse::fromJson($jsonValue);
                $this->claimableBalances->add($claimableBalance);
            }
        }
    }

    public static function fromJson(array $json) : ClaimableBalancesPageResponse {
        $result = new ClaimableBalancesPageResponse();
        $result->loadFromJson($json);
        return $result;
    }

    public function getNextPage(): ClaimableBalancesPageResponse | null {
        return $this->executeRequest(RequestType::CLAIMABLE_BALANCES_PAGE, $this->getNextPageUrl());
    }

    public function getPreviousPage(): ClaimableBalancesPageResponse | null {
        return $this->executeRequest(RequestType::CLAIMABLE_BALANCES_PAGE, $this->getPrevPageUrl());
    }
}