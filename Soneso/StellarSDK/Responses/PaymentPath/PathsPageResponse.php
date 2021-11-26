<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\PaymentPath;

use Soneso\StellarSDK\Responses\Page\PageResponse;
use Soneso\StellarSDK\Responses\Page\PagingLinksResponse;

class PathsPageResponse extends PageResponse
{
    private PagingLinksResponse $links;
    private PathsResponse $paths;

    /**
     * @return PagingLinksResponse
     */
    public function getLinks(): PagingLinksResponse
    {
        return $this->links;
    }

    /**
     * @return PathsResponse
     */
    public function getPaths(): PathsResponse
    {
        return $this->paths;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['_links'])) $this->links = PagingLinksResponse::fromJson($json['_links']);
        if (isset($json['_embedded']['records'])) {
            $this->paths = new PathsResponse();
            foreach ($json['_embedded']['records'] as $jsonValue) {
                $value = PathResponse::fromJson($jsonValue);
                $this->paths->add($value);
            }
        }
    }

    public static function fromJson(array $json) : PathsPageResponse
    {
        $result = new PathsPageResponse();
        $result->loadFromJson($json);
        return $result;
    }
}