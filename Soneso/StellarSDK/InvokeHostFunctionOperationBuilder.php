<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Exception;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;

/**
 * Builder for creating InvokeHostFunction operations.
 *
 * This builder implements the builder pattern to construct InvokeHostFunctionOperation
 * instances with a fluent interface. This operation invokes Soroban smart contract functions
 * on the Stellar network, supporting contract deployment, invocation, and asset operations.
 *
 * @package Soneso\StellarSDK
 * @see InvokeHostFunctionOperation
 * @see https://developers.stellar.org Stellar developer docs
 * @since 1.0.0
 *
 * @example
 * $operation = (new InvokeHostFunctionOperationBuilder($hostFunction, $authEntries))
 *     ->setSourceAccount($sourceId)
 *     ->build();
 */
class InvokeHostFunctionOperationBuilder
{
    /**
     * @var HostFunction The host function to invoke
     */
    public HostFunction $function;

    /**
     * @var array<SorobanAuthorizationEntry> The authorization entries for the invocation
     */
    public array $auth;

    /**
     * @var MuxedAccount|null The optional source account for this operation
     */
    public ?MuxedAccount $sourceAccount = null;

    /**
     * Creates a new InvokeHostFunction operation builder.
     *
     * @param HostFunction $function The host function to invoke
     * @param array<SorobanAuthorizationEntry> $auth The authorization entries for the invocation
     */
    public function __construct(HostFunction $function, array $auth = array())
    {
        $this->function = $function;
        $this->auth = $auth;
    }


    /**
     * Sets the source account for this operation.
     *
     * @param string $accountId The Stellar account ID (G...)
     * @return $this Returns the builder instance for method chaining
     */
    public function setSourceAccount(string $accountId) : InvokeHostFunctionOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    /**
     * Sets the muxed source account for this operation.
     *
     * @param MuxedAccount $sourceAccount The muxed account to use as source
     * @return $this Returns the builder instance for method chaining
     */
    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : InvokeHostFunctionOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    /**
     * Builds the InvokeHostFunction operation.
     *
     * @return InvokeHostFunctionOperation The constructed operation
     * @throws Exception If the host function type is unknown or not implemented
     */
    public function build(): InvokeHostFunctionOperation {
        return new InvokeHostFunctionOperation($this->function, $this->auth, $this->sourceAccount);
    }
}