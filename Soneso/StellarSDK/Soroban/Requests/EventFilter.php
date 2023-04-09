<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Requests;

/**
 * Event filter for the getEvents request.
 * See: https://soroban.stellar.org/api/methods/getEvents
 */
class EventFilter
{
    /// (optional) A comma separated list of event types (system, contract, or diagnostic)
    /// used to filter events. If omitted, all event types are included.
    public ?string $type = null;

    /// (optional) List of contract ids to query for events.
    /// If omitted, return events for all contracts.
    /// Maximum 5 contract IDs are allowed per request.
    public ?array $contractIds = null;

    /// (optional) List of topic filters. If omitted, query for all events.
    /// If multiple filters are specified, events will be included if they match any of the filters.
    /// Maximum 5 filters are allowed per request.
    public ?TopicFilters $topics = null;

    /**
     * @param string|null $type
     * @param array|null $contractIds
     * @param TopicFilters|null $topics
     */
    public function __construct(?string $type = null, ?array $contractIds = null, ?TopicFilters $topics = null)
    {
        $this->type = $type;
        $this->contractIds = $contractIds;
        $this->topics = $topics;
    }

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
     * @return array|null
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