<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\PaymentPath;

use Soneso\StellarSDK\Requests\RequestType;
use Soneso\StellarSDK\Responses\Page\PageResponse;

/**
 * Paginated collection of payment paths from Horizon API
 *
 * This response represents a single page of payment paths returned by Horizon's path finding
 * endpoints. Each page contains a collection of possible routes for converting one asset to
 * another through the Stellar network, along with pagination links to navigate through the
 * complete result set.
 *
 * Path finding enables path payments where the sender delivers one asset while the recipient
 * receives a different asset. Horizon calculates optimal conversion routes considering available
 * offers, liquidity pools, and intermediate assets. The response follows Horizon's standard
 * pagination pattern with cursor-based navigation.
 *
 * Returned by Horizon endpoints:
 * - GET /paths/strict-receive - Find paths for receiving a specific destination amount
 * - GET /paths/strict-send - Find paths for sending a specific source amount
 *
 * @package Soneso\StellarSDK\Responses\PaymentPath
 * @see PageResponse For pagination functionality
 * @see PathsResponse For the collection of paths in this page
 * @see PathResponse For individual path details
 * @see https://developers.stellar.org Stellar developer docs
 */
class PathsPageResponse extends PageResponse
{
    private PathsResponse $paths;

    /**
     * Gets the collection of payment paths in this page
     *
     * @return PathsResponse The iterable collection of path records
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

    /**
     * Creates a PathsPageResponse from JSON data
     *
     * @param array $json Associative array of parsed JSON response
     * @return PathsPageResponse The populated page response
     */
    public static function fromJson(array $json) : PathsPageResponse {
        $result = new PathsPageResponse();
        $result->loadFromJson($json);
        return $result;
    }

    /**
     * Fetches the next page of payment paths
     *
     * @return PathsPageResponse|null The next page or null if no next page exists
     */
    public function getNextPage(): PathsPageResponse | null {
        return $this->executeRequest(RequestType::PATHS_PAGE, $this->getNextPageUrl());
    }

    /**
     * Fetches the previous page of payment paths
     *
     * @return PathsPageResponse|null The previous page or null if no previous page exists
     */
    public function getPreviousPage(): PathsPageResponse | null {
        return $this->executeRequest(RequestType::PATHS_PAGE, $this->getPrevPageUrl());
    }
}
