<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Soroban\Requests;

/**
 * Collection of EventFilter objects used in Soroban RPC getEvents requests.
 * Implements iterator pattern for traversing multiple event filters.
 *
 * @extends \IteratorIterator<int, EventFilter, \ArrayIterator<int, EventFilter>>
 * @see EventFilter
 * @see https://soroban.stellar.org/api/methods/getEvents
 * @package Soneso\StellarSDK\Soroban\Requests
 */
class EventFilters extends \IteratorIterator
{

    /**
     * Constructor.
     *
     * @param EventFilter ...$responses Variable number of EventFilter objects
     */
    public function __construct(EventFilter ...$responses)
    {
        parent::__construct(new \ArrayIterator($responses));
    }

    /**
     * Returns the current EventFilter in the iteration.
     *
     * @return EventFilter The current event filter
     */
    public function current(): EventFilter
    {
        return parent::current();
    }

    /**
     * Adds an EventFilter to the collection.
     *
     * @param EventFilter $response The event filter to add
     * @return void
     */
    public function add(EventFilter $response)
    {
        $this->getInnerIterator()->append($response);
    }

    /**
     * Returns the number of EventFilter objects in the collection.
     *
     * @return int The count of event filters
     */
    public function count(): int
    {
        return $this->getInnerIterator()->count();
    }

    /**
     * @return array<EventFilter>
     */
    public function toArray() : array {
        /**
         * @var array<EventFilter> $result
         */
        $result = array();
        foreach($this as $value) {
            array_push($result, $value);
        }
        return $result;
    }
}