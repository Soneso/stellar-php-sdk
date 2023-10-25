<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

use Soneso\StellarSDK\Responses\Account\AccountBalanceResponse;
use Soneso\StellarSDK\Responses\Account\AccountBalancesResponse;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryData;

/**
 * Response when reading the current values of ledger entries.
 * https://soroban.stellar.org/api/methods/getLedgerEntries
 */
class GetLedgerEntriesResponse extends SorobanRpcResponse
{

    public ?array $entries = null; // LedgerEntry

    /// The current latest ledger observed by the node when this response was generated.
    public ?string $latestLedger = null;

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

    /**
     * @return string|null
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