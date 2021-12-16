<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\PaymentPath;

use Soneso\StellarSDK\Asset;

class PathAssetsResponse extends \IteratorIterator
{

    public function __construct(Asset ...$responses)
    {
        parent::__construct(new \ArrayIterator($responses));
    }

    public function current(): Asset
    {
        return parent::current();
    }

    public function add(Asset $response)
    {
        $this->getInnerIterator()->append($response);
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