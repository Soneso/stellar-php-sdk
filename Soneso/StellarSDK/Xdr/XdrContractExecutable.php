<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrContractExecutable extends XdrContractExecutableBase
{
    public function encode(): string {
        $bytes = $this->type->encode();
        switch ($this->type->getValue()) {
            case XdrContractExecutableType::CONTRACT_EXECUTABLE_WASM:
                $wasmIdBytes = pack("H*", $this->wasmIdHex);
                if (strlen($wasmIdBytes) > 32) {
                    $wasmIdBytes = substr($wasmIdBytes, -32);
                }
                $bytes .= XdrEncoder::opaqueFixed($wasmIdBytes, 32);
                break;
            case XdrContractExecutableType::CONTRACT_EXECUTABLE_STELLAR_ASSET:
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr): static {
        $result = new static(XdrContractExecutableType::decode($xdr));
        switch ($result->type->getValue()) {
            case XdrContractExecutableType::CONTRACT_EXECUTABLE_WASM:
                $result->wasmIdHex = bin2hex($xdr->readOpaqueFixed(32));
                break;
            case XdrContractExecutableType::CONTRACT_EXECUTABLE_STELLAR_ASSET:
                break;
        }
        return $result;
    }

    public static function forWasmId(string $wasmIdHex) : XdrContractExecutable {
        $result = new XdrContractExecutable(XdrContractExecutableType::CONTRACT_EXECUTABLE_WASM());
        $result->wasmIdHex = $wasmIdHex;
        return $result;
    }

    public static function forToken() : XdrContractExecutable {
        return new XdrContractExecutable(XdrContractExecutableType::CONTRACT_EXECUTABLE_STELLAR_ASSET());
    }
}
