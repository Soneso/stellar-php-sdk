<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\LiquidityPools;

use Soneso\StellarSDK\Requests\RequestType;
use Soneso\StellarSDK\Responses\Page\PageResponse;

class LiquidityPoolsPageResponse extends PageResponse
{
    private LiquidityPoolsResponse $liquidityPools;


    /**
     * @return LiquidityPoolsResponse
     */
    public function getLiquidityPools(): LiquidityPoolsResponse {
        return $this->liquidityPools;
    }

    protected function loadFromJson(array $json) : void {
        parent::loadFromJson($json);
        if (isset($json['_embedded']['records'])) {
            $this->liquidityPools = new LiquidityPoolsResponse();
            foreach ($json['_embedded']['records'] as $jsonValue) {
                $value = LiquidityPoolResponse::fromJson($jsonValue);
                $this->liquidityPools->add($value);
            }
        }
    }

    public static function fromJson(array $json) : LiquidityPoolsPageResponse {
        $result = new LiquidityPoolsPageResponse();
        $result->loadFromJson($json);
        return $result;
    }

    public function getNextPage(): LiquidityPoolsPageResponse | null {
        return $this->executeRequest(RequestType::LIQUIDITY_POOLS_PAGE, $this->getNextPageUrl());
    }

    public function getPreviousPage(): LiquidityPoolsPageResponse | null {
        return $this->executeRequest(RequestType::LIQUIDITY_POOLS_PAGE, $this->getPrevPageUrl());
    }
}