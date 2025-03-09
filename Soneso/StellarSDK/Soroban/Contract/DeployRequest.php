<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Contract;

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Xdr\XdrSCVal;

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
     * @var string|null $salt Salt used to generate the contract's ID. Default: random.
     */
    public ?string $salt;

    /**
     * @var MethodOptions method options used to fine tune the transaction.
     */
    public MethodOptions $methodOptions;

    /**
     * @var bool $enableServerLogging enable soroban server logging (helpful for debugging). Default: false.
     */
    public bool $enableServerLogging = false;

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
     * @param bool $enableServerLogging enable soroban server logging (helpful for debugging). Default: false.
     */
    public function __construct(string $rpcUrl,
                                Network $network,
                                KeyPair $sourceAccountKeyPair,
                                string $wasmHash,
                                ?array $constructorArgs = null,
                                ?string $salt = null,
                                ?MethodOptions $methodOptions = null,
                                bool $enableServerLogging = false)
    {
        $this->sourceAccountKeyPair = $sourceAccountKeyPair;
        $this->network = $network;
        $this->rpcUrl = $rpcUrl;
        $this->methodOptions = $methodOptions ?? new MethodOptions();
        $this->wasmHash = $wasmHash;
        $this->constructorArgs = $constructorArgs;
        $this->salt = $salt;
        $this->enableServerLogging = $enableServerLogging;
    }

}