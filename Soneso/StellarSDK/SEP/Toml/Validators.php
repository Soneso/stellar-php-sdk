<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Toml;

class Validators extends \IteratorIterator
{

    public function __construct(Validator ...$validators)
    {
        parent::__construct(new \ArrayIterator($validators));
    }

    public function current(): Validator
    {
        return parent::current();
    }

    public function add(Validator $validator)
    {
        $this->getInnerIterator()->append($validator);
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