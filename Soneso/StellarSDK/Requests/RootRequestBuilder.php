<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Requests;

use GuzzleHttp\Client;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\Response;
use Soneso\StellarSDK\Responses\Root\RootResponse;

class RootRequestBuilder extends RequestBuilder
{

    public function __construct(Client $httpClient) {
        parent::__construct($httpClient);
    }

    /**
     * Requests specific <code>url</code> and returns {@link RootResponse}.
     * @throws HorizonRequestException
     */
    public function getRoot(string $url) : RootResponse {
        return parent::executeRequest($url,RequestType::ROOT);
    }

    /**
     *  Build and execute request.
     *  @throws HorizonRequestException
     */
    public function execute() : Response {
        throw new HorizonRequestException("not supported");
    }
}