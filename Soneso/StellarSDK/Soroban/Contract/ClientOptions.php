<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Contract;

use Psr\Log\LoggerInterface;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;

/**
 * Client configuration options for Soroban smart contract interactions
 *
 * This class defines the core configuration required for a SorobanClient to interact with
 * smart contracts on the Stellar network. It specifies the source account, target contract,
 * network parameters, and RPC endpoint.
 *
 * @package Soneso\StellarSDK\Soroban\Contract
 * @see SorobanClient For the client that uses these options
 * @see AssembledTransactionOptions For transaction-specific configuration
 * @since 1.0.0
 */
class ClientOptions
{
    /**
     * @param KeyPair $sourceAccountKeyPair Keypair of the Stellar account that will send transactions.
     *                                      For read-only operations, only the public key is required. For write operations
     *                                      and automatic restore, the private key (secret seed) must be included for transaction signing.
     * @param string $contractId The address of the contract the client will interact with.
     * @param Network $network The Stellar network this contract is deployed to.
     * @param string $rpcUrl The URL of the RPC instance that will be used to interact with this contract.
     * @param LoggerInterface|null $logger PSR-3 logger for debug output. Default: null (no logging).
     *
     * @see MethodOptions::$restore For automatic restore configuration
     */
    public function __construct(
        public KeyPair $sourceAccountKeyPair,
        public string $contractId,
        public Network $network,
        public string $rpcUrl,
        public ?LoggerInterface $logger = null,
    ) {
    }
}