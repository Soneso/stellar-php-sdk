<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Requests;

class GetEventsRequest
{
    public string $startLedger;
    public string $endLedger;
    public ?EventFilters $filters = null;
    public ?PaginationOptions $paginationOptions = null;

    /**
     * @param string $startLedger
     * @param string $endLedger
     * @param EventFilters|null $filters
     * @param PaginationOptions|null $paginationOptions
     */
    public function __construct(string $startLedger, string $endLedger, ?EventFilters $filters = null, ?PaginationOptions $paginationOptions = null)
    {
        $this->startLedger = $startLedger;
        $this->endLedger = $endLedger;
        $this->filters = $filters;
        $this->paginationOptions = $paginationOptions;
    }

    public function getRequestParams() : array {
        $params = array(
            'startLedger' => $this->startLedger,
            'endLedger' => $this->endLedger
        );

        if ($this->filters != null) {
            $filterParams = array();
            foreach ($this->filters as $filter) {
                array_push($filterParams, $filter->getRequestParams());
            }
            $params['filters'] = $filterParams;
        }

        if ($this->paginationOptions != null) {
            $params['pagination'] = $this->paginationOptions->getRequestParams();
        }
        return $params;
    }

    /**
     * @return string
     */
    public function getStartLedger(): string
    {
        return $this->startLedger;
    }

    /**
     * @param string $startLedger
     */
    public function setStartLedger(string $startLedger): void
    {
        $this->startLedger = $startLedger;
    }

    /**
     * @return string
     */
    public function getEndLedger(): string
    {
        return $this->endLedger;
    }

    /**
     * @param string $endLedger
     */
    public function setEndLedger(string $endLedger): void
    {
        $this->endLedger = $endLedger;
    }

    /**
     * @return EventFilters|null
     */
    public function getFilters(): ?EventFilters
    {
        return $this->filters;
    }

    /**
     * @param EventFilters|null $filters
     */
    public function setFilters(?EventFilters $filters): void
    {
        $this->filters = $filters;
    }

    /**
     * @return PaginationOptions|null
     */
    public function getPaginationOptions(): ?PaginationOptions
    {
        return $this->paginationOptions;
    }

    /**
     * @param PaginationOptions|null $paginationOptions
     */
    public function setPaginationOptions(?PaginationOptions $paginationOptions): void
    {
        $this->paginationOptions = $paginationOptions;
    }

}