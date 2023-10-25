<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrTTLEntry
{
    public string $keyHash;
    public int $liveUntilLedgerSeq; // uint32

    /**
     * @param string $keyHash
     * @param int $liveUntilLedgerSeq
     */
    public function __construct(string $keyHash, int $liveUntilLedgerSeq)
    {
        $this->keyHash = $keyHash;
        $this->liveUntilLedgerSeq = $liveUntilLedgerSeq;
    }


    public function encode(): string {
        $body = XdrEncoder::opaqueFixed($this->keyHash, 32);
        $body .= XdrEncoder::unsignedInteger32($this->liveUntilLedgerSeq);
        return $body;
    }

    public static function decode(XdrBuffer $xdr) : XdrTTLEntry {
        $keyHash = $xdr->readOpaqueFixed(32);
        $liveUntilLedgerSeq = $xdr->readUnsignedInteger32();
        return new XdrTTLEntry($keyHash, $liveUntilLedgerSeq);
    }

    /**
     * @return string
     */
    public function getKeyHash(): string
    {
        return $this->keyHash;
    }

    /**
     * @param string $keyHash
     */
    public function setKeyHash(string $keyHash): void
    {
        $this->keyHash = $keyHash;
    }

    /**
     * @return int
     */
    public function getLiveUntilLedgerSeq(): int
    {
        return $this->liveUntilLedgerSeq;
    }

    /**
     * @param int $liveUntilLedgerSeq
     */
    public function setLiveUntilLedgerSeq(int $liveUntilLedgerSeq): void
    {
        $this->liveUntilLedgerSeq = $liveUntilLedgerSeq;
    }

}