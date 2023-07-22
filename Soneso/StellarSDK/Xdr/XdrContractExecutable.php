<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrContractExecutable
{

    public XdrContractExecutableType $type;
    public ?string $wasmIdHex = null;

    /**
     * @param XdrContractExecutableType $type
     */
    public function __construct(XdrContractExecutableType $type)
    {
        $this->type = $type;
    }

    public static function forWasmId(string $wasmIdHex) : XdrContractExecutable {
        $result = new XdrContractExecutable(XdrContractExecutableType::CONTRACT_EXECUTABLE_WASM());
        $result->wasmIdHex = $wasmIdHex;
        return $result;
    }

    public static function forToken() : XdrContractExecutable {
        return new XdrContractExecutable(XdrContractExecutableType::CONTRACT_EXECUTABLE_TOKEN());
    }

    public function encode(): string {
        $bytes = $this->type->encode();

        switch ($this->type->value) {
            case XdrContractExecutableType::CONTRACT_EXECUTABLE_WASM:
                $wasmIdBytes = pack("H*", $this->wasmIdHex);
                if (strlen($wasmIdBytes) > 32) {
                    $wasmIdBytes = substr($wasmIdBytes, -32);
                }
                $bytes .= XdrEncoder::opaqueFixed($wasmIdBytes, 32);
                break;
            case XdrContractExecutableType::CONTRACT_EXECUTABLE_TOKEN:
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrContractExecutable {
        $result = new XdrContractExecutable(XdrContractExecutableType::decode($xdr));
        switch ($result->getType()->getValue()) {
            case XdrContractExecutableType::CONTRACT_EXECUTABLE_WASM:
                $result->wasmIdHex = bin2hex($xdr->readOpaqueFixed(32));
                break;
            case XdrContractExecutableType::CONTRACT_EXECUTABLE_TOKEN:
                break;
        }
        return $result;
    }

    /**
     * @return XdrContractExecutable|XdrContractExecutableType
     */
    public function getType(): XdrContractExecutable|XdrContractExecutableType
    {
        return $this->type;
    }

    /**
     * @param XdrContractExecutable|XdrContractExecutableType $type
     */
    public function setType(XdrContractExecutable|XdrContractExecutableType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string|null
     */
    public function getWasmIdHex(): ?string
    {
        return $this->wasmIdHex;
    }

    /**
     * @param string|null $wasmIdHex
     */
    public function setWasmIdHex(?string $wasmIdHex): void
    {
        $this->wasmIdHex = $wasmIdHex;
    }

}