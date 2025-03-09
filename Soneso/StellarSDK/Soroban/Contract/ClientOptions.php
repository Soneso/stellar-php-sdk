<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Contract;

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;

class ClientOptions
{

    /**
     * @var KeyPair $sourceAccountKeyPair Keypair of the Stellar account that will send this transaction. If restore is set to true,
     * and restore is needed, the keypair must contain the private key (secret seed) otherwise the public key is sufficient.
     */
    public KeyPair $sourceAccountKeyPair;

    /**
     * @var string $contractId The address of the contract the client will interact with.
     */
    public string $contractId;

    /**
     * @var Network $network The Stellar network this contract is deployed
     *  to
     */
    public Network $network;

    /**
     * @var string $rpcUrl The URL of the RPC instance that will be used to interact with this
     *  contract.
     */
    public string $rpcUrl;

    /**
     * @var bool $enableServerLogging enable soroban server logging (helpful for debugging). Default: false.
     */
    public bool $enableServerLogging = false;

    /**
     * @param KeyPair $sourceAccountKeyPair Keypair of the Stellar account that will send this transaction. If restore is set to true,
     *  and restore is needed, the keypair must contain the private key (secret seed) otherwise the public key is sufficient.
     * @param string $contractId The address of the contract the client will interact with.
     * @param Network $network The Stellar network this contract is deployed
     * @param string $rpcUrl The URL of the RPC instance that will be used to interact with this contract.
     * @param bool $enableServerLogging enable soroban server logging (helpful for debugging). Default: false.
     */
    public function __construct(KeyPair $sourceAccountKeyPair,
                                string $contractId,
                                Network $network,
                                string $rpcUrl,
                                bool $enableServerLogging = false)
    {
        $this->sourceAccountKeyPair = $sourceAccountKeyPair;
        $this->contractId = $contractId;
        $this->network = $network;
        $this->rpcUrl = $rpcUrl;
        $this->enableServerLogging = $enableServerLogging;
    }
}