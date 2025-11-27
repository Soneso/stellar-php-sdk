<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Requests;

/**
 * Event filter for the getEvents request.
 *
 * Example usage:
 * ```php
 * $topicFilters = new TopicFilters(
 *     new TopicFilter(["*", XdrSCVal::forSymbol("transfer")->toBase64Xdr()])
 * );
 *
 * $eventFilter = new EventFilter(
 *     type: "contract",
 *     contractIds: ["CAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABSC4"],
 *     topics: $topicFilters
 * );
 * ```
 *
 * @see EventFilters
 * @see https://soroban.stellar.org/api/methods/getEvents
 * @package Soneso\StellarSDK\Soroban\Requests
 */
class EventFilter
{
    /**
     * Constructor.
     *
     * @param string|null $type A comma separated list of event types (system, contract, or diagnostic)
     *  used to filter events. If omitted, all event types are included.
     * @param array<string>|null $contractIds List of contract ids to query for events.
     *  If omitted, return events for all contracts. Maximum 5 contract IDs are allowed per request.
     * @param TopicFilters|null $topics List of topic filters. If omitted, query for all events.
     *  If multiple filters are specified, events will be included if they match any of the filters.
     *  Maximum 5 filters are allowed per request.
     */
    public function __construct(
        public ?string $type = null,
        public ?array $contractIds = null,
        public ?TopicFilters $topics = null,
    ) {
    }

    /**
     * Builds and returns the request parameters array for the RPC API call.
     *
     * @return array<string, mixed> The request parameters formatted for Soroban RPC
     */
    public function getRequestParams() : array {
        $params = array();
        if ($this->type != null) {
            $params['type'] = $this->type;
        }
        if ($this->contractIds != null) {
            $cIds = array();
            foreach ($this->contractIds as $contractId) {
                array_push($cIds, $contractId);
            }
            $params['contractIds'] = $cIds;
        }

        if ($this->topics != null) {
            $topicsParams = array();
            foreach ($this->topics as $topic) {
                array_push($topicsParams, $topic->getRequestParams());
            }
            $params['topics'] = $topicsParams;
        }

        return $params;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @return array<string>|null
     */
    public function getContractIds(): ?array
    {
        return $this->contractIds;
    }

    /**
     * @return TopicFilters|null
     */
    public function getTopics(): ?TopicFilters
    {
        return $this->topics;
    }

}