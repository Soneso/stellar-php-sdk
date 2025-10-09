<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

/**
 * Response of the getEvents request.
 * See: https://developers.stellar.org/network/soroban-rpc/api-reference/methods/getEvents
 */
class GetEventsResponse extends SorobanRpcResponse
{
    /**
     * @var array<EventInfo>|null $events found events.
     */
    public ?array $events = null;

    /**
     * @var int|null $latestLedger The sequence number of the latest ledger known to Soroban RPC at the time it handled the request.
     */
    public ?int $latestLedger = null;

    /**
     * @var String|null $cursor for pagination only available for protocol version >= 22
     */
    public ?String $cursor = null;

    /**
     * @var int|null $oldestLedger The sequence number of the oldest ledger in the search range.
     */
    public ?int $oldestLedger = null;

    /**
     * @var int|null $latestLedgerCloseTime Unix timestamp of the latest ledger close time.
     */
    public ?int $latestLedgerCloseTime = null;

    /**
     * @var int|null $oldestLedgerCloseTime Unix timestamp of the oldest ledger close time.
     */
    public ?int $oldestLedgerCloseTime = null;

    public static function fromJson(array $json): GetEventsResponse
    {
        $result = new GetEventsResponse($json);
        if (isset($json['result'])) {
            if (isset($json['result']['events'])) {
                $result->events = array();
                foreach ($json['result']['events'] as $jsonValue) {
                    $value = EventInfo::fromJson($jsonValue);
                    array_push($result->events, $value);
                }
            }
            if (isset($json['result']['latestLedger'])) {
                $result->latestLedger = $json['result']['latestLedger'];
            }
            if (isset($json['result']['cursor'])) {
                $result->cursor = $json['result']['cursor']; // protocol >= 22
            }
            if (isset($json['result']['oldestLedger'])) {
                $result->oldestLedger = $json['result']['oldestLedger'];
            }
            if (isset($json['result']['latestLedgerCloseTime'])) {
                $result->latestLedgerCloseTime = (int)$json['result']['latestLedgerCloseTime'];
            }
            if (isset($json['result']['oldestLedgerCloseTime'])) {
                $result->oldestLedgerCloseTime = (int)$json['result']['oldestLedgerCloseTime'];
            }
        } else if (isset($json['error'])) {
            $result->error = SorobanRpcErrorResponse::fromJson($json);
        }
        return $result;
    }

    /**
     * @return array<EventInfo>|null events.
     */
    public function getEvents(): ?array
    {
        return $this->events;
    }

    /**
     * @param array<EventInfo>|null $events
     */
    public function setEvents(?array $events): void
    {
        $this->events = $events;
    }

    /**
     * @return int|null The sequence number of the latest ledger known to Soroban RPC at the time it handled the request.
     */
    public function getLatestLedger(): ?int
    {
        return $this->latestLedger;
    }

    /**
     * @param int|null $latestLedger The sequence number of the latest ledger known to Soroban RPC at the time it handled the request.
     */
    public function setLatestLedger(?int $latestLedger): void
    {
        $this->latestLedger = $latestLedger;
    }

    /**
     * @return String|null for pagination only available for protocol version >= 22
     */
    public function getCursor(): ?string
    {
        return $this->cursor;
    }

    /**
     * @param String|null $cursor for pagination only for protocol version >= 22
     */
    public function setCursor(?string $cursor): void
    {
        $this->cursor = $cursor;
    }

    /**
     * @return int|null The sequence number of the oldest ledger in the search range.
     */
    public function getOldestLedger(): ?int
    {
        return $this->oldestLedger;
    }

    /**
     * @param int|null $oldestLedger The sequence number of the oldest ledger in the search range.
     */
    public function setOldestLedger(?int $oldestLedger): void
    {
        $this->oldestLedger = $oldestLedger;
    }

    /**
     * @return int|null Unix timestamp of the latest ledger close time.
     */
    public function getLatestLedgerCloseTime(): ?int
    {
        return $this->latestLedgerCloseTime;
    }

    /**
     * @param int|null $latestLedgerCloseTime Unix timestamp of the latest ledger close time.
     */
    public function setLatestLedgerCloseTime(?int $latestLedgerCloseTime): void
    {
        $this->latestLedgerCloseTime = $latestLedgerCloseTime;
    }

    /**
     * @return int|null Unix timestamp of the oldest ledger close time.
     */
    public function getOldestLedgerCloseTime(): ?int
    {
        return $this->oldestLedgerCloseTime;
    }

    /**
     * @param int|null $oldestLedgerCloseTime Unix timestamp of the oldest ledger close time.
     */
    public function setOldestLedgerCloseTime(?int $oldestLedgerCloseTime): void
    {
        $this->oldestLedgerCloseTime = $oldestLedgerCloseTime;
    }

}