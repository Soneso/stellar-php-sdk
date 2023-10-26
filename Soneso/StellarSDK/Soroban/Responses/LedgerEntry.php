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
    public string $lastModifiedLedgerSeq;
    public ?string $liveUntilLedgerSeq = null;

    /**
     * @param string $key
     * @param string $xdr
     * @param string $lastModifiedLedgerSeq
     * @param string|null $liveUntilLedgerSeq
     */
    public function __construct(string $key, string $xdr, string $lastModifiedLedgerSeq, ?string $liveUntilLedgerSeq)
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

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function getXdr(): string
    {
        return $this->xdr;
    }

    /**
     * @param string $xdr
     */
    public function setXdr(string $xdr): void
    {
        $this->xdr = $xdr;
    }

    /**
     * @return string
     */
    public function getLastModifiedLedgerSeq(): string
    {
        return $this->lastModifiedLedgerSeq;
    }

    /**
     * @param string $lastModifiedLedgerSeq
     */
    public function setLastModifiedLedgerSeq(string $lastModifiedLedgerSeq): void
    {
        $this->lastModifiedLedgerSeq = $lastModifiedLedgerSeq;
    }

    /**
     * @return string|null
     */
    public function getLiveUntilLedgerSeq(): ?string
    {
        return $this->liveUntilLedgerSeq;
    }

    /**
     * @param string|null $liveUntilLedgerSeq
     */
    public function setLiveUntilLedgerSeq(?string $liveUntilLedgerSeq): void
    {
        $this->liveUntilLedgerSeq = $liveUntilLedgerSeq;
    }

}