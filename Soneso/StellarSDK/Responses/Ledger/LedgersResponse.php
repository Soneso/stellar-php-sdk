<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Ledger;

class LedgersResponse extends \IteratorIterator {

    public function __construct(LedgerResponse ...$ledgers)
    {
        parent::__construct(new \ArrayIterator($ledgers));
    }

    public function current(): LedgerResponse
    {
        return parent::current();
    }

    public function add(LedgerResponse $ledger)
    {
        $this->getInnerIterator()->append($ledger);
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