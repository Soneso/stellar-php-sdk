<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;

use Soneso\StellarSDK\Requests\RequestType;
use Soneso\StellarSDK\Responses\Page\PageResponse;

class OperationsPageResponse extends PageResponse
{
    private OperationsResponse $operations;

    /**
     * @return OperationsResponse
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