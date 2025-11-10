<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Toml;

/**
 * Collection of Validator objects from the stellar.toml file.
 *
 * Provides an iterable collection for managing multiple validator entries
 * from the [[VALIDATORS]] section of the stellar.toml file.
 */
class Validators extends \IteratorIterator
{

    public function __construct(Validator ...$validators)
    {
        parent::__construct(new \ArrayIterator($validators));
    }

    /**
     * Returns the current Validator in the iteration.
     *
     * @return Validator The current validator object
     */
    public function current(): Validator
    {
        return parent::current();
    }

    /**
     * Adds a Validator to the collection.
     *
     * @param Validator $validator The validator to add
     * @return void
     */
    public function add(Validator $validator)
    {
        $this->getInnerIterator()->append($validator);
    }

    /**
     * Returns the number of validators in the collection.
     *
     * @return int The count of validators
     */
    public function count(): int
    {
        return $this->getInnerIterator()->count();
    }

    /**
     * @return array<Validator>
     */
    public function toArray() : array {
        /**
         * @var array<Validator> $result
         */
        $result = array();
        foreach($this as $value) {
            array_push($result, $value);
        }
        return $result;
    }
}