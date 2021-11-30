<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\ClaimableBalances;

class ClaimantsResponse extends \IteratorIterator
{

    public function __construct(ClaimantResponse ...$claimants)
    {
        parent::__construct(new \ArrayIterator($claimants));
    }

    public function current(): ClaimantResponse
    {
        return parent::current();
    }

    public function add(ClaimantResponse $claimant)
    {
        $this->getInnerIterator()->append($claimant);
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