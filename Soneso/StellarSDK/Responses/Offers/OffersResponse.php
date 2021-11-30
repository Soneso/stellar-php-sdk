<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Offers;


class OffersResponse extends \IteratorIterator
{

    public function __construct(OfferResponse ...$offers)
    {
        parent::__construct(new \ArrayIterator($offers));
    }

    public function current(): OfferResponse
    {
        return parent::current();
    }

    public function add(OfferResponse $offer)
    {
        $this->getInnerIterator()->append($offer);
    }

    public function count(): int
    {
        return $this->getInnerIterator()->count();
    }

    public function toArray() : array {
        $result = array();
        foreach($this as $value) {
            array_push($result, $value);
        }
        return $result;
    }
}