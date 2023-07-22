<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrContractDataEntry
{
    public XdrSCAddress $contract;
    public XdrSCVal $key;
    public XdrContractDataDurability $durability;
    public XdrContractDataEntryBody $body;
    public int $expirationLedgerSeq; // uint32

    /**
     * @param XdrSCAddress $contract
     * @param XdrSCVal $key
     * @param XdrContractDataDurability $durability
     * @param XdrContractDataEntryBody $body
     * @param int $expirationLedgerSeq
     */
    public function __construct(XdrSCAddress $contract, XdrSCVal $key, XdrContractDataDurability $durability, XdrContractDataEntryBody $body, int $expirationLedgerSeq)
    {
        $this->contract = $contract;
        $this->key = $key;
        $this->durability = $durability;
        $this->body = $body;
        $this->expirationLedgerSeq = $expirationLedgerSeq;
    }


    public function encode(): string {
        $bytes = $this->contract->encode();
        $bytes .= $this->key->encode();
        $bytes .= $this->durability->encode();
        $bytes .= $this->body->encode();
        $bytes .= XdrEncoder::unsignedInteger32($this->expirationLedgerSeq);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrContractDataEntry {
        $contract = XdrSCAddress::decode($xdr);
        $key = XdrSCVal::decode($xdr);
        $durability = XdrContractDataDurability::decode($xdr);
        $body = XdrContractDataEntryBody::decode($xdr);
        $expirationLedgerSeq = $xdr->readUnsignedInteger32();

        return new XdrContractDataEntry($contract, $key, $durability, $body, $expirationLedgerSeq);
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
     * @return XdrContractDataEntryBody
     */
    public function getBody(): XdrContractDataEntryBody
    {
        return $this->body;
    }

    /**
     * @param XdrContractDataEntryBody $body
     */
    public function setBody(XdrContractDataEntryBody $body): void
    {
        $this->body = $body;
    }

    /**
     * @return int
     */
    public function getExpirationLedgerSeq(): int
    {
        return $this->expirationLedgerSeq;
    }

    /**
     * @param int $expirationLedgerSeq
     */
    public function setExpirationLedgerSeq(int $expirationLedgerSeq): void
    {
        $this->expirationLedgerSeq = $expirationLedgerSeq;
    }

}