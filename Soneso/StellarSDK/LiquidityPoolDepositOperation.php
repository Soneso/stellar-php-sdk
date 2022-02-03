<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrLiquidityPoolDepositOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;

class LiquidityPoolDepositOperation extends AbstractOperation
{
    private string $liqudityPoolId;
    private string $maxAmountA;
    private string $maxAmountB;
    private Price $minPrice;
    private Price $maxPrice;

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

    /**
     * @return Price
     */
    public function getMinPrice(): Price
    {
        return $this->minPrice;
    }

    /**
     * @return Price
     */
    public function getMaxPrice(): Price
    {
        return $this->maxPrice;
    }

    public static function fromXdrOperation(XdrLiquidityPoolDepositOperation $xdrOp): LiquidityPoolDepositOperation {
        $maxAmountA = AbstractOperation::fromXdrAmount($xdrOp->getMaxAmountA());
        $maxAmountB = AbstractOperation::fromXdrAmount($xdrOp->getMaxAmountB());
        $minPrice = Price::fromXdr($xdrOp->getMinPrice());
        $maxPrice = Price::fromXdr($xdrOp->getMaxPrice());
        $liquidityPoolId = $xdrOp->getLiquidityPoolID();
        return new LiquidityPoolDepositOperation($liquidityPoolId, $maxAmountA, $maxAmountB, $minPrice, $maxPrice);
    }

    public function toOperationBody(): XdrOperationBody
    {
        $maxAmountA = AbstractOperation::toXdrAmount($this->maxAmountA);
        $maxAmountB = AbstractOperation::toXdrAmount($this->maxAmountB);

        $minPrice = $this->minPrice->toXdr();
        $maxPrice = $this->maxPrice->toXdr();

        $op = new XdrLiquidityPoolDepositOperation($this->liqudityPoolId, $maxAmountA, $maxAmountB, $minPrice, $maxPrice);
        $type = new XdrOperationType(XdrOperationType::LIQUIDITY_POOL_DEPOSIT);
        $result = new XdrOperationBody($type);
        $result->setLiquidityPoolDepositOperation($op);
        return $result;
    }
}