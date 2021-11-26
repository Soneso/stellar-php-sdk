<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;

use Soneso\StellarSDK\Responses\Page\PageResponse;
use Soneso\StellarSDK\Responses\Page\PagingLinksResponse;

class OperationsPageResponse extends PageResponse
{
    private PagingLinksResponse $links;
    private OperationsResponse $operations;

    /**
     * @return PagingLinksResponse
     */
    public function getLinks(): PagingLinksResponse
    {
        return $this->links;
    }

    /**
     * @return OperationsResponse
     */
    public function getOperations(): OperationsResponse
    {
        return $this->operations;
    }


    protected function loadFromJson(array $json) : void {

        if (isset($json['_links'])) $this->links = PagingLinksResponse::fromJson($json['_links']);
        if (isset($json['_embedded']['records'])) {
            $this->operations = new OperationsResponse();
            foreach ($json['_embedded']['records'] as $jsonData) {
                $value = OperationResponse::fromJson($jsonData);
                $this->operations->add($value);
            }
        }
    }

    public static function fromJson(array $json) : OperationsPageResponse
    {
        $result = new OperationsPageResponse();
        $result->loadFromJson($json);
        return $result;
    }
}