<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Toml;

/**
 * Collection of Currency objects from the stellar.toml file.
 *
 * Provides an iterable collection for managing multiple currency entries
 * from the [[CURRENCIES]] section of the stellar.toml file.
 */
class Currencies extends \IteratorIterator
{

    public function __construct(Currency ...$currencies)
    {
        parent::__construct(new \ArrayIterator($currencies));
    }

    /**
     * Returns the current Currency in the iteration.
     *
     * @return Currency The current currency object
     */
    public function current(): Currency
    {
        return parent::current();
    }

    /**
     * Adds a Currency to the collection.
     *
     * @param Currency $currency The currency to add
     * @return void
     */
    public function add(Currency $currency)
    {
        $this->getInnerIterator()->append($currency);
    }

    /**
     * Returns the number of currencies in the collection.
     *
     * @return int The count of currencies
     */
    public function count(): int
    {
        return $this->getInnerIterator()->count();
    }

    /**
     * @return array<Currency>
     */
    public function toArray() : array {
        /**
         * @var array<Currency> $result
         */
        $result = array();
        foreach($this as $value) {
            array_push($result, $value);
        }
        return $result;
    }
}