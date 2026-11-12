<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use InvalidArgumentException;
use Soneso\StellarSDK\Soroban\SecretSeedRedaction;

class XdrContractExecutable extends XdrContractExecutableBase
{
    /**
     * Returns the wasm hash as 64 lower case hexadecimal characters, as
     * XdrContractExecutableBase::getCanonicalWasmIdHex() does. A field that starts with "S"
     * or "s", the first letter of a secret seed, is not hexadecimal, and the error names it
     * by its kind without showing it.
     *
     * @return string the wasm hash as 64 lower case hexadecimal characters
     * @throws InvalidArgumentException when the field is unset, or holds a string that is
     * not 64 hexadecimal characters
     */
    public function getCanonicalWasmIdHex(): string {
        $kind = SecretSeedRedaction::seedKind($this->wasmIdHex ?? '');
        if ($kind !== null) {
            throw new InvalidArgumentException('Wasm hash must be a hexadecimal string, ' . $kind . ' given');
        }
        return parent::getCanonicalWasmIdHex();
    }

    public static function forWasmId(string $wasmIdHex) : XdrContractExecutable {
        $result = new XdrContractExecutable(XdrContractExecutableType::CONTRACT_EXECUTABLE_WASM());
        $result->wasmIdHex = $wasmIdHex;
        return $result;
    }

    public static function forToken() : XdrContractExecutable {
        return new XdrContractExecutable(XdrContractExecutableType::CONTRACT_EXECUTABLE_STELLAR_ASSET());
    }

    public static function forExternalRef(XdrSCAddress $executableOwner, string $tag) : XdrContractExecutable {
        $result = new XdrContractExecutable(XdrContractExecutableType::CONTRACT_EXECUTABLE_EXTERNAL_REF());
        $result->externalRef = new XdrContractExecutableExternalRef($executableOwner, $tag);
        return $result;
    }
}
