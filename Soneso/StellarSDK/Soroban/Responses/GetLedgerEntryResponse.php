<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

use Soneso\StellarSDK\Xdr\XdrLedgerEntryData;

/**
 * Response when reading the current values of ledger entries.
 * https://soroban.stellar.org/api/methods/getLedgerEntry
 */
class GetLedgerEntryResponse extends SorobanRpcResponse
{

    /// The current value of the given ledger entry  (serialized in a base64 string)
    public ?string $ledgerEntryData = null;

    /// The ledger number of the last time this entry was updated (optional)
    public ?string $lastModifiedLedgerSeq = null;

    /// The current latest ledger observed by the node when this response was generated.
    public ?string $latestLedger = null;

    public static function fromJson(array $json) : GetLedgerEntryResponse {
        $result = new GetLedgerEntryResponse($json);
        if (isset($json['result'])) {
            $result->ledgerEntryData = $json['result']['xdr'];
            if (isset($json['result']['lastModifiedLedgerSeq'])) {
                $result->lastModifiedLedgerSeq = $json['result']['lastModifiedLedgerSeq'];
            }
            $result->latestLedger = $json['result']['latestLedger'];
        } else if (isset($json['error'])) {
            $result->error = SorobanRpcErrorResponse::fromJson($json);
        }
        return $result;
    }

    /**
     * @return XdrLedgerEntryData|null  The current value of the given ledger entry as xdr object.
     */
    public function getLedgerEntryDataXdr() : ?XdrLedgerEntryData {
        if ($this->ledgerEntryData != null) {
            return XdrLedgerEntryData::fromBase64Xdr($this->ledgerEntryData);
        }
        return null;
    }
    /**
     * @return string|null The current value of the given ledger entry  (serialized in a base64 string).
     */
    public function getLedgerEntryData(): ?string
    {
        return $this->ledgerEntryData;
    }

    /**
     * @param string|null $ledgerEntryData
     */
    public function setLedgerEntryData(?string $ledgerEntryData): void
    {
        $this->ledgerEntryData = $ledgerEntryData;
    }

    /**
     * @return string|null The ledger number of the last time this entry was updated (optional)
     */
    public function getLastModifiedLedgerSeq(): ?string
    {
        return $this->lastModifiedLedgerSeq;
    }

    /**
     * @param string|null $lastModifiedLedgerSeq
     */
    public function setLastModifiedLedgerSeq(?string $lastModifiedLedgerSeq): void
    {
        $this->lastModifiedLedgerSeq = $lastModifiedLedgerSeq;
    }

    /**
     * @return string|null The current latest ledger observed by the node when this response was generated.
     */
    public function getLatestLedger(): ?string
    {
        return $this->latestLedger;
    }

    /**
     * @param string|null $latestLedger
     */
    public function setLatestLedger(?string $latestLedger): void
    {
        $this->latestLedger = $latestLedger;
    }
}