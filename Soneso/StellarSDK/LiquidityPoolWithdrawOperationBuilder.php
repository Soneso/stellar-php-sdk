<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

class LiquidityPoolWithdrawOperationBuilder
{

    private string $liqudityPoolId;
    private string $amount;
    private string $maxAmountA;
    private string $maxAmountB;
    private ?MuxedAccount $sourceAccount = null;

    /**
     * @param string $liqudityPoolId
     * @param string $amount
     * @param string $maxAmountA
     * @param string $maxAmountB
     */
    public function __construct(string $liqudityPoolId, string $amount, string $maxAmountA, string $maxAmountB)
    {
        $this->liqudityPoolId = $liqudityPoolId;
        $this->amount = $amount;
        $this->maxAmountA = $maxAmountA;
        $this->maxAmountB = $maxAmountB;
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
        $result = new LiquidityPoolWithdrawOperation($this->liqudityPoolId, $this->amount, $this->maxAmountA, $this->maxAmountB);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}