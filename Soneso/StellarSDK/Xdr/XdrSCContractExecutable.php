<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCContractExecutable
{

    public XdrSCContractExecutableType $type;
    public ?string $wasmIdHex = null;

    /**
     * @param XdrSCContractExecutableType $type
     */
    public function __construct(XdrSCContractExecutableType $type)
    {
        $this->type = $type;
    }

    public static function forWasmId(string $wasmIdHex) : XdrSCContractExecutable {
        $result = new XdrSCContractExecutable(XdrSCContractExecutableType::WASM_REF());
        $result->wasmIdHex = $wasmIdHex;
        return $result;
    }

    public static function forToken() : XdrSCContractExecutable {
        return new XdrSCContractExecutable(XdrSCContractExecutableType::TOKEN());
    }

    public function encode(): string {
        $bytes = $this->type->encode();

        switch ($this->type->value) {
            case XdrSCContractExecutableType::SCCONTRACT_EXECUTABLE_WASM_REF:
                $wasmIdBytes = pack("H*", $this->wasmIdHex);
                if (strlen($wasmIdBytes) > 32) {
                    $wasmIdBytes = substr($wasmIdBytes, -32);
                }
                $bytes .= XdrEncoder::opaqueFixed($wasmIdBytes, 32);
                break;
            case XdrSCContractExecutableType::SCCONTRACT_EXECUTABLE_TOKEN:
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCContractExecutable {
        $result = new XdrSCContractExecutable(XdrSCContractExecutableType::decode($xdr));
        switch ($result->getType()->getValue()) {
            case XdrSCContractExecutableType::SCCONTRACT_EXECUTABLE_WASM_REF:
                $result->wasmIdHex = bin2hex($xdr->readOpaqueFixed(32));
                break;
            case XdrSCContractExecutableType::SCCONTRACT_EXECUTABLE_TOKEN:
                break;
        }
        return $result;
    }

    /**
     * @return XdrSCContractExecutable|XdrSCContractExecutableType
     */
    public function getType(): XdrSCContractExecutable|XdrSCContractExecutableType
    {
        return $this->type;
    }

    /**
     * @param XdrSCContractExecutable|XdrSCContractExecutableType $type
     */
    public function setType(XdrSCContractExecutable|XdrSCContractExecutableType $type): void
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