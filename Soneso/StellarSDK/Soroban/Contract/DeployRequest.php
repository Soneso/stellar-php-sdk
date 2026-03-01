<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Contract;

use Psr\Log\LoggerInterface;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Xdr\XdrSCVal;

/**
 * Request parameters for deploying a Soroban smart contract instance
 *
 * This class encapsulates all parameters needed to deploy a new contract instance from
 * previously installed WASM code. The deployment process creates a contract with a unique
 * ID and optionally calls the contract's constructor with initialization arguments.
 *
 * The WASM code must be installed on the network first using InstallRequest.
 *
 * @package Soneso\StellarSDK\Soroban\Contract
 * @see SorobanClient::deploy() For the deployment method that uses this request
 * @see InstallRequest For installing contract WASM code
 * @see https://developers.stellar.org/docs/smart-contracts/getting-started/deploy-to-testnet
 * @since 1.0.0
 */
class DeployRequest
{

    /**
     * @var KeyPair $sourceAccountKeyPair Keypair of the Stellar account that will send this transaction.
     * The keypair must contain the private key for signing.
     */
    public KeyPair $sourceAccountKeyPair;

    /**
     * @var Network $network The Stellar network this contract is to be deployed
     *  to
     */
    public Network $network;

    /**
     * @var string $rpcUrl The URL of the RPC instance that will be used to deploy the contract.
     */
    public string $rpcUrl;


    /**
     * @var string $wasmHash The hash of the Wasm blob (in hex string format), which must already be installed on-chain.
     */
    public string $wasmHash;

    /**
     * @var array<XdrSCVal>|null $constructorArgs Constructor/Initialization Args for the contract's `__constructor` method.
     */
    public ?array $constructorArgs;

    /**
     * @var string|null $salt Salt used to generate the contract's ID. A 32-byte hex string that
     * influences the deterministic contract address. Using the same WASM hash and salt will produce
     * the same contract ID. Default: random (generates a unique contract ID for each deployment).
     */
    public ?string $salt;

    /**
     * @var MethodOptions method options used to fine tune the transaction.
     */
    public MethodOptions $methodOptions;

    /**
     * @var LoggerInterface|null $logger PSR-3 logger for debug output. Default: null (no logging).
     */
    public ?LoggerInterface $logger = null;

    /**
     * Constructor.
     *
     * @param string $rpcUrl The URL of the RPC instance that will be used to deploy the contract.
     * @param Network $network The Stellar network this contract is to be deployed.
     * @param KeyPair $sourceAccountKeyPair Keypair of the Stellar account that will send this transaction.
     * The keypair must contain the private key for signing.
     * @param string $wasmHash The hash of the Wasm blob (in hex string format), which must already be installed on-chain.
     * @param array<XdrSCVal>|null $constructorArgs Constructor/Initialization Args for the contract's `__constructor` method.
     * @param string|null $salt Salt used to generate the contract's ID. Default: random.
     * @param MethodOptions|null $methodOptions method options used to fine tune the transaction. Default: new MethodOptions()
     * @param LoggerInterface|null $logger PSR-3 logger for debug output. Default: null (no logging).
     */
    public function __construct(string $rpcUrl,
                                Network $network,
                                KeyPair $sourceAccountKeyPair,
                                string $wasmHash,
                                ?array $constructorArgs = null,
                                ?string $salt = null,
                                ?MethodOptions $methodOptions = null,
                                ?LoggerInterface $logger = null)
    {
        $this->sourceAccountKeyPair = $sourceAccountKeyPair;
        $this->network = $network;
        $this->rpcUrl = $rpcUrl;
        $this->methodOptions = $methodOptions ?? new MethodOptions();
        $this->wasmHash = $wasmHash;
        $this->constructorArgs = $constructorArgs;
        $this->salt = $salt;
        $this->logger = $logger;
    }

}