<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

/**
 * General node health check response for the getHealth request.
 *
 * @package Soneso\StellarSDK\Soroban\Responses
 * @see https://developers.stellar.org/network/soroban-rpc/api-reference/methods/getHealth
 */
class GetHealthResponse extends SorobanRpcResponse
{
    const HEALTHY = "healthy";

    /**
     * @var string|null $status Health status of the node (e.g. "healthy")
     */
    public ?string $status = null;

    /**
     * @var int|null $ledgerRetentionWindow Maximum retention window configured
     */
    public ?int $ledgerRetentionWindow = null;

    /**
     * @var int|null $oldestLedger Oldest ledger sequence kept in history
     */
    public ?int $oldestLedger = null;

    /**
     * @var int|null $latestLedger Most recent known ledger sequence
     */
    public ?int $latestLedger = null;

    /**
     * @var string|null $latestLedgerCloseTime Unix timestamp (seconds) at which the latest known ledger
     *  closed. Returned by RPC servers from v27.1.0; null when the server does not provide it.
     */
    public ?string $latestLedgerCloseTime = null;

    /**
     * @var string|null $oldestLedgerCloseTime Unix timestamp (seconds) at which the oldest ledger kept
     *  in history closed. Returned by RPC servers from v27.1.0; null when the server does not provide it.
     */
    public ?string $oldestLedgerCloseTime = null;

    /**
     * Creates an instance from JSON-RPC response data
     *
     * @param array<string,mixed> $json The JSON response data
     * @return static The created instance
     */
    public static function fromJson(array $json) : GetHealthResponse {
        $result = new GetHealthResponse($json);
        if (isset($json['result'])) {
            if (isset($json['result']['status'])) {
                $result->status = $json['result']['status'];
            }
            if (isset($json['result']['ledgerRetentionWindow'])) {
                $result->ledgerRetentionWindow = $json['result']['ledgerRetentionWindow'];
            }
            if (isset($json['result']['oldestLedger'])) {
                $result->oldestLedger = $json['result']['oldestLedger'];
            }
            if (isset($json['result']['latestLedger'])) {
                $result->latestLedger = $json['result']['latestLedger'];
            }
            if (isset($json['result']['latestLedgerCloseTime'])) {
                $result->latestLedgerCloseTime = $json['result']['latestLedgerCloseTime'];
            }
            if (isset($json['result']['oldestLedgerCloseTime'])) {
                $result->oldestLedgerCloseTime = $json['result']['oldestLedgerCloseTime'];
            }
        } else if (isset($json['error'])) {
            $result->error = SorobanRpcErrorResponse::fromJson($json);
        }
        return $result;
    }

    /**
     * @return string|null Health status of the node (e.g. "healthy")
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @param string|null $status Health status of the node
     * @return void
     */
    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return int|null Maximum retention window configured
     */
    public function getLedgerRetentionWindow(): ?int
    {
        return $this->ledgerRetentionWindow;
    }

    /**
     * @param int|null $ledgerRetentionWindow Maximum retention window configured
     * @return void
     */
    public function setLedgerRetentionWindow(?int $ledgerRetentionWindow): void
    {
        $this->ledgerRetentionWindow = $ledgerRetentionWindow;
    }

    /**
     * @return int|null Oldest ledger sequence kept in history
     */
    public function getOldestLedger(): ?int
    {
        return $this->oldestLedger;
    }

    /**
     * @param int|null $oldestLedger Oldest ledger sequence kept in history
     * @return void
     */
    public function setOldestLedger(?int $oldestLedger): void
    {
        $this->oldestLedger = $oldestLedger;
    }

    /**
     * @return int|null Most recent known ledger sequence
     */
    public function getLatestLedger(): ?int
    {
        return $this->latestLedger;
    }

    /**
     * @param int|null $latestLedger Most recent known ledger sequence
     * @return void
     */
    public function setLatestLedger(?int $latestLedger): void
    {
        $this->latestLedger = $latestLedger;
    }

    /**
     * @return string|null Unix timestamp (seconds) at which the latest known ledger closed
     */
    public function getLatestLedgerCloseTime(): ?string
    {
        return $this->latestLedgerCloseTime;
    }

    /**
     * @param string|null $latestLedgerCloseTime Unix timestamp (seconds) at which the latest known ledger closed
     * @return void
     */
    public function setLatestLedgerCloseTime(?string $latestLedgerCloseTime): void
    {
        $this->latestLedgerCloseTime = $latestLedgerCloseTime;
    }

    /**
     * @return string|null Unix timestamp (seconds) at which the oldest ledger kept in history closed
     */
    public function getOldestLedgerCloseTime(): ?string
    {
        return $this->oldestLedgerCloseTime;
    }

    /**
     * @param string|null $oldestLedgerCloseTime Unix timestamp (seconds) at which the oldest ledger kept in history closed
     * @return void
     */
    public function setOldestLedgerCloseTime(?string $oldestLedgerCloseTime): void
    {
        $this->oldestLedgerCloseTime = $oldestLedgerCloseTime;
    }
}