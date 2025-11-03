<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\ClaimableBalances;

use Soneso\StellarSDK\Responses\Link\LinkResponse;

/**
 * Represents HAL links for a claimable balance response
 *
 * @package Soneso\StellarSDK\Responses\ClaimableBalances
 * @see ClaimableBalanceResponse For the parent claimable balance details
 * @see LinkResponse For the link structure
 * @since 1.0.0
 */
class ClaimableBalanceLinksResponse
{

    private LinkResponse $self;

    /**
     * Gets the self-referencing link to this claimable balance
     *
     * @return LinkResponse The self link
     */
    public function getSelf() : LinkResponse {
        return $this->self;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['self'])) $this->self = LinkResponse::fromJson($json['self']);
    }

    public static function fromJson(array $json) : ClaimableBalanceLinksResponse {
        $result = new ClaimableBalanceLinksResponse();
        $result->loadFromJson($json);
        return $result;
    }

}