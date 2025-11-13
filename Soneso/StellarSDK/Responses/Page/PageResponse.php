<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Page;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Requests\RequestBuilder;
use Soneso\StellarSDK\Responses\Response;
use Soneso\StellarSDK\Responses\ResponseHandler;

/**
 * Abstract base class for paginated collection responses from Horizon API
 *
 * Horizon returns collections in pages for efficient data transfer. This base class provides
 * common pagination functionality including navigation links, page state checking, and methods
 * to fetch next/previous pages. All paginated collection responses extend this class.
 *
 * Key features:
 * - Navigation links for next, previous, and self pages
 * - Helper methods to check if next/previous pages exist
 * - Abstract methods to fetch next/previous pages (implemented by subclasses)
 * - Support for both forward and backward pagination
 *
 * @package Soneso\StellarSDK\Responses\Page
 * @see PagingLinksResponse For pagination link details
 * @see https://developers.stellar.org Stellar developer docs Horizon Pagination
 * @since 1.0.0
 */
abstract class PageResponse extends Response
{
    private PagingLinksResponse $links;

    /**
     * Gets the pagination links for navigating between pages
     *
     * @return PagingLinksResponse The navigation links (next, prev, self)
     */
    public function getLinks(): PagingLinksResponse
    {
        return $this->links;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['_links'])) $this->links = PagingLinksResponse::fromJson($json['_links']);
    }

    /**
     * Checks if a next page exists
     *
     * @return bool True if there is a next page available
     */
    public function hasNextPage() : bool {

        if ($this->links->getNext()?->getHref()) {
            return true;
        }
        return false;
    }

    /**
     * Checks if a previous page exists
     *
     * @return bool True if there is a previous page available
     */
    public function hasPrevPage() : bool {

        if ($this->links->getPrev()?->getHref()) {
            return true;
        }
        return false;
    }

    /**
     * Fetches the next page of results
     *
     * @return PageResponse|null The next page or null if no next page exists
     */
    public abstract function getNextPage() : PageResponse | null;

    /**
     * Fetches the previous page of results
     *
     * @return PageResponse|null The previous page or null if no previous page exists
     */
    public abstract function getPreviousPage() : PageResponse | null;

    protected function getNextPageUrl() : string | null {
        return $this->links->getNext()?->getHref();
    }
    protected function getPrevPageUrl() : string | null {
        return $this->links->getPrev()?->getHref();
    }

    /**
     * @throws HorizonRequestException
     */
    protected function executeRequest(string $requestType, ?string $url = null) : Response | null {
        if (!$url) {
            return null;
        }

        $requestMethod = "GET";
        $response = null;
        try {
            $request = new Request($requestMethod, $url, RequestBuilder::HEADERS);
            $response = $this->httpClient->send($request);
        }
        catch (GuzzleException $e) {
            throw HorizonRequestException::fromOtherException($url, $requestMethod, $e, $response);
        }
        $responseHandler = new ResponseHandler();
        try {
            return $responseHandler->handleResponse($response, $requestType, $this->httpClient);
        } catch (\Exception $e) {
            throw HorizonRequestException::fromOtherException($url, $requestMethod, $e, $response);
        }
    }
}