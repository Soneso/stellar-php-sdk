<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Asset;

/**
 * Iterable collection of asset responses
 *
 * Provides iterator functionality for traversing multiple AssetResponse objects.
 * Supports adding assets, counting, and converting to array.
 *
 * @package Soneso\StellarSDK\Responses\Asset
 * @see AssetResponse For individual asset details
 * @since 1.0.0
 */
class AssetsResponse extends \IteratorIterator
{

    /**
     * Constructs an assets collection
     *
     * @param AssetResponse ...$assets Variable number of asset responses
     */
    public function __construct(AssetResponse ...$assets)
    {
        parent::__construct(new \ArrayIterator($assets));
    }

    /**
     * Gets the current asset in the iteration
     *
     * @return AssetResponse The current asset
     */
    public function current(): AssetResponse
    {
        return parent::current();
    }

    /**
     * Adds an asset to the collection
     *
     * @param AssetResponse $asset The asset to add
     * @return void
     */
    public function add(AssetResponse $asset)
    {
        $this->getInnerIterator()->append($asset);
    }

    /**
     * Gets the total number of assets in the collection
     *
     * @return int The count of assets
     */
    public function count(): int
    {
        return $this->getInnerIterator()->count();
    }

    /**
     * Converts the collection to an array
     *
     * @return array<AssetResponse> Array of asset responses
     */
    public function toArray() : array {
        /**
         * @var array<AssetResponse> $result
         */
        $result = array();
        foreach($this as $value) {
            array_push($result, $value);
        }
        return $result;
    }
}