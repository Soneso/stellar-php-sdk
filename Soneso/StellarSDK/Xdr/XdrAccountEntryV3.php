<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrAccountEntryV3
{
    public XdrExtensionPoint $ext;
    public int $seqLedger; //uint32
    public int $seqTime; // uint64


    /**
     * @param int $seqLedger
     * @param int $seqTime
     * @param XdrExtensionPoint $ext
     */
    public function __construct(XdrExtensionPoint $ext, int $seqLedger, int $seqTime)
    {
        $this->ext = $ext;
        $this->seqLedger = $seqLedger;
        $this->seqTime = $seqTime;
    }


    public function encode(): string {
        $bytes = $this->ext->encode();
        $bytes .= XdrEncoder::unsignedInteger32($this->seqLedger);
        $bytes .= XdrEncoder::integer64($this->seqTime);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrAccountEntryV3 {
        $ext = XdrExtensionPoint::decode($xdr);
        $seqLedger = $xdr->readUnsignedInteger32();
        $seqTime = $xdr->readInteger64();

        return new XdrAccountEntryV3($ext, $seqLedger, $seqTime);
    }

    /**
     * @return int
     */
    public function getSeqLedger(): int
    {
        return $this->seqLedger;
    }

    /**
     * @param int $seqLedger
     */
    public function setSeqLedger(int $seqLedger): void
    {
        $this->seqLedger = $seqLedger;
    }

    /**
     * @return int
     */
    public function getSeqTime(): int
    {
        return $this->seqTime;
    }

    /**
     * @param int $seqTime
     */
    public function setSeqTime(int $seqTime): void
    {
        $this->seqTime = $seqTime;
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
}