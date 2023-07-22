<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrContractDataEntryBodyData
{
    public int $flags; // uint32
    public XdrSCVal $val;

    /**
     * @param int $flags
     * @param XdrSCVal $val
     */
    public function __construct(int $flags, XdrSCVal $val)
    {
        $this->flags = $flags;
        $this->val = $val;
    }


    public function encode(): string {
        $bytes = XdrEncoder::unsignedInteger32($this->flags);
        $bytes .= $this->val->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrContractDataEntryBodyData {
        $flags = $xdr->readUnsignedInteger32();
        $val = XdrSCVal::decode($xdr);

        return new XdrContractDataEntryBodyData($flags, $val);
    }

    /**
     * @return int
     */
    public function getFlags(): int
    {
        return $this->flags;
    }

    /**
     * @param int $flags
     */
    public function setFlags(int $flags): void
    {
        $this->flags = $flags;
    }

    /**
     * @return XdrSCVal
     */
    public function getVal(): XdrSCVal
    {
        return $this->val;
    }

    /**
     * @param XdrSCVal $val
     */
    public function setVal(XdrSCVal $val): void
    {
        $this->val = $val;
    }

}