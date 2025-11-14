<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Page;

use Soneso\StellarSDK\Responses\Link\LinkResponse;

/**
 * Represents pagination navigation links in Horizon API collection responses
 *
 * Horizon returns collections (accounts, transactions, operations, etc.) in paginated
 * format to manage large result sets efficiently. This response object contains the
 * hypermedia links needed to navigate between pages of results.
 *
 * Links provided:
 * - self: URL for the current page
 * - next: URL for the next page (may be null if on last page)
 * - prev: URL for the previous page (may be null if on first page)
 *
 * These links include the proper cursor and limit parameters, allowing seamless
 * navigation through result sets without manually constructing URLs. The SDK's
 * PageResponse classes use these links internally when calling getNextPage() and
 * getPreviousPage() methods.
 *
 * @package Soneso\StellarSDK\Responses\Page
 * @see PageResponse For paginated collection base class
 * @see LinkResponse For individual link structure
 * @see https://developers.stellar.org Stellar developer docs Horizon Pagination
 */
class PagingLinksResponse
{
    private LinkResponse $self;
    private ?LinkResponse $next;
    private ?LinkResponse $prev;

    /**
     * Gets the link to the current page
     *
     * This link represents the URL that was used to fetch the current page of results,
     * including all query parameters like cursor, order, and limit.
     *
     * @return LinkResponse The self link with href and optional templated flag
     */
    public function getSelf(): LinkResponse
    {
        return $this->self;
    }

    /**
     * Gets the link to the next page of results
     *
     * This link points to the next page in the collection. It will be null if the
     * current page is the last page. The link includes the appropriate cursor value
     * to fetch the next set of records.
     *
     * @return LinkResponse|null The next page link, or null if on last page
     */
    public function getNext(): ?LinkResponse
    {
        return $this->next;
    }

    /**
     * Gets the link to the previous page of results
     *
     * This link points to the previous page in the collection. It will be null if the
     * current page is the first page. The link includes the appropriate cursor value
     * to fetch the previous set of records.
     *
     * @return LinkResponse|null The previous page link, or null if on first page
     */
    public function getPrev(): ?LinkResponse
    {
        return $this->prev;
    }

    /**
     * Populates the object from parsed JSON data
     *
     * @param array $json Associative array of pagination links from API response
     * @return void
     */
    protected function loadFromJson(array $json) : void {

        if (isset($json['self'])) $this->self = LinkResponse::fromJson($json['self']);
        if (isset($json['next'])) $this->next = LinkResponse::fromJson($json['next']);
        if (isset($json['prev'])) $this->prev = LinkResponse::fromJson($json['prev']);
    }

    /**
     * Creates a PagingLinksResponse from JSON data
     *
     * @param array $json Associative array of pagination links from API response
     * @return PagingLinksResponse The populated pagination links object
     */
    public static function fromJson(array $json) : PagingLinksResponse {
        $result = new PagingLinksResponse();
        $result->loadFromJson($json);
        return $result;
    }
}