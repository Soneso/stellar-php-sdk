<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrHostFunction;

/**
 * Abstract base class for Soroban smart contract host functions
 *
 * Host functions enable interaction with Soroban smart contracts on the Stellar network.
 * This abstract class defines the interface for all host function types including
 * contract invocation, deployment, and WASM upload operations.
 *
 * Host Function Types:
 * - InvokeContractHostFunction: Call smart contract functions
 * - CreateContractHostFunction: Deploy contracts from WASM hash
 * - CreateContractWithConstructorHostFunction: Deploy contracts with constructor arguments
 * - UploadContractWasmHostFunction: Upload contract WASM code
 * - DeploySACWithAssetHostFunction: Deploy Stellar Asset Contracts for assets
 * - DeploySACWithSourceAccountHostFunction: Deploy Stellar Asset Contracts with source account
 *
 * Usage:
 * <code>
 * // Invoke a contract function
 * $hostFunction = new InvokeContractHostFunction($contractId, $functionName, $args);
 *
 * // Upload WASM code
 * $hostFunction = new UploadContractWasmHostFunction($wasmBytes);
 *
 * // Deploy a contract
 * $hostFunction = new CreateContractHostFunction($wasmId, $address);
 * </code>
 *
 * @package Soneso\StellarSDK
 * @see InvokeContractHostFunction For calling contract functions
 * @see CreateContractHostFunction For deploying contracts
 * @see UploadContractWasmHostFunction For uploading WASM code
 * @see https://developers.stellar.org Stellar developer docs
 * @since 1.0.0
 */
abstract class HostFunction
{

    /**
     * Constructs a new HostFunction instance
     */
    public function __construct()
    {
    }

    /**
     * Converts the host function to XDR format
     *
     * @return XdrHostFunction The XDR representation
     */
    abstract public function toXdr() : XdrHostFunction;

    /**
     * Creates a HostFunction from XDR format
     *
     * @param XdrHostFunction $xdr The XDR host function
     * @return HostFunction The decoded host function instance
     */
    abstract public static function fromXdr(XdrHostFunction $xdr) : HostFunction;

}