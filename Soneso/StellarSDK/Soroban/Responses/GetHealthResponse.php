<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

/**
 * General node health check response or the getHealth request.
 * See: https://developers.stellar.org/network/soroban-rpc/api-reference/methods/getHealth
 */
class GetHealthResponse extends SorobanRpcResponse
{
    const HEALTHY = "healthy";

    /**
     * @var string|null $status e.g. "healthy"
     */
    public ?string $status = null;

    /**
     * @var int|null $ledgerRetentionWindow Maximum retention window configured.
     * A full window state can be determined via: ledgerRetentionWindow = latestLedger - oldestLedger + 1
     */
    public ?int $ledgerRetentionWindow = null;

    /**
     * @var int|null $oldestLedger Oldest ledger sequence kept in history
     */
    public ?int $oldestLedger = null;


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
        } else if (isset($json['error'])) {
            $result->error = SorobanRpcErrorResponse::fromJson($json);
        }
        return $result;
    }

    /**
     * @return string|null health status. e.g. "healthy"
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @param string|null $status e.g. "healthy"
     */
    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return int|null Maximum retention window configured. A full window state can be determined
     * via: ledgerRetentionWindow = latestLedger - oldestLedger + 1
     */
    public function getLedgerRetentionWindow(): ?int
    {
        return $this->ledgerRetentionWindow;
    }

    /**
     * @param int|null $ledgerRetentionWindow Maximum retention window configured. A full window state can be
     * determined via: ledgerRetentionWindow = latestLedger - oldestLedger + 1
     */
    public function setLedgerRetentionWindow(?int $ledgerRetentionWindow): void
    {
        $this->ledgerRetentionWindow = $ledgerRetentionWindow;
    }

    /**
     * @return int|null Oldest ledger sequence kept in history.
     */
    public function getOldestLedger(): ?int
    {
        return $this->oldestLedger;
    }

    /**
     * @param int|null $oldestLedger Oldest ledger sequence kept in history.
     */
    public function setOldestLedger(?int $oldestLedger): void
    {
        $this->oldestLedger = $oldestLedger;
    }
}