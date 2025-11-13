<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

/**
 * Builder for creating LiquidityPoolDeposit operations.
 *
 * This builder implements the builder pattern to construct LiquidityPoolDepositOperation
 * instances with a fluent interface. This operation deposits assets into a liquidity pool,
 * receiving pool shares in return proportional to the contribution.
 *
 * @package Soneso\StellarSDK
 * @see LiquidityPoolDepositOperation
 * @see https://developers.stellar.org Stellar developer docs
 * @since 1.0.0
 *
 * @example
 * $operation = (new LiquidityPoolDepositOperationBuilder($poolId, '100', '200', $minPrice, $maxPrice))
 *     ->setSourceAccount($sourceId)
 *     ->build();
 */
class LiquidityPoolDepositOperationBuilder
{
    /**
     * @var string The liquidity pool ID
     */
    private string $liquidityPoolId;

    /**
     * @var string The maximum amount of asset A to deposit
     */
    private string $maxAmountA;

    /**
     * @var string The maximum amount of asset B to deposit
     */
    private string $maxAmountB;

    /**
     * @var Price The minimum price for the deposit
     */
    private Price $minPrice;

    /**
     * @var Price The maximum price for the deposit
     */
    private Price $maxPrice;

    /**
     * @var MuxedAccount|null The optional source account for this operation
     */
    private ?MuxedAccount $sourceAccount = null;

    /**
     * Creates a new LiquidityPoolDeposit operation builder.
     *
     * @param string $liquidityPoolId The liquidity pool ID
     * @param string $maxAmountA The maximum amount of asset A to deposit
     * @param string $maxAmountB The maximum amount of asset B to deposit
     * @param Price $minPrice The minimum price for the deposit
     * @param Price $maxPrice The maximum price for the deposit
     */
    public function __construct(string $liquidityPoolId, string $maxAmountA, string $maxAmountB, Price $minPrice, Price $maxPrice)
    {
        $this->liquidityPoolId = $liquidityPoolId;
        $this->maxAmountA = $maxAmountA;
        $this->maxAmountB = $maxAmountB;
        $this->minPrice = $minPrice;
        $this->maxPrice = $maxPrice;
    }

    /**
     * Sets the source account for this operation.
     *
     * @param string $accountId The Stellar account ID (G...)
     * @return $this Returns the builder instance for method chaining
     */
    public function setSourceAccount(string $accountId) : LiquidityPoolDepositOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    /**
     * Sets the muxed source account for this operation.
     *
     * @param MuxedAccount $sourceAccount The muxed account to use as source
     * @return $this Returns the builder instance for method chaining
     */
    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : LiquidityPoolDepositOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    /**
     * Builds the LiquidityPoolDeposit operation.
     *
     * @return LiquidityPoolDepositOperation The constructed operation
     */
    public function build(): LiquidityPoolDepositOperation {
        $result = new LiquidityPoolDepositOperation($this->liquidityPoolId, $this->maxAmountA, $this->maxAmountB, $this->minPrice, $this->maxPrice);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}