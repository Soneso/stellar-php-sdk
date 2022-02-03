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
    private string $minAmountA;
    private string $minAmountB;

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
    public function getMinAmountA(): string
    {
        return $this->minAmountA;
    }

    /**
     * @return string
     */
    public function getMinAmountB(): string
    {
        return $this->minAmountB;
    }


    public static function fromXdrOperation(XdrLiquidityPoolWithdrawOperation $xdrOp): LiquidityPoolWithdrawOperation {
        $minAmountA = AbstractOperation::fromXdrAmount($xdrOp->getMinAmountA());
        $minAmountB = AbstractOperation::fromXdrAmount($xdrOp->getMinAmountB());
        $amount = AbstractOperation::fromXdrAmount($xdrOp->getAmount());
        $liquidityPoolId = $xdrOp->getLiquidityPoolID();
        return new LiquidityPoolWithdrawOperation($liquidityPoolId, $amount, $minAmountA, $minAmountB);
    }

    public function toOperationBody(): XdrOperationBody
    {
        $amount = AbstractOperation::toXdrAmount($this->amount);
        $minAmountA = AbstractOperation::toXdrAmount($this->minAmountA);
        $minAmountB = AbstractOperation::toXdrAmount($this->minAmountB);

        $op = new XdrLiquidityPoolWithdrawOperation($this->liqudityPoolId, $amount, $minAmountA, $minAmountB);
        $type = new XdrOperationType(XdrOperationType::LIQUIDITY_POOL_WITHDRAW);
        $result = new XdrOperationBody($type);
        $result->setLiquidityPoolWithdrawOperation($op);
        return $result;
    }
}