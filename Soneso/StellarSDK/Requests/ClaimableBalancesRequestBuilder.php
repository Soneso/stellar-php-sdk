<?php

namespace Soneso\StellarSDK\Requests;

use GuzzleHttp\Client;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\ClaimableBalances\ClaimableBalanceResponse;
use Soneso\StellarSDK\Responses\ClaimableBalances\ClaimableBalancesPageResponse;

/**
 * Builds requests for the claimable balances endpoint in Horizon
 *
 * This class provides methods to query claimable balances on the Stellar network. Claimable
 * balances represent amounts of an asset that can be claimed by specific accounts under
 * predefined conditions. They are commonly used for payment escrow and conditional transfers.
 *
 * Query Methods:
 * - claimableBalance(): Fetch a single claimable balance by ID
 * - forSponsor(): Filter by sponsor account
 * - forAsset(): Filter by the asset held in the balance
 * - forClaimant(): Filter by accounts that can claim the balance
 *
 * Claimable balances support both BalanceID (starts with "B") and hex formats.
 *
 * Usage Examples:
 *
 * // Get a specific claimable balance
 * $balance = $sdk->claimableBalances()
 *     ->claimableBalance("00000000...");
 *
 * // Get claimable balances for a specific claimant
 * $balances = $sdk->claimableBalances()
 *     ->forClaimant("GDAT5...")
 *     ->execute();
 *
 * // Get claimable balances by sponsor and asset
 * $asset = Asset::createNonNativeAsset("USD", "GBBD...");
 * $balances = $sdk->claimableBalances()
 *     ->forSponsor("GDAT5...")
 *     ->forAsset($asset)
 *     ->execute();
 *
 * @package Soneso\StellarSDK\Requests
 * @see ClaimableBalancesPageResponse For the response format
 * @see https://developers.stellar.org Stellar developer docs Horizon API Claimable Balances endpoint
 */
class ClaimableBalancesRequestBuilder  extends RequestBuilder
{
    private const SPONSOR_PARAMETER_NAME = "sponsor";
    private const ASSET_PARAMETER_NAME = "asset";
    private const CLAIMANT_PARAMETER_NAME = "claimant";

    /**
     * Constructor
     *
     * @param Client $httpClient The HTTP client used for making requests to Horizon
     */
    public function __construct(Client $httpClient)
    {
        parent::__construct($httpClient, "claimable_balances");
    }

    /**
     * The claimable balance details endpoint provides information on a claimable balance.
     * @param string $claimableBalanceId Specifies which claimable balance to load.
     * @return ClaimableBalanceResponse The claimable balance details.
     * @throws HorizonRequestException
     */
    public function claimableBalance(string $claimableBalanceId) : ClaimableBalanceResponse {
        $idHex = $claimableBalanceId;
        if (str_starts_with($idHex, "B")) {
            $idHex = StrKey::decodeClaimableBalanceIdHex($idHex);
        }
        $this->setSegments("claimable_balances", $idHex);
        return parent::executeRequest($this->buildUrl(),RequestType::SINGLE_CLAIMABLE_BALANCE);
    }

    /**
     * Returns all claimable balances sponsored by a given account.
     * @param string $sponsor Account ID of the sponsor.
     * @return ClaimableBalancesRequestBuilder current instance
     */
    public function forSponsor(string $sponsor) : ClaimableBalancesRequestBuilder {
        $this->queryParameters[ClaimableBalancesRequestBuilder::SPONSOR_PARAMETER_NAME] = $sponsor;
        return $this;
    }

    /**
     * Returns all claimable balances which hold a given asset.
     * @param Asset $asset The asset held by the claimable balance.
     * @return ClaimableBalancesRequestBuilder current instance.
     */
    public function forAsset(Asset $asset) : ClaimableBalancesRequestBuilder {
        $this->queryParameters[ClaimableBalancesRequestBuilder::ASSET_PARAMETER_NAME] = Asset::canonicalForm($asset);
        return $this;
    }

    /**
     * Returns all claimable balances which can be claimed by a given account id.
     * @param string $claimant Account ID of the address which can claim the claimable balance.
     * @return ClaimableBalancesRequestBuilder current instance.
     */
    public function forClaimant(string $claimant) : ClaimableBalancesRequestBuilder {
        $this->queryParameters[ClaimableBalancesRequestBuilder::CLAIMANT_PARAMETER_NAME] = $claimant;
        return $this;
    }

    /**
     * Sets <code>cursor</code> parameter on the request.
     * A cursor is a value that points to a specific location in a collection of resources.
     * The cursor attribute itself is an opaque value meaning that users should not try to parse it.
     * @see https://developers.stellar.org Stellar developer docs Page documentation
     * @param string $cursor
     */
    public function cursor(string $cursor) : ClaimableBalancesRequestBuilder {
        return parent::cursor($cursor);
    }

    /**
     * Sets <code>limit</code> parameter on the request.
     * It defines maximum number of records to return.
     * For range and default values check documentation of the endpoint requested.
     * @param int $number Maximum number of records to return
     */
    public function limit(int $number) : ClaimableBalancesRequestBuilder {
        return parent::limit($number);
    }

    /**
     * Sets <code>order</code> parameter on the request.
     * @param string $direction "asc" or "desc"
     */
    public function order(string $direction = "asc") : ClaimableBalancesRequestBuilder {
        return parent::order($direction);
    }
    /**
     * Requests specific <code>url</code> and returns {@link ClaimableBalancesPageResponse}.
     * @throws HorizonRequestException
     */
    public function request(string $url): ClaimableBalancesPageResponse {
        return parent::executeRequest($url, RequestType::CLAIMABLE_BALANCES_PAGE);
    }

    /**
     *  Build and execute request.
     *  @throws HorizonRequestException
     */
    public function execute() : ClaimableBalancesPageResponse {
        return $this->request($this->buildUrl());
    }
}