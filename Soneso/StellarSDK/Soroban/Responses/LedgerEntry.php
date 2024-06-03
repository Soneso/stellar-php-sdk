<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

use Soneso\StellarSDK\Xdr\XdrLedgerEntryData;
use Soneso\StellarSDK\Xdr\XdrSCVal;

/**
 * Part of the getLedgerEntries response.
 * See: https://developers.stellar.org/network/soroban-rpc/api-reference/methods/getLedgerEntries
 */
class LedgerEntry
{
    /**
     * @var string $key The key of the ledger entry (serialized in a base64 xdr string).
     */
    public string $key;

    /**
     * @var string $xdr The current value of the given ledger entry (serialized in a base64 xdr string).
     */
    public string $xdr;

    /**
     * @var int $lastModifiedLedgerSeq The ledger sequence number of the last time this entry was updated.
     */
    public int $lastModifiedLedgerSeq;

    /**
     * @var int|null $liveUntilLedgerSeq Sequence number of the ledger.
     */
    public ?int $liveUntilLedgerSeq = null;

    /**
     * @param string $key The key of the ledger entry (serialized in a base64 xdr string).
     * @param string $xdr The current value of the given ledger entry (serialized in a base64 xdr string).
     * @param int $lastModifiedLedgerSeq The ledger sequence number of the last time this entry was updated.
     * @param int|null $liveUntilLedgerSeq Sequence number of the ledger.
     */
    public function __construct(
        string $key,
        string $xdr,
        int $lastModifiedLedgerSeq,
        ?int $liveUntilLedgerSeq = null,
    )
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

    /**
     * @return XdrLedgerEntryData The current value of the given ledger entry.
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
     */
    public function getKeyXdr(): XdrSCVal
    {
        return XdrSCVal::fromBase64Xdr($this->key);
    }

    /**
     * @param string $key The key of the ledger entry (serialized in a base64 xdr string).
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
     */
    public function setLiveUntilLedgerSeq(?int $liveUntilLedgerSeq): void
    {
        $this->liveUntilLedgerSeq = $liveUntilLedgerSeq;
    }

}