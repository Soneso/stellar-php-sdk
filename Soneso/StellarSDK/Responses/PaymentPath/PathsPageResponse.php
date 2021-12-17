<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\PaymentPath;

use Soneso\StellarSDK\Requests\RequestType;
use Soneso\StellarSDK\Responses\Page\PageResponse;

class PathsPageResponse extends PageResponse
{
    private PathsResponse $paths;

    /**
     * @return PathsResponse
     */
    public function getPaths(): PathsResponse {
        return $this->paths;
    }

    protected function loadFromJson(array $json) : void {
        parent::loadFromJson($json);
        if (isset($json['_embedded']['records'])) {
            $this->paths = new PathsResponse();
            foreach ($json['_embedded']['records'] as $jsonValue) {
                $value = PathResponse::fromJson($jsonValue);
                $this->paths->add($value);
            }
        }
    }

    public static function fromJson(array $json) : PathsPageResponse {
        $result = new PathsPageResponse();
        $result->loadFromJson($json);
        return $result;
    }

    public function getNextPage(): PathsPageResponse | null {
        return $this->executeRequest(RequestType::PATHS_PAGE, $this->getNextPageUrl());
    }

    public function getPreviousPage(): PathsPageResponse | null {
        return $this->executeRequest(RequestType::PATHS_PAGE, $this->getPrevPageUrl());
    }
}