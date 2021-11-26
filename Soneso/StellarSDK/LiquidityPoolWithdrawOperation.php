<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrLiquidityPoolWithdrawOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;

class LiquidityPoolWithdrawOperation extends AbstractOperation
{

    private string $liqudityPoolId;
    private string $amount;
    private string $maxAmountA;
    private string $maxAmountB;

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

    /**
     * @return string
     */
    public function getLiqudityPoolId(): string
    {
        return $this->liqudityPoolId;
    }

    /**
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getMaxAmountA(): string
    {
        return $this->maxAmountA;
    }

    /**
     * @return string
     */
    public function getMaxAmountB(): string
    {
        return $this->maxAmountB;
    }

    public function toOperationBody(): XdrOperationBody
    {
        $amount = AbstractOperation::toXdrAmount($this->amount);
        $maxAmountA = AbstractOperation::toXdrAmount($this->maxAmountA);
        $maxAmountB = AbstractOperation::toXdrAmount($this->maxAmountB);

        $op = new XdrLiquidityPoolWithdrawOperation($this->liqudityPoolId, $amount, $maxAmountA, $maxAmountB);
        $type = new XdrOperationType(XdrOperationType::LIQUIDITY_POOL_WITHDRAW);
        $result = new XdrOperationBody($type);
        $result->setLiquidityPoolWithdrawOperation($op);
        return $result;
    }
}