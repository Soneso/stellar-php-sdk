<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Requests;

/**
 * Part of the getEvents request.
 * See: https://developers.stellar.org/network/soroban-rpc/api-reference/methods/getEvents
 */
class TopicFilters extends \IteratorIterator
{

    public function __construct(TopicFilter ...$responses)
    {
        parent::__construct(new \ArrayIterator($responses));
    }

    public function current(): TopicFilter
    {
        return parent::current();
    }

    public function add(TopicFilter $response)
    {
        $this->getInnerIterator()->append($response);
    }

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