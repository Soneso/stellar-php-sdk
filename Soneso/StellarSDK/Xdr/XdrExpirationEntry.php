<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrExpirationEntry
{
    public string $keyHash;
    public int $expirationLedgerSeq; // uint32

    /**
     * @param string $keyHash
     * @param int $expirationLedgerSeq
     */
    public function __construct(string $keyHash, int $expirationLedgerSeq)
    {
        $this->keyHash = $keyHash;
        $this->expirationLedgerSeq = $expirationLedgerSeq;
    }


    public function encode(): string {
        $body = XdrEncoder::opaqueFixed($this->keyHash, 32);
        $body .= XdrEncoder::unsignedInteger32($this->expirationLedgerSeq);
        return $body;
    }

    public static function decode(XdrBuffer $xdr) : XdrExpirationEntry {
        $keyHash = $xdr->readOpaqueFixed(32);
        $expirationLedgerSeq = $xdr->readUnsignedInteger32();
        return new XdrExpirationEntry($keyHash, $expirationLedgerSeq);
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