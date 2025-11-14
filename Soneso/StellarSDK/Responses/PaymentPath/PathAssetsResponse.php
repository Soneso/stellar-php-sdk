<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\PaymentPath;

use Soneso\StellarSDK\Asset;

/**
 * Iterable collection of assets in a payment path
 *
 * This class provides an iterable wrapper around a collection of Asset objects representing
 * the sequence of intermediate assets used in a path payment. Path payments allow sending one
 * asset while the recipient receives a different asset, with the network automatically finding
 * the best conversion route through multiple asset pairs.
 *
 * The collection holds the ordered sequence of assets that form the conversion path, excluding
 * the source and destination assets. For example, in a path from USD to EUR through XLM, this
 * collection would contain [XLM] as the intermediate asset.
 *
 * @package Soneso\StellarSDK\Responses\PaymentPath
 * @see Asset For individual asset details
 * @see PathResponse For the complete payment path information
 */
class PathAssetsResponse extends \IteratorIterator
{

    /**
     * Constructs a new path assets collection
     *
     * @param Asset ...$responses Variable number of asset objects
     */
    public function __construct(Asset ...$responses)
    {
        parent::__construct(new \ArrayIterator($responses));
    }

    /**
     * Gets the current asset in the iteration
     *
     * @return Asset The current asset in the path
     */
    public function current(): Asset
    {
        return parent::current();
    }

    /**
     * Adds an asset to the path collection
     *
     * @param Asset $response The asset to add
     * @return void
     */
    public function add(Asset $response)
    {
        $this->getInnerIterator()->append($response);
    }

    /**
     * Gets the total number of assets in this path
     *
     * @return int The count of intermediate assets
     */
    public function count(): int
    {
        return $this->getInnerIterator()->count();
    }

    /**
     * @return array<Asset>
     */
    public function toArray() : array {
        /**
         * @var array<Asset> $result
         */
        $result = array();
        foreach($this as $value) {
            array_push($result, $value);
        }
        return $result;
    }
}