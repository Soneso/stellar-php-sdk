<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrContractCostType
{
    public int $value;

    const WasmInsnExec = 0; // Cost of running 1 wasm instruction
    const WasmMemAlloc = 1; // Cost of growing wasm linear memory by 1 page
    const HostMemAlloc = 2; // Cost of allocating a chuck of host memory (in bytes)
    const HostMemCpy = 3; // Cost of copying a chuck of bytes into a pre-allocated host memory
    const HostMemCmp = 4; // Cost of comparing two slices of host memory
    const DispatchHostFunction = 5; // Cost of a host function dispatch, not including the actual work done by the function nor the cost of VM invocation machinary
    const VisitObject = 6; // Cost of visiting a host object from the host object storage. Exists to make sure some baseline cost coverage, i.e. repeatly visiting objects by the guest will always incur some charges.
    const ValSer = 7; // Cost of serializing an xdr object to bytes
    const ValDeser = 8; // Cost of deserializing an xdr object from bytes
    const ComputeSha256Hash = 9; // Cost of computing the sha256 hash from bytes
    const ComputeEd25519PubKey = 10; // Cost of computing the ed25519 pubkey from bytes
    const MapEntry = 11; // Cost of accessing an entry in a Map.
    const VecEntry = 12; // Cost of accessing an entry in a Vec.
    const VerifyEd25519Sig = 13; // Cost of verifying ed25519 signature of a payload.
    const VmMemRead = 14; // Cost of reading a slice of vm linear memory.
    const VmMemWrite = 15; // Cost of writing to a slice of vm linear memory.
    const VmInstantiation = 16; // Cost of instantiation a VM from wasm bytes code.
    const VmCachedInstantiation = 17;
    const InvokeVMFunction = 18;
    const ComputeKeccak256Hash = 19;
    const ComputeEcdsaSecp256k1Key = 20;
    const ComputeEcdsaSecp256k1Sig = 21;
    const RecoverEcdsaSecp256k1Key = 22;
    const Int256AddSub = 23;
    const Int256Mul = 24;
    const Int256Div = 25;
    const Int256Pow = 26;
    const Int256Shift = 27;

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