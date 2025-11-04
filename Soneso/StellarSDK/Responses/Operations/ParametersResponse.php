<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;

/**
 * Collection of Soroban contract function parameters
 *
 * Provides an iterable collection of ParameterResponse objects representing all parameters
 * passed to a smart contract function. Supports iteration, counting, and conversion to array.
 * Used in InvokeHostFunctionOperationResponse to represent function arguments.
 *
 * @package Soneso\StellarSDK\Responses\Operations
 */
class ParametersResponse extends \IteratorIterator
{

    public function __construct(ParameterResponse ...$responses)
    {
        parent::__construct(new \ArrayIterator($responses));
    }

    public function current(): ParameterResponse
    {
        return parent::current();
    }

    /**
     * Adds a parameter to the collection
     *
     * @param ParameterResponse $response The parameter response to add
     * @return void
     */
    public function add(ParameterResponse $response)
    {
        $this->getInnerIterator()->append($response);
    }

    /**
     * Gets the number of parameters in the collection
     *
     * @return int The parameter count
     */
    public function count(): int
    {
        return $this->getInnerIterator()->count();
    }

    /**
     * @return array<ParameterResponse>
     */
    public function toArray() : array {
        /**
         * @var array<ParameterResponse> $result
         */
        $result = array();
        foreach($this as $value) {
            array_push($result, $value);
        }
        return $result;
    }
}