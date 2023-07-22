<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Exception;
use Soneso\StellarSDK\Xdr\XdrHostFunction;
use Soneso\StellarSDK\Xdr\XdrHostFunctionType;

class UploadContractWasmHostFunction extends HostFunction
{
    public ?string $contractCodeBytes = null;// Uint8List

    /**
     * @param string|null $contractCodeBytes
     */
    public function __construct(?string $contractCodeBytes)
    {
        $this->contractCodeBytes = $contractCodeBytes;
        parent::__construct();
    }

    public function toXdr() : XdrHostFunction {
        return XdrHostFunction::forUploadContractWasm($this->contractCodeBytes);
    }

    /**
     * @throws Exception
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
     * @return string|null
     */
    public function getContractCodeBytes(): ?string
    {
        return $this->contractCodeBytes;
    }

    /**
     * @param string|null $contractCodeBytes
     */
    public function setContractCodeBytes(?string $contractCodeBytes): void
    {
        $this->contractCodeBytes = $contractCodeBytes;
    }

}