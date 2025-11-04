<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\PaymentPath;

/**
 * Iterable collection of payment path responses
 *
 * This class provides an iterable wrapper around a collection of PathResponse objects
 * representing possible asset conversion routes. It extends IteratorIterator to enable
 * efficient traversal of payment paths returned from Horizon API endpoints. The collection
 * supports iteration, counting, array conversion, and dynamic addition of path records.
 *
 * Used by PathsPageResponse to hold the paths contained in a single page of results.
 * Each item represents a potential route for converting one asset to another through
 * intermediate assets, including the required source amount and resulting destination amount.
 *
 * @package Soneso\StellarSDK\Responses\PaymentPath
 * @see PathResponse For individual path details
 * @see PathsPageResponse For paginated path results
 */
class PathsResponse extends \IteratorIterator
{

    /**
     * Constructs a new paths collection
     *
     * @param PathResponse ...$responses Variable number of path responses
     */
    public function __construct(PathResponse ...$responses)
    {
        parent::__construct(new \ArrayIterator($responses));
    }

    /**
     * Gets the current path in the iteration
     *
     * @return PathResponse The current path response
     */
    public function current(): PathResponse
    {
        return parent::current();
    }

    /**
     * Adds a path response to the collection
     *
     * @param PathResponse $response The path response to add
     * @return void
     */
    public function add(PathResponse $response)
    {
        $this->getInnerIterator()->append($response);
    }

    /**
     * Gets the total number of paths in this collection
     *
     * @return int The count of payment paths
     */
    public function count(): int
    {
        return $this->getInnerIterator()->count();
    }

    /**
     * @return array<PathResponse>
     */
    public function toArray() : array {
        /**
         * @var array<PathResponse> $result
         */
        $result = array();
        foreach($this as $value) {
            array_push($result, $value);
        }
        return $result;
    }
}