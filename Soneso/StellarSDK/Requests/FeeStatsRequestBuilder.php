<?php

namespace Soneso\StellarSDK\Requests;

use GuzzleHttp\Client;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\FeeStats\FeeStatsResponse;
use Soneso\StellarSDK\Responses\Response;

class FeeStatsRequestBuilder extends RequestBuilder
{

    public function __construct(Client $httpClient) {
        parent::__construct($httpClient);
    }

    /**
     * @return FeeStatsResponse
     * @throws HorizonRequestException
     */
    public function getFeeStats() : FeeStatsResponse {
        $this->setSegments("fee_stats");
        return parent::executeRequest($this->buildUrl(),RequestType::FEE_STATS);
    }

    /**
     *  Build and execute request.
     *  @throws HorizonRequestException
     */
    public function execute() : Response {
        throw new HorizonRequestException("not supported");
    }
}