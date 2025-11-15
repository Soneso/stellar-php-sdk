<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrLiquidityPoolDepositOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;

/**
 * Represents <a href="https://developers.stellar.org" target="_blank">Stellar developer docs</a> operation.
 *
 * Deposits assets into a liquidity pool, contributing to the pool's reserves.
 *
 * @package Soneso\StellarSDK
 * @see <a href="https://developers.stellar.org" target="_blank">Stellar developer docs</a>
 * @see LiquidityPoolWithdrawOperation For withdrawing from a pool
 * @since 1.0.0
 */
class LiquidityPoolDepositOperation extends AbstractOperation
{
    /**
     * Creates a new LiquidityPoolDepositOperation.
     *
     * @param string $liqudityPoolId The liquidity pool ID
     * @param string $maxAmountA Maximum amount of asset A to deposit (as a decimal string)
     * @param string $maxAmountB Maximum amount of asset B to deposit (as a decimal string)
     * @param Price $minPrice Minimum exchange rate for asset A to asset B
     * @param Price $maxPrice Maximum exchange rate for asset A to asset B
     */
    public function __construct(
        private string $liqudityPoolId,
        private string $maxAmountA,
        private string $maxAmountB,
        private Price $minPrice,
        private Price $maxPrice,
    ) {
    }

    /**
     * Gets the liquidity pool ID.
     *
     * @return string The liquidity pool ID
     */
    public function getLiqudityPoolId(): string
    {
        return $this->liqudityPoolId;
    }

    /**
     * Gets the maximum amount of asset A.
     *
     * @return string The maximum amount as a decimal string
     */
    public function getMaxAmountA(): string
    {
        return $this->maxAmountA;
    }

    /**
     * Gets the maximum amount of asset B.
     *
     * @return string The maximum amount as a decimal string
     */
    public function getMaxAmountB(): string
    {
        return $this->maxAmountB;
    }

    /**
     * Gets the minimum price.
     *
     * @return Price The minimum price
     */
    public function getMinPrice(): Price
    {
        return $this->minPrice;
    }

    /**
     * Gets the maximum price.
     *
     * @return Price The maximum price
     */
    public function getMaxPrice(): Price
    {
        return $this->maxPrice;
    }

    /**
     * Creates a LiquidityPoolDepositOperation from its XDR representation.
     *
     * @param XdrLiquidityPoolDepositOperation $xdrOp The XDR liquidity pool deposit operation to convert
     * @return LiquidityPoolDepositOperation The resulting LiquidityPoolDepositOperation instance
     */
    public static function fromXdrOperation(XdrLiquidityPoolDepositOperation $xdrOp): LiquidityPoolDepositOperation {
        $maxAmountA = AbstractOperation::fromXdrAmount($xdrOp->getMaxAmountA());
        $maxAmountB = AbstractOperation::fromXdrAmount($xdrOp->getMaxAmountB());
        $minPrice = Price::fromXdr($xdrOp->getMinPrice());
        $maxPrice = Price::fromXdr($xdrOp->getMaxPrice());
        $liquidityPoolId = $xdrOp->getLiquidityPoolID();
        return new LiquidityPoolDepositOperation($liquidityPoolId, $maxAmountA, $maxAmountB, $minPrice, $maxPrice);
    }

    /**
     * Converts this operation to its XDR operation body representation.
     *
     * @return XdrOperationBody The XDR operation body
     */
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