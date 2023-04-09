<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Requests;

/**
 * Part of the getEvents() request
 * See: https://soroban.stellar.org/api/methods/getEvents
 * See: https://soroban.stellar.org/api/pagination
 */
class PaginationOptions
{
    public ?string $cursor = null;
    public ?int $limit = null;

    /**
     * @param string|null $cursor
     * @param int|null $limit
     */
    public function __construct(?string $cursor = null, ?int $limit = null)
    {
        $this->cursor = $cursor;
        $this->limit = $limit;
    }

    public function getRequestParams() : array {
        $params = array();
        if ($this->cursor != null) {
            $params['cursor'] = $this->cursor;
        }
        if ($this->limit != null) {
            $params['limit'] = $this->limit;
        }
        return $params;
    }

    /**
     * @return string|null
     */
    public function getCursor(): ?string
    {
        return $this->cursor;
    }

    /**
     * @param string|null $cursor
     */
    public function setCursor(?string $cursor): void
    {
        $this->cursor = $cursor;
    }

    /**
     * @return int|null
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * @param int|null $limit
     */
    public function setLimit(?int $limit): void
    {
        $this->limit = $limit;
    }

}