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
     * @var String|null $cursor for pagination
     */
    public ?String $cursor = null;

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
                $result->cursor = $json['result']['cursor'];
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
     * @return String|null cursor for pagination
     */
    public function getCursor(): ?string
    {
        return $this->cursor;
    }

    /**
     * @param String|null $cursor for pagination
     */
    public function setCursor(?string $cursor): void
    {
        $this->cursor = $cursor;
    }

}