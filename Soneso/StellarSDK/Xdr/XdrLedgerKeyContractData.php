<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrLedgerKeyContractData
{
    public XdrSCAddress $contract;
    public XdrSCVal $key;
    public XdrContractDataDurability $durability;

    /**
     * @param XdrSCAddress $contract
     * @param XdrSCVal $key
     * @param XdrContractDataDurability $durability
     */
    public function __construct(XdrSCAddress $contract, XdrSCVal $key, XdrContractDataDurability $durability)
    {
        $this->contract = $contract;
        $this->key = $key;
        $this->durability = $durability;
    }


    public function encode(): string {
        $body = $this->contract->encode();
        $body .= $this->key->encode();
        $body .= $this->durability->encode();
        return $body;
    }

    public static function decode(XdrBuffer $xdr) : XdrLedgerKeyContractData {
        $contract = XdrSCAddress::decode($xdr);
        $key = XdrSCVal::decode($xdr);
        $durability = XdrContractDataDurability::decode($xdr);
        return new XdrLedgerKeyContractData($contract, $key, $durability);
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
}