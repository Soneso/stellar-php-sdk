<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCContractCode
{

    public XdrSCContractCodeType $type;
    public ?string $wasmIdHex = null;

    /**
     * @param XdrSCContractCodeType $type
     */
    public function __construct(XdrSCContractCodeType $type)
    {
        $this->type = $type;
    }

    public static function forWasmId(string $wasmIdHex) : XdrSCContractCode {
        $result = new XdrSCContractCode(XdrSCContractCodeType::WASM_REF());
        $result->wasmIdHex = $wasmIdHex;
        return $result;
    }

    public static function forToken() : XdrSCContractCode {
        return new XdrSCContractCode(XdrSCContractCodeType::TOKEN());
    }

    public function encode(): string {
        $bytes = $this->type->encode();

        switch ($this->type->value) {
            case XdrSCContractCodeType::SCCONTRACT_CODE_WASM_REF:
                $wasmIdBytes = pack("H*", $this->wasmIdHex);
                if (strlen($wasmIdBytes) > 32) {
                    $wasmIdBytes = substr($wasmIdBytes, -32);
                }
                $bytes .= XdrEncoder::opaqueFixed($wasmIdBytes, 32);
                break;
            case XdrSCContractCodeType::SCCONTRACT_CODE_TOKEN:
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCContractCode {
        $result = new XdrSCContractCode(XdrSCContractCodeType::decode($xdr));
        switch ($result->getType()->getValue()) {
            case XdrSCContractCodeType::SCCONTRACT_CODE_WASM_REF:
                $result->wasmIdHex = bin2hex($xdr->readOpaqueFixed(32));
                break;
            case XdrSCContractCodeType::SCCONTRACT_CODE_TOKEN:
                break;
        }
        return $result;
    }

    /**
     * @return XdrSCContractCode|XdrSCContractCodeType
     */
    public function getType(): XdrSCContractCode|XdrSCContractCodeType
    {
        return $this->type;
    }

    /**
     * @param XdrSCContractCode|XdrSCContractCodeType $type
     */
    public function setType(XdrSCContractCode|XdrSCContractCodeType $type): void
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