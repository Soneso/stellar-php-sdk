<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Contract;

use Psr\Log\LoggerInterface;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Xdr\XdrSCVal;

/**
 * Request parameters for deploying a Soroban smart contract instance from a CAP-85
 * external reference
 *
 * The executable is not a wasm hash: it names an owner contract and a tag, and the
 * owner holds a persistent contract data entry under that tag whose value is the
 * 32-byte hash of an already uploaded wasm. The created instance runs that code.
 * Nothing is installed as part of the deployment; the owner contract already holds
 * the tag entry.
 *
 * @package Soneso\StellarSDK\Soroban\Contract
 * @see SorobanClient::deployFromExternalRef() For the deployment method that uses this request
 * @see DeployRequest For deploying from an installed wasm hash
 * @since 1.13.0
 */
class DeployFromExternalRefRequest
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
     * @var Address $executableOwner The contract holding the executable tag entry the new instance runs.
     */
    public Address $executableOwner;

    /**
     * @var string $tag The tag the owner holds the executable entry under; matched byte for byte.
     */
    public string $tag;

    /**
     * @var array<XdrSCVal>|null $constructorArgs Constructor/Initialization Args for the contract's `__constructor` method.
     */
    public ?array $constructorArgs;

    /**
     * @var string|null $salt Salt used to generate the contract's ID. 32 bytes that
     * influence the deterministic contract address. Using the same executable reference and salt
     * will produce the same contract ID. Default: random (generates a unique contract ID for
     * each deployment).
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
     * @var SorobanServer|null $server RPC server instance to use. When null, one is created
     * automatically from $rpcUrl. Provide a preconfigured instance to customize the underlying
     * HTTP client.
     */
    public ?SorobanServer $server = null;

    /**
     * Constructor.
     *
     * @param string $rpcUrl The URL of the RPC instance that will be used to deploy the contract.
     * @param Network $network The Stellar network this contract is to be deployed.
     * @param KeyPair $sourceAccountKeyPair Keypair of the Stellar account that will send this transaction.
     * The keypair must contain the private key for signing.
     * @param Address $executableOwner The contract holding the executable tag entry the new instance runs.
     * @param string $tag The tag the owner holds the executable entry under; matched byte for byte.
     * @param array<XdrSCVal>|null $constructorArgs Constructor/Initialization Args for the contract's `__constructor` method.
     * @param string|null $salt Salt used to generate the contract's ID. Default: random.
     * @param MethodOptions|null $methodOptions method options used to fine tune the transaction. Default: new MethodOptions()
     * @param LoggerInterface|null $logger PSR-3 logger for debug output. Default: null (no logging).
     * @param SorobanServer|null $server RPC server instance to use. When null, one is created
     * automatically from $rpcUrl.
     */
    public function __construct(string $rpcUrl,
                                Network $network,
                                KeyPair $sourceAccountKeyPair,
                                Address $executableOwner,
                                string $tag,
                                ?array $constructorArgs = null,
                                ?string $salt = null,
                                ?MethodOptions $methodOptions = null,
                                ?LoggerInterface $logger = null,
                                ?SorobanServer $server = null)
    {
        $this->sourceAccountKeyPair = $sourceAccountKeyPair;
        $this->network = $network;
        $this->rpcUrl = $rpcUrl;
        $this->methodOptions = $methodOptions ?? new MethodOptions();
        $this->executableOwner = $executableOwner;
        $this->tag = $tag;
        $this->constructorArgs = $constructorArgs;
        $this->salt = $salt;
        $this->logger = $logger;
        $this->server = $server;
    }

}
