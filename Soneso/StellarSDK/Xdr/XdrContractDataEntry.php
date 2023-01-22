<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrContractDataEntry
{
    public string $contractID; // hash
    public XdrSCVal $key;
    public XdrSCVal $val;

    /**
     * @param string $contractID
     * @param XdrSCVal $key
     * @param XdrSCVal $val
     */
    public function __construct(string $contractID, XdrSCVal $key, XdrSCVal $val)
    {
        $this->contractID = $contractID;
        $this->key = $key;
        $this->val = $val;
    }


    public function encode(): string {
        $bytes = XdrEncoder::opaqueFixed($this->contractID,32);
        $bytes .= $this->key->encode();
        $bytes .= $this->val->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrContractDataEntry {
        $contractID = $xdr->readOpaqueFixed(32);

        $key = XdrSCVal::decode($xdr);
        $val = XdrSCVal::decode($xdr);

        return new XdrContractDataEntry($contractID, $key, $val);
    }

    /**
     * @return string
     */
    public function getContractID(): string
    {
        return $this->contractID;
    }

    /**
     * @param string $contractID
     */
    public function setContractID(string $contractID): void
    {
        $this->contractID = $contractID;
    }

    /**
     * @return XdrSCVal
     */
    public function getKey(): XdrSCVal
    {
        return $this->key;
    }

    /**
     * @param XdrSCVal $key
     */
    public function setKey(XdrSCVal $key): void
    {
        $this->key = $key;
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