<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Exception;
use Soneso\StellarSDK\Xdr\XdrContractIDType;
use Soneso\StellarSDK\Xdr\XdrHostFunction;
use Soneso\StellarSDK\Xdr\XdrHostFunctionArgs;
use Soneso\StellarSDK\Xdr\XdrHostFunctionType;
use Soneso\StellarSDK\Xdr\XdrSCContractExecutableType;

class CreateContractHostFunction extends HostFunction
{
    public string $wasmId;
    public string $salt;

    /**
     * @param string $wasmId
     * @param string $salt
     */
    public function __construct(string $wasmId, ?string $salt = null, ?array $auth = array())
    {
        $this->wasmId = $wasmId;
        $this->salt = $salt != null ? $salt : random_bytes(32);
        parent::__construct($auth);
    }

    public function toXdr() : XdrHostFunction {
        $args = XdrHostFunctionArgs::forCreatingContract($this->wasmId, $this->salt);
        return new XdrHostFunction($args, self::convertToXdrAuth($this->auth));
    }

    /**
     * @throws Exception
     */
    public static function fromXdr(XdrHostFunction $xdr) : CreateContractHostFunction {
        $args = $xdr->args;
        $type = $args->type;
        if ($type->value != XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT || $args->createContract == null
            || $args->createContract->contractID->type->value != XdrContractIDType::CONTRACT_ID_FROM_SOURCE_ACCOUNT
            || $args->createContract->executable->type->value != XdrSCContractExecutableType::SCCONTRACT_EXECUTABLE_WASM_REF) {
            throw new Exception("Invalid argument");
        }
        $wasmId = $args->createContract->executable->wasmIdHex;

        if ($wasmId == null) {
            throw new Exception("invalid argument");
        }
        return new CreateContractHostFunction($wasmId, $args->createContract->contractID->salt, self::convertFromXdrAuth($xdr->auth));
    }

    /**
     * @return string
     */
    public function getWasmId(): string
    {
        return $this->wasmId;
    }

    /**
     * @param string $wasmId
     */
    public function setWasmId(string $wasmId): void
    {
        $this->wasmId = $wasmId;
    }

    /**
     * @return string
     */
    public function getSalt(): string
    {
        return $this->salt;
    }

    /**
     * @param string $salt
     */
    public function setSalt(string $salt): void
    {
        $this->salt = $salt;
    }

}