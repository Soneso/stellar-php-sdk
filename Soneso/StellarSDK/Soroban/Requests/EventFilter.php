<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Requests;

class EventFilter
{
    public ?string $type = null;
    public ?array $contractIds = null;
    public ?SegmentFiltersList $topics = null;

    /**
     * @param string|null $type
     * @param array|null $contractIds
     * @param SegmentFiltersList|null $topics
     */
    public function __construct(?string $type = null, ?array $contractIds = null, ?SegmentFiltersList $topics = null)
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
                $tParams = array();
                foreach ($topic as $filter) {
                    array_push($tParams, $filter->getRequestParams());
                }
                array_push($topicsParams, $tParams);
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
     * @param string|null $type
     */
    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return array|null
     */
    public function getContractIds(): ?array
    {
        return $this->contractIds;
    }

    /**
     * @param array|null $contractIds
     */
    public function setContractIds(?array $contractIds): void
    {
        $this->contractIds = $contractIds;
    }

    /**
     * @return SegmentFiltersList|null
     */
    public function getTopics(): ?SegmentFiltersList
    {
        return $this->topics;
    }

    /**
     * @param SegmentFiltersList|null $topics
     */
    public function setTopics(?SegmentFiltersList $topics): void
    {
        $this->topics = $topics;
    }

}