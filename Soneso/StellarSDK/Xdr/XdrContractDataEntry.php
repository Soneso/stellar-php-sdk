<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrContractDataEntry
{
    public XdrExtensionPoint $ext;
    public XdrSCAddress $contract;
    public XdrSCVal $key;
    public XdrContractDataDurability $durability;
    public XdrSCVal $val;

    /**
     * @param XdrExtensionPoint $ext
     * @param XdrSCAddress $contract
     * @param XdrSCVal $key
     * @param XdrContractDataDurability $durability
     * @param XdrSCVal $val
     */
    public function __construct(XdrExtensionPoint $ext, XdrSCAddress $contract, XdrSCVal $key, XdrContractDataDurability $durability, XdrSCVal $val)
    {
        $this->ext = $ext;
        $this->contract = $contract;
        $this->key = $key;
        $this->durability = $durability;
        $this->val = $val;
    }


    public function encode(): string {
        $bytes = $this->ext->encode();
        $bytes .= $this->contract->encode();
        $bytes .= $this->key->encode();
        $bytes .= $this->durability->encode();
        $bytes .= $this->val->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrContractDataEntry {
        $ext = XdrExtensionPoint::decode($xdr);
        $contract = XdrSCAddress::decode($xdr);
        $key = XdrSCVal::decode($xdr);
        $durability = XdrContractDataDurability::decode($xdr);
        $val = XdrSCVal::decode($xdr);

        return new XdrContractDataEntry($ext, $contract, $key, $durability, $val);
    }

    /**
     * @return XdrExtensionPoint
     */
    public function getExt(): XdrExtensionPoint
    {
        return $this->ext;
    }

    /**
     * @param XdrExtensionPoint $ext
     */
    public function setExt(XdrExtensionPoint $ext): void
    {
        $this->ext = $ext;
    }

    /**
     * @return XdrSCAddress
     */
    public function getContract(): XdrSCAddress
    {
        return $this->contract;
    }

    /**
     * @param XdrSCAddress $contract
     */
    public function setContract(XdrSCAddress $contract): void
    {
        $this->contract = $contract;
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
     * @return XdrContractDataDurability
     */
    public function getDurability(): XdrContractDataDurability
    {
        return $this->durability;
    }

    /**
     * @param XdrContractDataDurability $durability
     */
    public function setDurability(XdrContractDataDurability $durability): void
    {
        $this->durability = $durability;
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