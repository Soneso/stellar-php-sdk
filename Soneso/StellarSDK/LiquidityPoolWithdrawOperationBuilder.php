<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

class LiquidityPoolWithdrawOperationBuilder
{

    private string $liqudityPoolId;
    private string $amount;
    private string $minAmountA;
    private string $minAmountB;
    private ?MuxedAccount $sourceAccount = null;

    /**
     * @param string $liqudityPoolId
     * @param string $amount
     * @param string $minAmountA
     * @param string $minAmountB
     */
    public function __construct(string $liqudityPoolId, string $amount, string $minAmountA, string $minAmountB)
    {
        $this->liqudityPoolId = $liqudityPoolId;
        $this->amount = $amount;
        $this->minAmountA = $minAmountA;
        $this->minAmountB = $minAmountB;
    }

    public function setSourceAccount(string $accountId) : LiquidityPoolWithdrawOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : LiquidityPoolWithdrawOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    public function build(): LiquidityPoolWithdrawOperation {
        $result = new LiquidityPoolWithdrawOperation($this->liqudityPoolId, $this->amount, $this->minAmountA, $this->minAmountB);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}