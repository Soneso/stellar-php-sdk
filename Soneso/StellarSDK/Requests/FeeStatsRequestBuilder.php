<?php

namespace Soneso\StellarSDK\Requests;

use GuzzleHttp\Client;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\FeeStats\FeeStatsResponse;
use Soneso\StellarSDK\Responses\Response;

/**
 * Builds requests for the fee stats endpoint in Horizon
 *
 * This class provides access to network fee statistics, which include information about
 * recent transaction fees on the Stellar network. Fee stats help users determine
 * appropriate fee levels for transaction submission to ensure timely processing.
 *
 * The fee stats endpoint returns percentile-based fee recommendations for different
 * transaction priorities (high, medium, low) based on recent ledger activity.
 *
 * Usage Example:
 *
 * // Get current network fee statistics
 * $feeStats = $sdk->feeStats()->getFeeStats();
 * echo "Max fee: " . $feeStats->getMaxFee()->getFeeCharged() . PHP_EOL;
 * echo "Mode fee: " . $feeStats->getFeeCharged()->getMode() . PHP_EOL;
 *
 * @package Soneso\StellarSDK\Requests
 * @see FeeStatsResponse For the response format
 * @see https://developers.stellar.org/api/aggregations/fee-stats Horizon API Fee Stats endpoint
 */
class FeeStatsRequestBuilder extends RequestBuilder
{
    /**
     * Constructor
     *
     * @param Client $httpClient The HTTP client used for making requests to Horizon
     */
    public function __construct(Client $httpClient) {
        parent::__construct($httpClient);
    }

    /**
     * Retrieve current network fee statistics
     *
     * Returns statistics about accepted transaction fees over recent ledgers, including
     * minimum, mode, and various percentile values.
     *
     * @return FeeStatsResponse The current fee statistics
     * @throws HorizonRequestException When the request fails
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