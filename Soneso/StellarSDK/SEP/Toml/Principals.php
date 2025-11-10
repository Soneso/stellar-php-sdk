<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Toml;

/**
 * Collection of PointOfContact objects from the stellar.toml file.
 *
 * Provides an iterable collection for managing multiple principal entries
 * from the [[PRINCIPALS]] section of the stellar.toml file.
 */
class Principals extends \IteratorIterator
{

    public function __construct(PointOfContact ...$pointsOfContact)
    {
        parent::__construct(new \ArrayIterator($pointsOfContact));
    }

    /**
     * Returns the current PointOfContact in the iteration.
     *
     * @return PointOfContact The current point of contact object
     */
    public function current(): PointOfContact
    {
        return parent::current();
    }

    /**
     * Adds a PointOfContact to the collection.
     *
     * @param PointOfContact $pointOfContact The point of contact to add
     * @return void
     */
    public function add(PointOfContact $pointOfContact)
    {
        $this->getInnerIterator()->append($pointOfContact);
    }

    /**
     * Returns the number of principals in the collection.
     *
     * @return int The count of principals
     */
    public function count(): int
    {
        return $this->getInnerIterator()->count();
    }

    /**
     * @return array<PointOfContact>
     */
    public function toArray() : array {
        /**
         * @var array<PointOfContact> $result
         */
        $result = array();
        foreach($this as $value) {
            array_push($result, $value);
        }
        return $result;
    }
}