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

abstract class PageResponse extends Response
{
    private PagingLinksResponse $links;

    /**
     * @return PagingLinksResponse
     */
    public function getLinks(): PagingLinksResponse
    {
        return $this->links;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['_links'])) $this->links = PagingLinksResponse::fromJson($json['_links']);
    }

    public function hasNextPage() : bool {

        if ($this->links->getNext()?->getHref()) {
            return true;
        }
        return false;
    }

    public function hasPrevPage() : bool {

        if ($this->links->getPrev()?->getHref()) {
            return true;
        }
        return false;
    }

    public abstract function getNextPage() : PageResponse | null;
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