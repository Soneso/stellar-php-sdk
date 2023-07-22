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
    const InvokeHostFunction = 5; // Cost of a host function invocation, not including the actual work done by the function
    const VisitObject = 6; // Cost of visiting a host object from the host object storage. Only thing to make sure is the guest can't visitObject repeatly without incurring some charges elsewhere.
    const ValXdrConv = 7; // Tracks a single Val (RawVal or primitive Object like U64) <=> ScVal conversion cost. Most of these Val counterparts in ScVal (except e.g. Symbol) consumes a single int64 and therefore is a constant overhead.
    const ValSer = 8; // Cost of serializing an xdr object to bytes
    const ValDeser = 9; // Cost of deserializing an xdr object from bytes
    const ComputeSha256Hash = 10; // Cost of computing the sha256 hash from bytes
    const ComputeEd25519PubKey = 11; // Cost of computing the ed25519 pubkey from bytes
    const MapEntry = 12; // Cost of accessing an entry in a Map.
    const VecEntry = 13; // Cost of accessing an entry in a Vec.
    const GuardFrame = 14; // Cost of guarding a frame, which involves pushing and poping a frame and capturing a rollback point.
    const VerifyEd25519Sig = 15; // Cost of verifying ed25519 signature of a payload.
    const VmMemRead = 16; // Cost of reading a slice of vm linear memory.
    const VmMemWrite = 17; // Cost of writing to a slice of vm linear memory.
    const VmInstantiation = 18; // Cost of instantiation a VM from wasm bytes code.
    const VmCachedInstantiation = 19;
    const InvokeVmFunction = 20;
    const ChargeBudget = 21;
    const ComputeKeccak256Hash = 22;
    const ComputeEcdsaSecp256k1Key = 23;
    const ComputeEcdsaSecp256k1Sig = 24;
    const RecoverEcdsaSecp256k1Key = 25;
    const Int256AddSub = 26;
    const Int256Mul = 27;
    const Int256Div = 28;
    const Int256Pow = 29;
    const Int256Shift = 30;

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