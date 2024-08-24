<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;

class AssetBalanceChangesResponse extends \IteratorIterator
{

    public function __construct(AssetBalanceChangeResponse ...$responses)
    {
        parent::__construct(new \ArrayIterator($responses));
    }

    public function current(): AssetBalanceChangeResponse
    {
        return parent::current();
    }

    public function add(AssetBalanceChangeResponse $response)
    {
        $this->getInnerIterator()->append($response);
    }

    public function count(): int
    {
        return $this->getInnerIterator()->count();
    }

    /**
     * @return array<AssetBalanceChangeResponse>
     */
    public function toArray() : array {
        /**
         * @var array<AssetBalanceChangeResponse> $result
         */
        $result = array();
        foreach($this as $value) {
            array_push($result, $value);
        }
        return $result;
    }
}