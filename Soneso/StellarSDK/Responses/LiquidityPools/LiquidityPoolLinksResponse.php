<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\LiquidityPools;

use Soneso\StellarSDK\Responses\Link\LinkResponse;

/**
 * Represents HAL links for a liquidity pool response
 *
 * @package Soneso\StellarSDK\Responses\LiquidityPools
 * @see LiquidityPoolResponse For the parent liquidity pool details
 * @see LinkResponse For the link structure
 * @since 1.0.0
 */
class LiquidityPoolLinksResponse
{

    private LinkResponse $self;
    private LinkResponse $operations;
    private LinkResponse $transactions;

    /**
     * Gets the self-referencing link to this liquidity pool
     *
     * @return LinkResponse The self link
     */
    public function getSelf() : LinkResponse {
        return $this->self;
    }

    /**
     * Gets the link to operations related to this liquidity pool
     *
     * @return LinkResponse The operations link
     */
    public function getOperations() : LinkResponse {
        return $this->operations;
    }

    /**
     * Gets the link to transactions involving this liquidity pool
     *
     * @return LinkResponse The transactions link
     */
    public function getTransactions() : LinkResponse {
        return $this->transactions;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['self'])) $this->self = LinkResponse::fromJson($json['self']);
        if (isset($json['operations'])) $this->operations = LinkResponse::fromJson($json['operations']);
        if (isset($json['transactions'])) $this->transactions = LinkResponse::fromJson($json['transactions']);
    }

    public static function fromJson(array $json) : LiquidityPoolLinksResponse {
        $result = new LiquidityPoolLinksResponse();
        $result->loadFromJson($json);
        return $result;
    }
}
