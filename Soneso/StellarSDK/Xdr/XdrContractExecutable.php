<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrContractExecutable extends XdrContractExecutableBase
{

    public static function forWasmId(string $wasmIdHex) : XdrContractExecutable {
        $result = new XdrContractExecutable(XdrContractExecutableType::CONTRACT_EXECUTABLE_WASM());
        $result->wasmIdHex = $wasmIdHex;
        return $result;
    }

    public static function forToken() : XdrContractExecutable {
        return new XdrContractExecutable(XdrContractExecutableType::CONTRACT_EXECUTABLE_STELLAR_ASSET());
    }

}
