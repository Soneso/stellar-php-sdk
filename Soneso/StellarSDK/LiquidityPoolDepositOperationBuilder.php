<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

class LiquidityPoolDepositOperationBuilder
{
    private string $liqudityPoolId;
    private string $maxAmountA;
    private string $maxAmountB;
    private Price $minPrice;
    private Price $maxPrice;
    private ?MuxedAccount $sourceAccount = null;

    /**
     * @param string $liqudityPoolId
     * @param string $maxAmountA
     * @param string $maxAmountB
     * @param Price $minPrice
     * @param Price $maxPrice
     */
    public function __construct(string $liqudityPoolId, string $maxAmountA, string $maxAmountB, Price $minPrice, Price $maxPrice)
    {
        $this->liqudityPoolId = $liqudityPoolId;
        $this->maxAmountA = $maxAmountA;
        $this->maxAmountB = $maxAmountB;
        $this->minPrice = $minPrice;
        $this->maxPrice = $maxPrice;
    }

    public function setSourceAccount(string $accountId) : LiquidityPoolDepositOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : LiquidityPoolDepositOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    public function build(): LiquidityPoolDepositOperation {
        $result = new LiquidityPoolDepositOperation($this->liqudityPoolId, $this->maxAmountA, $this->maxAmountB, $this->minPrice, $this->maxPrice);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}