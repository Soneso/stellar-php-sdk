<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Requests;

/**
 * Collection of TopicFilter objects used for filtering events by topics.
 * Implements iterator pattern for traversing multiple topic filters.
 *
 * @extends \IteratorIterator<int, TopicFilter, \ArrayIterator<int, TopicFilter>>
 * @see TopicFilter
 * @see https://developers.stellar.org/network/soroban-rpc/api-reference/methods/getEvents
 * @package Soneso\StellarSDK\Soroban\Requests
 */
class TopicFilters extends \IteratorIterator
{

    /**
     * Constructor.
     *
     * @param TopicFilter ...$responses Variable number of TopicFilter objects
     */
    public function __construct(TopicFilter ...$responses)
    {
        parent::__construct(new \ArrayIterator($responses));
    }

    /**
     * Returns the current TopicFilter in the iteration.
     *
     * @return TopicFilter The current topic filter
     */
    public function current(): TopicFilter
    {
        return parent::current();
    }

    /**
     * Adds a TopicFilter to the collection.
     *
     * @param TopicFilter $response The topic filter to add
     * @return void
     */
    public function add(TopicFilter $response)
    {
        $this->getInnerIterator()->append($response);
    }

    /**
     * Returns the number of TopicFilter objects in the collection.
     *
     * @return int The count of topic filters
     */
    public function count(): int
    {
        return $this->getInnerIterator()->count();
    }

    /**
     * @return array<TopicFilter> list of topic filters.
     */
    public function toArray() : array {
        /**
         * @var array<TopicFilter> $result
         */
        $result = array();
        foreach($this as $value) {
            array_push($result, $value);
        }
        return $result;
    }
}