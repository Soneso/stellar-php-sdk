<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Exception;
use Soneso\StellarSDK\Xdr\XdrHostFunction;
use Soneso\StellarSDK\Xdr\XdrHostFunctionType;

/**
 * Represents a Soroban host function for uploading contract WASM code
 *
 * This host function uploads WebAssembly (WASM) bytecode to the Stellar network,
 * making it available for contract deployment. The uploaded WASM is stored on the
 * ledger and can be referenced by its hash (WASM ID) when deploying contract instances.
 *
 * The upload process:
 * 1. Upload WASM code using this host function
 * 2. Receive WASM ID (hash) from the transaction result
 * 3. Use WASM ID to deploy contract instances via CreateContractHostFunction
 *
 * Usage:
 * <code>
 * // Read WASM file
 * $wasmBytes = file_get_contents('contract.wasm');
 *
 * // Create upload host function
 * $hostFunction = new UploadContractWasmHostFunction($wasmBytes);
 *
 * // Use in an InvokeHostFunctionOperation
 * $operation = (new InvokeHostFunctionOperationBuilder($hostFunction))->build();
 * </code>
 *
 * @package Soneso\StellarSDK
 * @see HostFunction Base class for all host functions
 * @see CreateContractHostFunction For deploying contracts from uploaded WASM
 * @see https://developers.stellar.org Stellar developer docs
 * @since 1.0.0
 */
class UploadContractWasmHostFunction extends HostFunction
{
    /**
     * @var string|null $contractCodeBytes The WASM bytecode as a binary string
     */
    public ?string $contractCodeBytes = null;

    /**
     * Constructs a new UploadContractWasmHostFunction
     *
     * @param string|null $contractCodeBytes The WASM bytecode as a binary string
     */
    public function __construct(?string $contractCodeBytes)
    {
        $this->contractCodeBytes = $contractCodeBytes;
        parent::__construct();
    }

    /**
     * Converts the upload WASM host function to XDR format
     *
     * @return XdrHostFunction The XDR host function
     */
    public function toXdr() : XdrHostFunction {
        return XdrHostFunction::forUploadContractWasm($this->contractCodeBytes);
    }

    /**
     * Creates an UploadContractWasmHostFunction from XDR format
     *
     * @param XdrHostFunction $xdr The XDR host function
     * @return UploadContractWasmHostFunction The decoded host function
     * @throws Exception If the XDR format is invalid or missing WASM data
     */
    public static function fromXdr(XdrHostFunction $xdr) : UploadContractWasmHostFunction {
        $type = $xdr->type;
        if ($type->value != XdrHostFunctionType::HOST_FUNCTION_TYPE_UPLOAD_CONTRACT_WASM || $xdr->wasm == null) {
            throw new Exception("Invalid argument");
        }
        $contractCode = $xdr->wasm->getValue();
        return new UploadContractWasmHostFunction($contractCode);
    }

    /**
     * Gets the contract WASM bytecode
     *
     * @return string|null The WASM bytecode as a binary string
     */
    public function getContractCodeBytes(): ?string
    {
        return $this->contractCodeBytes;
    }

    /**
     * Sets the contract WASM bytecode
     *
     * @param string|null $contractCodeBytes The WASM bytecode as a binary string
     * @return void
     */
    public function setContractCodeBytes(?string $contractCodeBytes): void
    {
        $this->contractCodeBytes = $contractCodeBytes;
    }

}