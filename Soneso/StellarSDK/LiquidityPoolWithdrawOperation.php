<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrLiquidityPoolWithdrawOperation;
use Soneso\StellarSDK\Xdr\XdrOperationBody;
use Soneso\StellarSDK\Xdr\XdrOperationType;

/**
 * Represents <a href="https://developers.stellar.org/docs/start/list-of-operations/#liquidity-pool-withdraw" target="_blank">LiquidityPoolWithdraw</a> operation.
 *
 * Withdraws assets from a liquidity pool, reducing the pool's reserves.
 *
 * @package Soneso\StellarSDK
 * @see <a href="https://developers.stellar.org/docs/start/list-of-operations/" target="_blank">List of Operations</a>
 * @see LiquidityPoolDepositOperation For depositing to a pool
 * @since 1.0.0
 */
class LiquidityPoolWithdrawOperation extends AbstractOperation
{
    /**
     * @var string The liquidity pool ID
     */
    private string $liqudityPoolId;

    /**
     * @var string The amount of pool shares to withdraw (as a decimal string)
     */
    private string $amount;

    /**
     * @var string Minimum amount of asset A to receive (as a decimal string)
     */
    private string $minAmountA;

    /**
     * @var string Minimum amount of asset B to receive (as a decimal string)
     */
    private string $minAmountB;

    /**
     * Creates a new LiquidityPoolWithdrawOperation.
     *
     * @param string $liqudityPoolId The liquidity pool ID
     * @param string $amount The amount of pool shares to withdraw
     * @param string $minAmountA Minimum amount of asset A to receive
     * @param string $minAmountB Minimum amount of asset B to receive
     */
    public function __construct(string $liqudityPoolId, string $amount, string $minAmountA, string $minAmountB)
    {
        $this->liqudityPoolId = $liqudityPoolId;
        $this->amount = $amount;
        $this->minAmountA = $minAmountA;
        $this->minAmountB = $minAmountB;
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
     * Gets the amount of pool shares to withdraw.
     *
     * @return string The amount as a decimal string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Gets the minimum amount of asset A to receive.
     *
     * @return string The minimum amount as a decimal string
     */
    public function getMinAmountA(): string
    {
        return $this->minAmountA;
    }

    /**
     * Gets the minimum amount of asset B to receive.
     *
     * @return string The minimum amount as a decimal string
     */
    public function getMinAmountB(): string
    {
        return $this->minAmountB;
    }

    /**
     * Creates a LiquidityPoolWithdrawOperation from its XDR representation.
     *
     * @param XdrLiquidityPoolWithdrawOperation $xdrOp The XDR liquidity pool withdraw operation to convert
     * @return LiquidityPoolWithdrawOperation The resulting LiquidityPoolWithdrawOperation instance
     */
    public static function fromXdrOperation(XdrLiquidityPoolWithdrawOperation $xdrOp): LiquidityPoolWithdrawOperation {
        $minAmountA = AbstractOperation::fromXdrAmount($xdrOp->getMinAmountA());
        $minAmountB = AbstractOperation::fromXdrAmount($xdrOp->getMinAmountB());
        $amount = AbstractOperation::fromXdrAmount($xdrOp->getAmount());
        $liquidityPoolId = $xdrOp->getLiquidityPoolID();
        return new LiquidityPoolWithdrawOperation($liquidityPoolId, $amount, $minAmountA, $minAmountB);
    }

    /**
     * Converts this operation to its XDR operation body representation.
     *
     * @return XdrOperationBody The XDR operation body
     */
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