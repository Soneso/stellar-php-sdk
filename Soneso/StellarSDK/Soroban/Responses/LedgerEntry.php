<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

use Soneso\StellarSDK\Xdr\XdrLedgerEntryData;
use Soneso\StellarSDK\Xdr\XdrSCVal;

/**
 * Part of the getLedgerEntries response.
 *
 * @package Soneso\StellarSDK\Soroban\Responses
 * @see https://developers.stellar.org/network/soroban-rpc/api-reference/methods/getLedgerEntries
 */
class LedgerEntry
{
    /**
     * @param string $key The key of the ledger entry (serialized in a base64 XDR string)
     * @param string $xdr The current value of the given ledger entry (serialized in a base64 XDR string)
     * @param int $lastModifiedLedgerSeq The ledger sequence number of the last time this entry was updated
     * @param int|null $liveUntilLedgerSeq Ledger sequence number until which the entry is live
     * @param string|null $ext The entry's "Ext" field (only available for protocol version >= 23)
     */
    public function __construct(
        public string $key,
        public string $xdr,
        public int $lastModifiedLedgerSeq,
        public ?int $liveUntilLedgerSeq = null,
        public ?string $ext = null,
    )
    {
    }

    /**
     * Creates an instance from JSON-RPC response data
     *
     * @param array<string,mixed> $json The JSON response data
     * @return static The created instance
     */
    public static function fromJson(array $json): LedgerEntry
    {
        $key = $json['key'];
        $xdr = $json['xdr'];
        $lastModifiedLedgerSeq = $json['lastModifiedLedgerSeq'];
        $liveUntilLedgerSeq = null;
        if (isset($json['liveUntilLedgerSeq'])) {
            $liveUntilLedgerSeq = $json['liveUntilLedgerSeq'];
        }
        $ext = null;
        if (isset($json['ext'])) {
            $ext = $json['ext'];
        }
        return new LedgerEntry($key, $xdr, $lastModifiedLedgerSeq, $liveUntilLedgerSeq, $ext);
    }

    /**
     * @return XdrLedgerEntryData The current value of the given ledger entry.
     * @throws \InvalidArgumentException If XDR data is malformed
     */
    public function getLedgerEntryDataXdr() : XdrLedgerEntryData {
        return XdrLedgerEntryData::fromBase64Xdr($this->xdr);
    }

    /**
     * @return string The key of the ledger entry (serialized in a base64 xdr string).
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return XdrSCVal The key of the ledger entry.
     * @throws \InvalidArgumentException If XDR data is malformed
     */
    public function getKeyXdr(): XdrSCVal
    {
        return XdrSCVal::fromBase64Xdr($this->key);
    }

    /**
     * @param string $key The key of the ledger entry (serialized in a base64 xdr string).
     * @return void
     */
    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    /**
     * @return string The current value of the given ledger entry (serialized in a base64 xdr string).
     */
    public function getXdr(): string
    {
        return $this->xdr;
    }

    /**
     * @param string $xdr The current value of the given ledger entry (serialized in a base64 xdr string).
     * @return void
     */
    public function setXdr(string $xdr): void
    {
        $this->xdr = $xdr;
    }

    /**
     * @return int The ledger sequence number of the last time this entry was updated.
 */
    public function getLastModifiedLedgerSeq(): int
    {
        return $this->lastModifiedLedgerSeq;
    }

    /**
     * @param int $lastModifiedLedgerSeq The ledger sequence number of the last time this entry was updated.
     * @return void
     */
    public function setLastModifiedLedgerSeq(int $lastModifiedLedgerSeq): void
    {
        $this->lastModifiedLedgerSeq = $lastModifiedLedgerSeq;
    }

    /**
     * @return int|null Sequence number of the ledger.
     */
    public function getLiveUntilLedgerSeq(): ?int
    {
        return $this->liveUntilLedgerSeq;
    }

    /**
     * @param int|null $liveUntilLedgerSeq Sequence number of the ledger.
     * @return void
     */
    public function setLiveUntilLedgerSeq(?int $liveUntilLedgerSeq): void
    {
        $this->liveUntilLedgerSeq = $liveUntilLedgerSeq;
    }

    /**
     * @return string|null
     */
    public function getExt(): ?string
    {
        return $this->ext;
    }

    /**
     * @param string|null $ext
     * @return void
     */
    public function setExt(?string $ext): void
    {
        $this->ext = $ext;
    }

}