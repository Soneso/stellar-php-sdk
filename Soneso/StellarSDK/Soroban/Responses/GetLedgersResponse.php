<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Soroban\Responses;

/**
 * Response for the getLedgers request.
 *
 * @package Soneso\StellarSDK\Soroban\Responses
 * @see https://developers.stellar.org/docs/data/rpc/api-reference/methods/getLedgers
 */
class GetLedgersResponse extends SorobanRpcResponse
{
    /**
     * @var array<LedgerInfo>|null $ledgers Array of ledger information
     */
    public ?array $ledgers = null;

    /**
     * @var int|null $latestLedger The sequence number of the latest ledger known to Soroban RPC at the time it handled the request
     */
    public ?int $latestLedger = null;

    /**
     * @var int|null $latestLedgerCloseTime Unix timestamp of the latest ledger close time
     */
    public ?int $latestLedgerCloseTime = null;

    /**
     * @var int|null $oldestLedger The sequence number of the oldest ledger ingested by Soroban RPC
     */
    public ?int $oldestLedger = null;

    /**
     * @var int|null $oldestLedgerCloseTime Unix timestamp of the oldest ledger close time
     */
    public ?int $oldestLedgerCloseTime = null;

    /**
     * @var string|null $cursor Cursor value for pagination
     */
    public ?string $cursor = null;

    /**
     * Creates an instance from JSON-RPC response data
     *
     * @param array<string,mixed> $json The JSON response data
     * @return static The created instance
     */
    public static function fromJson(array $json): GetLedgersResponse
    {
        $result = new GetLedgersResponse($json);
        if (isset($json['result'])) {
            if (isset($json['result']['ledgers'])) {
                $result->ledgers = array();
                foreach ($json['result']['ledgers'] as $jsonValue) {
                    $value = LedgerInfo::fromJson($jsonValue);
                    array_push($result->ledgers, $value);
                }
            }
            if (isset($json['result']['latestLedger'])) {
                $result->latestLedger = $json['result']['latestLedger'];
            }
            if (isset($json['result']['latestLedgerCloseTime'])) {
                $result->latestLedgerCloseTime = $json['result']['latestLedgerCloseTime'];
            }
            if (isset($json['result']['oldestLedger'])) {
                $result->oldestLedger = $json['result']['oldestLedger'];
            }
            if (isset($json['result']['oldestLedgerCloseTime'])) {
                $result->oldestLedgerCloseTime = $json['result']['oldestLedgerCloseTime'];
            }
            if (isset($json['result']['cursor'])) {
                $result->cursor = $json['result']['cursor'];
            }
        } else if (isset($json['error'])) {
            $result->error = SorobanRpcErrorResponse::fromJson($json);
        }
        return $result;
    }

    /**
     * @return array<LedgerInfo>|null Array of ledger information
     */
    public function getLedgers(): ?array
    {
        return $this->ledgers;
    }

    /**
     * @param array<LedgerInfo>|null $ledgers Array of ledger information
     * @return void
     */
    public function setLedgers(?array $ledgers): void
    {
        $this->ledgers = $ledgers;
    }

    /**
     * @return int|null The sequence number of the latest ledger known to Soroban RPC at the time it handled the request
     */
    public function getLatestLedger(): ?int
    {
        return $this->latestLedger;
    }

    /**
     * @param int|null $latestLedger The sequence number of the latest ledger
     * @return void
     */
    public function setLatestLedger(?int $latestLedger): void
    {
        $this->latestLedger = $latestLedger;
    }

    /**
     * @return int|null Unix timestamp of the latest ledger close time
     */
    public function getLatestLedgerCloseTime(): ?int
    {
        return $this->latestLedgerCloseTime;
    }

    /**
     * @param int|null $latestLedgerCloseTime Unix timestamp of the latest ledger close time
     * @return void
     */
    public function setLatestLedgerCloseTime(?int $latestLedgerCloseTime): void
    {
        $this->latestLedgerCloseTime = $latestLedgerCloseTime;
    }

    /**
     * @return int|null The sequence number of the oldest ledger ingested by Soroban RPC
     */
    public function getOldestLedger(): ?int
    {
        return $this->oldestLedger;
    }

    /**
     * @param int|null $oldestLedger The sequence number of the oldest ledger
     * @return void
     */
    public function setOldestLedger(?int $oldestLedger): void
    {
        $this->oldestLedger = $oldestLedger;
    }

    /**
     * @return int|null Unix timestamp of the oldest ledger close time
     */
    public function getOldestLedgerCloseTime(): ?int
    {
        return $this->oldestLedgerCloseTime;
    }

    /**
     * @param int|null $oldestLedgerCloseTime Unix timestamp of the oldest ledger close time
     * @return void
     */
    public function setOldestLedgerCloseTime(?int $oldestLedgerCloseTime): void
    {
        $this->oldestLedgerCloseTime = $oldestLedgerCloseTime;
    }

    /**
     * @return string|null Cursor value for pagination
     */
    public function getCursor(): ?string
    {
        return $this->cursor;
    }

    /**
     * @param string|null $cursor Cursor value for pagination
     * @return void
     */
    public function setCursor(?string $cursor): void
    {
        $this->cursor = $cursor;
    }
}
