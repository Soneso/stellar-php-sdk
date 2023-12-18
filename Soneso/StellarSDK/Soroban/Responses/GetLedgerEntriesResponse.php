<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

/**
 * Response when reading the current values of ledger entries.
 * https://soroban.stellar.org/api/methods/getLedgerEntries
 */
class GetLedgerEntriesResponse extends SorobanRpcResponse
{

    public ?array $entries = null; // LedgerEntry

    /// The current latest ledger observed by the node when this response was generated.
    public ?int $latestLedger = null;

    public static function fromJson(array $json) : GetLedgerEntriesResponse {
        $result = new GetLedgerEntriesResponse($json);
        if (isset($json['result'])) {

            if (isset($json['result']['entries'])) {
                $result->entries = array();
                foreach ($json['result']['entries'] as $jsonEntry) {
                    $entry = LedgerEntry::fromJson($jsonEntry);
                    array_push($result->entries, $entry);
                }
            }

            $result->latestLedger = $json['result']['latestLedger'];
        } else if (isset($json['error'])) {
            $result->error = SorobanRpcErrorResponse::fromJson($json);
        }
        return $result;
    }

    /**
     * @return array|null
     */
    public function getEntries(): ?array
    {
        return $this->entries;
    }

    /**
     * @param array|null $entries
     */
    public function setEntries(?array $entries): void
    {
        $this->entries = $entries;
    }

    public function getLatestLedger(): ?int
    {
        return $this->latestLedger;
    }

    public function setLatestLedger(?int $latestLedger): void
    {
        $this->latestLedger = $latestLedger;
    }

}