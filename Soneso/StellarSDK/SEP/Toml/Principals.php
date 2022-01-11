<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Toml;

class Principals extends \IteratorIterator
{

    public function __construct(PointOfContact ...$pointsOfContact)
    {
        parent::__construct(new \ArrayIterator($pointsOfContact));
    }

    public function current(): PointOfContact
    {
        return parent::current();
    }

    public function add(PointOfContact $pointOfContact)
    {
        $this->getInnerIterator()->append($pointOfContact);
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