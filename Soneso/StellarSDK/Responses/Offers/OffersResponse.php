<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Offers;

/**
 * Iterable collection of offer responses
 *
 * This class provides an iterable wrapper around a collection of OfferResponse objects
 * representing DEX offers. It extends IteratorIterator to enable efficient traversal of
 * offers returned from Horizon API endpoints. The collection supports iteration, counting,
 * array conversion, and dynamic addition of offer records.
 *
 * Used by OffersPageResponse to hold the offers contained in a single page of results.
 * Each item in the collection represents an individual standing order on the Stellar
 * distributed exchange with its price, amounts, and asset details.
 *
 * @package Soneso\StellarSDK\Responses\Offers
 * @see OfferResponse For individual offer details
 * @see OffersPageResponse For paginated offer results
 */
class OffersResponse extends \IteratorIterator
{

    /**
     * Constructs a new offers collection
     *
     * @param OfferResponse ...$offers Variable number of offer responses
     */
    public function __construct(OfferResponse ...$offers)
    {
        parent::__construct(new \ArrayIterator($offers));
    }

    /**
     * Gets the current offer in the iteration
     *
     * @return OfferResponse The current offer response
     */
    public function current(): OfferResponse
    {
        return parent::current();
    }

    /**
     * Adds an offer response to the collection
     *
     * @param OfferResponse $offer The offer response to add
     * @return void
     */
    public function add(OfferResponse $offer)
    {
        $this->getInnerIterator()->append($offer);
    }

    /**
     * Gets the total number of offers in this collection
     *
     * @return int The count of offers
     */
    public function count(): int
    {
        return $this->getInnerIterator()->count();
    }

    /**
     * @return array<OfferResponse>
     */
    public function toArray() : array {
        /**
         * @var array<OfferResponse> $result
         */
        $result = array();
        foreach($this as $value) {
            array_push($result, $value);
        }
        return $result;
    }
}