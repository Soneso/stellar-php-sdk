<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrContractCostType
{
    public int $value;

    const WasmInsnExec = 0; // Cost of running 1 wasm instruction
    const MemAlloc = 1; // Cost of allocating a slice of memory (in bytes)
    const MemCpy = 2; // Cost of copying a slice of bytes into a pre-allocated memory
    const MemCmp = 3; // Cost of comparing two slices of memory
    const DispatchHostFunction = 4; // Cost of a host function dispatch, not including the actual work done by the function nor the cost of VM invocation machinary
    const VisitObject = 5; // Cost of visiting a host object from the host object storage. Exists to make sure some baseline cost coverage, i.e. repeatly visiting objects by the guest will always incur some charges.
    const ValSer = 6; // Cost of serializing an xdr object to bytes
    const ValDeser = 7; // Cost of deserializing an xdr object from bytes
    const ComputeSha256Hash = 8; // Cost of computing the sha256 hash from bytes
    const ComputeEd25519PubKey = 9; // Cost of computing the ed25519 pubkey from bytes
    const VerifyEd25519Sig = 10; // Cost of verifying ed25519 signature of a payload.
    const VmInstantiation = 11; // Cost of instantiation a VM from wasm bytes code.
    const VmCachedInstantiation = 12; // Cost of instantiation a VM from a cached state.
    const InvokeVMFunction = 13; // Cost of invoking a function on the VM. If the function is a host function, additional cost will be covered by `DispatchHostFunction`.
    const ComputeKeccak256Hash = 14; // Cost of computing a keccak256 hash from bytes.
    const DecodeEcdsaCurve256Sig = 15;  // Cost of decoding an ECDSA signature computed from a 256-bit prime modulus curve (e.g. secp256k1 and secp256r1).
    const RecoverEcdsaSecp256k1Key = 16; // Cost of recovering an ECDSA secp256k1 key from a signature.
    const Int256AddSub = 17; // Cost of int256 addition (`+`) and subtraction (`-`) operations
    const Int256Mul = 18; // Cost of int256 multiplication (`*`) operation
    const Int256Div = 19; // Cost of int256 division (`/`) operation
    const Int256Pow = 20; // Cost of int256 power (`exp`) operation
    const Int256Shift = 21; // Cost of int256 shift (`shl`, `shr`) operation
    const ChaCha20DrawBytes = 22; // Cost of drawing random bytes using a ChaCha20 PRNG
    const ParseWasmInstructions = 23; // Cost of parsing wasm bytes that only encode instructions.
    const ParseWasmFunctions = 24; // Cost of parsing a known number of wasm functions.
    const ParseWasmGlobals = 25; // Cost of parsing a known number of wasm globals.
    const ParseWasmTableEntries = 26; // Cost of parsing a known number of wasm table entries
    const ParseWasmTypes = 27; // Cost of parsing a known number of wasm types.
    const ParseWasmDataSegments = 28; // Cost of parsing a known number of wasm data segments.
    const ParseWasmElemSegments = 29; // Cost of parsing a known number of wasm element segments.
    const ParseWasmImports = 30; // Cost of parsing a known number of wasm imports.
    const ParseWasmExports = 31; // Cost of parsing a known number of wasm exports.
    const ParseWasmDataSegmentBytes = 32; // Cost of parsing a known number of data segment bytes.
    const InstantiateWasmInstructions = 33; // Cost of instantiating wasm bytes that only encode instructions.
    const InstantiateWasmFunctions = 34; // Cost of instantiating a known number of wasm functions.
    const InstantiateWasmGlobals = 35; // Cost of instantiating a known number of wasm globals.
    const InstantiateWasmTableEntries = 36;  // Cost of instantiating a known number of wasm table entries.
    const InstantiateWasmTypes = 37; // Cost of instantiating a known number of wasm types.
    const InstantiateWasmDataSegments = 38; // Cost of instantiating a known number of wasm data segments.
    const InstantiateWasmElemSegments = 39; // Cost of instantiating a known number of wasm element segments.
    const InstantiateWasmImports = 40; // Cost of instantiating a known number of wasm imports.
    const InstantiateWasmExports = 41; // Cost of instantiating a known number of wasm exports.
    const InstantiateWasmDataSegmentBytes = 42; // Cost of instantiating a known number of data segment bytes.
    const Sec1DecodePointUncompressed = 43; // Cost of decoding a bytes array representing an uncompressed SEC-1 encoded point on a 256-bit elliptic curve
    const VerifyEcdsaSecp256r1Sig = 44; // Cost of verifying an ECDSA Secp256r1 signature

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    /**
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }

    public function encode(): string
    {
        return XdrEncoder::integer32($this->value);
    }

    public function decode(XdrBuffer $xdr): XdrContractCostType
    {
        $value = $xdr->readInteger32();
        return new XdrContractCostType($value);
    }
}