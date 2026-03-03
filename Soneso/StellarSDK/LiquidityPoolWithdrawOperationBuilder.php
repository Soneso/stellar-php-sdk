<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

/**
 * Builder for creating LiquidityPoolWithdraw operations.
 *
 * This builder implements the builder pattern to construct LiquidityPoolWithdrawOperation
 * instances with a fluent interface. This operation withdraws assets from a liquidity pool
 * by redeeming pool shares for the underlying reserves.
 *
 * @package Soneso\StellarSDK
 * @see LiquidityPoolWithdrawOperation
 * @see https://developers.stellar.org Stellar developer docs
 * @since 1.0.0
 *
 * @example
 * $operation = (new LiquidityPoolWithdrawOperationBuilder($poolId, '50', '25', '25'))
 *     ->setSourceAccount($sourceId)
 *     ->build();
 */
class LiquidityPoolWithdrawOperationBuilder
{
    /**
     * @var MuxedAccount|null The optional source account for this operation
     */
    private ?MuxedAccount $sourceAccount = null;

    /**
     * Creates a new LiquidityPoolWithdraw operation builder.
     *
     * @param string $liquidityPoolId The liquidity pool ID
     * @param string $amount The amount of pool shares to withdraw
     * @param string $minAmountA The minimum amount of asset A to receive
     * @param string $minAmountB The minimum amount of asset B to receive
     */
    public function __construct(
        private string $liquidityPoolId,
        private string $amount,
        private string $minAmountA,
        private string $minAmountB,
    ) {
    }

    /**
     * Sets the source account for this operation.
     *
     * @param string $accountId The Stellar account ID (G...)
     * @return $this Returns the builder instance for method chaining
     */
    public function setSourceAccount(string $accountId) : LiquidityPoolWithdrawOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    /**
     * Sets the muxed source account for this operation.
     *
     * @param MuxedAccount $sourceAccount The muxed account to use as source
     * @return $this Returns the builder instance for method chaining
     */
    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : LiquidityPoolWithdrawOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    /**
     * Builds the LiquidityPoolWithdraw operation.
     *
     * @return LiquidityPoolWithdrawOperation The constructed operation
     */
    public function build(): LiquidityPoolWithdrawOperation {
        $result = new LiquidityPoolWithdrawOperation($this->liquidityPoolId, $this->amount, $this->minAmountA, $this->minAmountB);
        if ($this->sourceAccount !== null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}