<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;

use Soneso\StellarSDK\Requests\RequestType;
use Soneso\StellarSDK\Responses\Page\PageResponse;

/**
 * Paginated collection of operations from Horizon API
 *
 * Represents a page of operation results with embedded records and navigation links.
 * Extends PageResponse to provide cursor-based pagination for traversing operation history.
 * Supports retrieving next and previous pages of operations.
 *
 * @package Soneso\StellarSDK\Responses\Operations
 */
class OperationsPageResponse extends PageResponse
{
    private OperationsResponse $operations;

    /**
     * Gets the collection of operations in this page
     *
     * @return OperationsResponse Iterable collection of operation responses
     */
    public function getOperations(): OperationsResponse {
        return $this->operations;
    }


    protected function loadFromJson(array $json) : void {
        parent::loadFromJson($json);
        if (isset($json['_embedded']['records'])) {
            $this->operations = new OperationsResponse();
            foreach ($json['_embedded']['records'] as $jsonData) {
                $value = OperationResponse::fromJson($jsonData);
                $this->operations->add($value);
            }
        }
    }

    public static function fromJson(array $json) : OperationsPageResponse {
        $result = new OperationsPageResponse();
        $result->loadFromJson($json);
        return $result;
    }

    public function getNextPage(): OperationsPageResponse | null {
        return $this->executeRequest(RequestType::OPERATIONS_PAGE, $this->getNextPageUrl());
    }

    public function getPreviousPage(): OperationsPageResponse | null {
        return $this->executeRequest(RequestType::OPERATIONS_PAGE, $this->getPrevPageUrl());
    }
}