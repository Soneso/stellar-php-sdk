<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrLedgerEntry
{
    public int $lastModifiedLedgerSeq; //uint32
    public XdrLedgerEntryData $data;
    public XdrLedgerEntryExt $ext;

    /**
     * @param int $lastModifiedLedgerSeq
     * @param XdrLedgerEntryData $data
     * @param XdrLedgerEntryExt $ext
     */
    public function __construct(int $lastModifiedLedgerSeq, XdrLedgerEntryData $data, XdrLedgerEntryExt $ext)
    {
        $this->lastModifiedLedgerSeq = $lastModifiedLedgerSeq;
        $this->data = $data;
        $this->ext = $ext;
    }


    public function encode(): string {
        $bytes = XdrEncoder::unsignedInteger32($this->lastModifiedLedgerSeq);
        $bytes .= $this->data->encode();
        $bytes .= $this->ext->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrLedgerEntry {
        $lastModifiedLedgerSeq = $xdr->readUnsignedInteger32();
        $data = XdrLedgerEntryData::decode($xdr);
        $ext = XdrLedgerEntryExt::decode($xdr);
        return new XdrLedgerEntry($lastModifiedLedgerSeq, $data, $ext);
    }

    /**
     * @return int
     */
    public function getLastModifiedLedgerSeq(): int
    {
        return $this->lastModifiedLedgerSeq;
    }

    /**
     * @param int $lastModifiedLedgerSeq
     */
    public function setLastModifiedLedgerSeq(int $lastModifiedLedgerSeq): void
    {
        $this->lastModifiedLedgerSeq = $lastModifiedLedgerSeq;
    }

    /**
     * @return XdrLedgerEntryData
     */
    public function getData(): XdrLedgerEntryData
    {
        return $this->data;
    }

    /**
     * @param XdrLedgerEntryData $data
     */
    public function setData(XdrLedgerEntryData $data): void
    {
        $this->data = $data;
    }

    /**
     * @return XdrLedgerEntryExt
     */
    public function getExt(): XdrLedgerEntryExt
    {
        return $this->ext;
    }

    /**
     * @param XdrLedgerEntryExt $ext
     */
    public function setExt(XdrLedgerEntryExt $ext): void
    {
        $this->ext = $ext;
    }
}