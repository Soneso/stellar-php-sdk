<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\LiquidityPools;

use Soneso\StellarSDK\Responses\Page\PageResponse;
use Soneso\StellarSDK\Responses\Page\PagingLinksResponse;

class LiquidityPoolsPageResponse extends PageResponse
{
    private PagingLinksResponse $links;
    private LiquidityPoolsResponse $liquidityPools;

    /**
     * @return PagingLinksResponse
     */
    public function getLinks(): PagingLinksResponse
    {
        return $this->links;
    }

    /**
     * @return LiquidityPoolsResponse
     */
    public function getLiquidityPools(): LiquidityPoolsResponse
    {
        return $this->liquidityPools;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['_links'])) $this->links = PagingLinksResponse::fromJson($json['_links']);
        if (isset($json['_embedded']['records'])) {
            $this->liquidityPools = new LiquidityPoolsResponse();
            foreach ($json['_embedded']['records'] as $jsonValue) {
                $value = LiquidityPoolResponse::fromJson($jsonValue);
                $this->liquidityPools->add($value);
            }
        }
    }

    public static function fromJson(array $json) : LiquidityPoolsPageResponse
    {
        $result = new LiquidityPoolsPageResponse();
        $result->loadFromJson($json);
        return $result;
    }
}