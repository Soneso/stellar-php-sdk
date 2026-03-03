<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Contract;

use Psr\Log\LoggerInterface;
use Soneso\StellarSDK\Xdr\XdrSCVal;

/**
 * Configuration options for constructing and managing AssembledTransaction instances
 *
 * This class encapsulates all configuration needed to build, simulate, and send Soroban
 * smart contract transactions. It combines client configuration, method-specific options,
 * and transaction parameters into a single object for convenient transaction construction.
 *
 * @package Soneso\StellarSDK\Soroban\Contract
 * @see AssembledTransaction For the transaction class that uses these options
 * @see ClientOptions For client-level configuration
 * @see MethodOptions For method invocation settings
 * @since 1.0.0
 */
class AssembledTransactionOptions
{
    /**
     * @param ClientOptions $clientOptions Client options.
     * @param MethodOptions $methodOptions Method options.
     * @param string $method The name of the contract method to call.
     * @param array<XdrSCVal>|null $arguments Arguments to pass to the method call.
     * @param LoggerInterface|null $logger PSR-3 logger for debug output. Default: null (no logging).
     */
    public function __construct(
        public ClientOptions $clientOptions,
        public MethodOptions $methodOptions,
        public string $method,
        public ?array $arguments = null,
        public ?LoggerInterface $logger = null,
    ) {
    }

}