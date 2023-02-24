<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

class GetEventsResponse extends SorobanRpcResponse
{

    public ?array $events = null; // [EventInfo]
    public ?string $latestLedger = null;

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
        } else if (isset($json['error'])) {
            $result->error = SorobanRpcErrorResponse::fromJson($json);
        }
        return $result;
    }

    /**
     * @return array|null
     */
    public function getEvents(): ?array
    {
        return $this->events;
    }

    /**
     * @param array|null $events
     */
    public function setEvents(?array $events): void
    {
        $this->events = $events;
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