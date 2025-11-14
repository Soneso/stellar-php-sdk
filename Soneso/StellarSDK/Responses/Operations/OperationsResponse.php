<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Operations;

/**
 * Collection of operation responses from Horizon API
 *
 * Provides an iterable collection of OperationResponse objects. Supports iteration through
 * operations, adding new operations to the collection, counting operations, and converting
 * the collection to an array. Used within OperationsPageResponse for paginated results.
 *
 * @package Soneso\StellarSDK\Responses\Operations
 */
class OperationsResponse extends \IteratorIterator
{

    public function __construct(OperationResponse ...$responses)
    {
        parent::__construct(new \ArrayIterator($responses));
    }

    public function current(): OperationResponse
    {
        return parent::current();
    }

    /**
     * Adds an operation to the collection
     *
     * @param OperationResponse $response The operation response to add
     * @return void
     */
    public function add(OperationResponse $response)
    {
        $this->getInnerIterator()->append($response);
    }

    /**
     * Gets the number of operations in the collection
     *
     * @return int The operation count
     */
    public function count(): int
    {
        return $this->getInnerIterator()->count();
    }

    /**
     * @return array<OperationResponse>
     */
    public function toArray() : array {
        /**
         * @var array<OperationResponse> $result
         */
        $result = array();
        foreach($this as $value) {
            array_push($result, $value);
        }
        return $result;
    }
}