<?php  declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Transaction;

class PreconditionsLedgerBoundsResponse
{
    private int $minLedger;
    private int $maxLedger;

    /**
     * @return int
     */
    public function getMinLedger(): int
    {
        return $this->minLedger;
    }

    /**
     * @return int
     */
    public function getMaxLedger(): int
    {
        return $this->maxLedger;
    }

    protected function loadFromJson(array $json): void
    {
        if (isset($json['min_ledger'])) {
            $this->minLedger = $json['min_ledger'];
        } else {
            $this->minLedger = 0;
        }
        if (isset($json['max_ledger'])) {
            $this->maxLedger = $json['max_ledger'];
        } else {
            $this->maxLedger = 0;
        }
    }

    public static function fromJson(array $json): PreconditionsLedgerBoundsResponse
    {
        $result = new PreconditionsLedgerBoundsResponse();
        $result->loadFromJson($json);
        return $result;
    }
}