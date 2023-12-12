<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

use Soneso\StellarSDK\Xdr\XdrLedgerEntryData;

class LedgerEntry
{
    public string $key;
    public string $xdr;
    public int $lastModifiedLedgerSeq;
    public ?int $liveUntilLedgerSeq = null;

    /**
     * @param string $key
     * @param string $xdr
     * @param int $lastModifiedLedgerSeq
     * @param int|null $liveUntilLedgerSeq
     */
    public function __construct(string $key, string $xdr, int $lastModifiedLedgerSeq, ?int $liveUntilLedgerSeq)
    {
        $this->key = $key;
        $this->xdr = $xdr;
        $this->lastModifiedLedgerSeq = $lastModifiedLedgerSeq;
        $this->liveUntilLedgerSeq = $liveUntilLedgerSeq;
    }


    public static function fromJson(array $json): LedgerEntry
    {
        $key = $json['key'];
        $xdr = $json['xdr'];
        $lastModifiedLedgerSeq = $json['lastModifiedLedgerSeq'];
        $liveUntilLedgerSeq = null;
        if (isset($json['liveUntilLedgerSeq'])) {
            $liveUntilLedgerSeq = $json['liveUntilLedgerSeq'];
        }
        return new LedgerEntry($key, $xdr, $lastModifiedLedgerSeq, $liveUntilLedgerSeq);
    }

    public function getLedgerEntryDataXdr() : XdrLedgerEntryData {
        return XdrLedgerEntryData::fromBase64Xdr($this->xdr);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function getXdr(): string
    {
        return $this->xdr;
    }

    public function setXdr(string $xdr): void
    {
        $this->xdr = $xdr;
    }

    public function getLastModifiedLedgerSeq(): int
    {
        return $this->lastModifiedLedgerSeq;
    }

    public function setLastModifiedLedgerSeq(int $lastModifiedLedgerSeq): void
    {
        $this->lastModifiedLedgerSeq = $lastModifiedLedgerSeq;
    }

    public function getLiveUntilLedgerSeq(): ?int
    {
        return $this->liveUntilLedgerSeq;
    }

    public function setLiveUntilLedgerSeq(?int $liveUntilLedgerSeq): void
    {
        $this->liveUntilLedgerSeq = $liveUntilLedgerSeq;
    }

}