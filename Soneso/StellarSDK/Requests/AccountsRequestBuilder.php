<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Requests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Responses\Account\AccountResponse;
use Soneso\StellarSDK\Responses\Account\AccountDataValueResponse;
use Soneso\StellarSDK\Responses\Account\AccountsPageResponse;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;

/**
 * Builds requests for the accounts endpoint in Horizon
 *
 * This class provides methods to query accounts on the Stellar network, including
 * fetching individual accounts, querying accounts by various criteria (signer, asset,
 * sponsor, liquidity pool), and streaming account updates in real-time.
 *
 * Query Methods:
 * - forSigner(): Filter accounts by a specific signer
 * - forAsset(): Filter accounts holding a specific asset
 * - forSponsor(): Filter accounts by sponsor
 * - forLiquidityPool(): Filter accounts participating in a liquidity pool
 *
 * Note: Filter methods are mutually exclusive - only one filter can be applied per query.
 *
 * Usage Examples:
 *
 * // Get a single account
 * $account = $sdk->accounts()->account("GDAT5...");
 *
 * // Query accounts by signer with pagination
 * $accounts = $sdk->accounts()
 *     ->forSigner("GDAT5...")
 *     ->limit(50)
 *     ->order("desc")
 *     ->execute();
 *
 * // Query accounts holding a specific asset
 * $asset = Asset::createNonNativeAsset("USD", "GBBD...");
 * $accounts = $sdk->accounts()
 *     ->forAsset($asset)
 *     ->execute();
 *
 * // Stream real-time account updates
 * $sdk->accounts()->streamAccount("GDAT5...", function($account) {
 *     echo "Balance: " . $account->getBalances()->getNativeBalance()->getBalance();
 * });
 *
 * @package Soneso\StellarSDK\Requests
 * @see https://developers.stellar.org/api/resources/accounts Accounts API documentation
 */
class AccountsRequestBuilder extends RequestBuilder
{
    private const ASSET_PARAMETER_NAME = "asset";
    private const LIQUIDITY_POOL_PARAMETER_NAME = "liquidity_pool";
    private const SIGNER_PARAMETER_NAME = "signer";
    private const SPONSOR_PARAMETER_NAME = "sponsor";

    /**
     * Constructs a new AccountsRequestBuilder instance
     *
     * @param Client $httpClient The Guzzle HTTP client for making requests
     */
    public function __construct(Client $httpClient)
    {
        parent::__construct($httpClient, "accounts");
    }

    /**
     * Fetches a single account by its public key
     *
     * Requests GET /accounts/{account}
     *
     * @param string $accountId Public key of the account to fetch (G-address)
     * @return AccountResponse The account details including balances, signers, and data
     * @throws HorizonRequestException If the account does not exist or request fails
     * @see https://developers.stellar.org/api/resources/accounts/single/ Account Details
     */
    public function account(string $accountId) : AccountResponse {
      $this->setSegments("accounts", $accountId);
      return parent::executeRequest($this->buildUrl(),RequestType::SINGLE_ACCOUNT);
    }

    /**
     * Requests <code>GET /accounts/{account}/data/{key}</code>
     * Returns the value of a single data entry for an account.
     * @param string $accountId Public key of the account
     * @param string $key The data entry key to fetch
     * @return AccountDataValueResponse
     * @throws HorizonRequestException
     * @see https://developers.stellar.org/docs/data/apis/horizon/api-reference/get-data-by-account-id Account data details
     */
    public function accountData(string $accountId, string $key) : AccountDataValueResponse {
        $this->setSegments("accounts", $accountId, "data", $key);
        return parent::executeRequest($this->buildUrl(), RequestType::ACCOUNT_DATA_VALUE);
    }

    /**
     * Streams AccountResponse objects for a specific account to $callback
     *
     * This method provides real-time updates by streaming the /accounts/{account_id} endpoint
     * directly. Horizon uses polling-based streaming: it polls the account endpoint every few
     * seconds and sends SSE events when the account state changes.
     *
     * The callback receives AccountResponse objects whenever the account is updated (e.g., after
     * transactions, balance changes, data entries, etc.).
     *
     * $callback should have arguments:
     *  AccountResponse
     *
     * For example:
     *
     * $sdk = StellarSDK::getTestNetInstance();
     * $accountId = "GDQJUTQYK2MQX2VGDR2FYWLIYAQIEGXTQVTFEMGH2BEWFG4BRUY4CKI7";
     * $sdk->accounts()->streamAccount($accountId, function(AccountResponse $account) {
     *     printf('Account %s updated - Sequence: %s, Balance: %s XLM' . PHP_EOL,
     *         $account->getAccountId(),
     *         $account->getSequenceNumber(),
     *         $account->getBalances()->getNativeBalance()->getBalance()
     *     );
     * });
     *
     * @param string $accountId Public key of the account to stream
     * @param callable|null $callback Callback function to receive AccountResponse objects
     * @throws GuzzleException
     * @throws HorizonRequestException
     * @see https://developers.stellar.org/api/resources/accounts/single/ Account details
     */
    public function streamAccount(string $accountId, ?callable $callback = null)
    {
        $this->setSegments("accounts", $accountId);
        $this->getAndStream($this->buildUrl(), function($rawData) use ($callback) {
            $parsedObject = AccountResponse::fromJson($rawData);
            $callback($parsedObject);
        });
    }

    /**
     * Streams AccountDataValueResponse objects for a specific account data entry to $callback
     *
     * This method provides real-time updates by streaming the /accounts/{account_id}/data/{key}
     * endpoint. Horizon uses polling-based streaming: it polls the endpoint every few seconds
     * and sends SSE events when the data value changes.
     *
     * The callback receives AccountDataValueResponse objects whenever the data entry value
     * is updated (e.g., after a MANAGE_DATA operation).
     *
     * $callback should have arguments:
     *  AccountDataValueResponse
     *
     * For example:
     *
     * $sdk = StellarSDK::getTestNetInstance();
     * $accountId = "GDQJUTQYK2MQX2VGDR2FYWLIYAQIEGXTQVTFEMGH2BEWFG4BRUY4CKI7";
     * $key = "config";
     * $sdk->accounts()->streamAccountData($accountId, $key, function(AccountDataValueResponse $data) {
     *     printf('Data value updated: %s' . PHP_EOL, $data->getDecodedValue());
     * });
     *
     * @param string $accountId Public key of the account
     * @param string $key The data entry key to stream
     * @param callable|null $callback Callback function to receive AccountDataValueResponse objects
     * @throws GuzzleException
     * @throws HorizonRequestException
     * @see https://developers.stellar.org/docs/data/apis/horizon/api-reference/get-data-by-account-id Account data details
     */
    public function streamAccountData(string $accountId, string $key, ?callable $callback = null): void
    {
        $this->setSegments("accounts", $accountId, "data", $key);
        $this->getAndStream($this->buildUrl(), function($rawData) use ($callback) {
            $parsedObject = AccountDataValueResponse::fromJson($rawData);
            $callback($parsedObject);
        });
    }

    /**
     * Filters accounts by a specific signer
     *
     * Returns accounts where the specified address is one of the signers.
     * This filter is mutually exclusive with forAsset, forLiquidityPool, and forSponsor.
     *
     * @param string $signer Public key of the signer to filter by (G-address)
     * @return AccountsRequestBuilder This instance for method chaining
     * @throws \RuntimeException If another filter (asset, liquidity_pool, sponsor) is already set
     */
    public function forSigner(string $signer) : AccountsRequestBuilder {

        if (array_key_exists(AccountsRequestBuilder::ASSET_PARAMETER_NAME, $this->queryParameters)) {
            throw new \RuntimeException("cannot set both asset and signer");
        }
        if (array_key_exists(AccountsRequestBuilder::LIQUIDITY_POOL_PARAMETER_NAME, $this->queryParameters)) {
            throw new \RuntimeException("cannot set both liquidity_pool and signer");
        }
        if (array_key_exists(AccountsRequestBuilder::SPONSOR_PARAMETER_NAME, $this->queryParameters)) {
            throw new \RuntimeException("cannot set both sponsor and signer");
        }

        $this->queryParameters[AccountsRequestBuilder::SIGNER_PARAMETER_NAME] = $signer;
        return $this;
    }

    /**
     * Filters accounts by a specific asset they hold
     *
     * Returns accounts that have a trustline for the specified asset.
     * This filter is mutually exclusive with forSigner, forLiquidityPool, and forSponsor.
     *
     * @param AssetTypeCreditAlphaNum $asset The asset to filter by (cannot be native XLM)
     * @return AccountsRequestBuilder This instance for method chaining
     * @throws \RuntimeException If another filter (signer, liquidity_pool, sponsor) is already set
     */
    public function forAsset(AssetTypeCreditAlphaNum $asset) : AccountsRequestBuilder {

        if (array_key_exists(AccountsRequestBuilder::SIGNER_PARAMETER_NAME, $this->queryParameters)) {
            throw new \RuntimeException("cannot set both asset and signer");
        }
        if (array_key_exists(AccountsRequestBuilder::LIQUIDITY_POOL_PARAMETER_NAME, $this->queryParameters)) {
            throw new \RuntimeException("cannot set both liquidity_pool and asset");
        }
        if (array_key_exists(AccountsRequestBuilder::SPONSOR_PARAMETER_NAME, $this->queryParameters)) {
            throw new \RuntimeException("cannot set both sponsor and asset");
        }

        $this->queryParameters[AccountsRequestBuilder::ASSET_PARAMETER_NAME] = Asset::canonicalForm($asset);
        return $this;
    }

    /**
     * Filters accounts by liquidity pool participation
     *
     * Returns accounts that have trustlines to the specified liquidity pool.
     * This filter is mutually exclusive with forSigner, forAsset, and forSponsor.
     *
     * @param string $liquidityPoolId The liquidity pool ID (L-address or hex format)
     * @return AccountsRequestBuilder This instance for method chaining
     * @throws \RuntimeException If another filter (signer, asset, sponsor) is already set
     */
    public function forLiquidityPool(string $liquidityPoolId) : AccountsRequestBuilder {

        if (array_key_exists(AccountsRequestBuilder::SIGNER_PARAMETER_NAME, $this->queryParameters)) {
            throw new \RuntimeException("cannot set both liquidity_pool and signer");
        }
        if (array_key_exists(AccountsRequestBuilder::ASSET_PARAMETER_NAME, $this->queryParameters)) {
            throw new \RuntimeException("cannot set both liquidity_pool and asset");
        }
        if (array_key_exists(AccountsRequestBuilder::SPONSOR_PARAMETER_NAME, $this->queryParameters)) {
            throw new \RuntimeException("cannot set both sponsor and liquidity_pool");
        }

        $idHex = $liquidityPoolId;
        if (str_starts_with($idHex, "L")) {
            $idHex = StrKey::decodeLiquidityPoolIdHex($idHex);
        }
        $this->queryParameters[AccountsRequestBuilder::LIQUIDITY_POOL_PARAMETER_NAME] = $idHex;
        return $this;
    }

    /**
     * Filters accounts by sponsor
     *
     * Returns accounts where the base reserve is being sponsored by the specified account.
     * This filter is mutually exclusive with forSigner, forAsset, and forLiquidityPool.
     *
     * @param string $sponsor Public key of the sponsor account (G-address)
     * @return AccountsRequestBuilder This instance for method chaining
     * @throws \RuntimeException If another filter (signer, asset, liquidity_pool) is already set
     */
    public function forSponsor(string $sponsor) : AccountsRequestBuilder {
        if (array_key_exists(AccountsRequestBuilder::SIGNER_PARAMETER_NAME, $this->queryParameters)) {
            throw new \RuntimeException("cannot set both sponsor and signer");
        }
        if (array_key_exists(AccountsRequestBuilder::ASSET_PARAMETER_NAME, $this->queryParameters)) {
            throw new \RuntimeException("cannot set both sponsor and asset");
        }
        if (array_key_exists(AccountsRequestBuilder::LIQUIDITY_POOL_PARAMETER_NAME, $this->queryParameters)) {
            throw new \RuntimeException("cannot set both sponsor and liquidity_pool");
        }

        $this->queryParameters[AccountsRequestBuilder::SPONSOR_PARAMETER_NAME] = $sponsor;
        return $this;
    }

    /**
     * Requests a specific URL and returns paginated accounts
     *
     * This method is typically used internally for pagination. Use execute() instead
     * for normal queries, or follow pagination links from response objects.
     *
     * @param string $url The complete URL to request
     * @return AccountsPageResponse Paginated list of accounts
     * @throws HorizonRequestException If the request fails
     */
    public function request(string $url) : AccountsPageResponse {
        return parent::executeRequest($url,RequestType::ACCOUNTS_PAGE);
    }

    /**
     * Builds the query URL and executes the request
     *
     * Combines all query parameters and filters to build the final URL, then
     * executes the request and returns paginated account results.
     *
     * @return AccountsPageResponse Paginated list of accounts matching the query
     * @throws HorizonRequestException If the request fails
     */
    public function execute() : AccountsPageResponse {
        return $this->request($this->buildUrl());
    }

    /**
     * Sets the cursor position for pagination
     *
     * A cursor is an opaque value that points to a specific location in a result set.
     * Use this to navigate to a specific page when paginating through results.
     *
     * @param string $cursor The paging token from a previous response
     * @return AccountsRequestBuilder This instance for method chaining
     * @see https://developers.stellar.org/api/introduction/pagination/ Pagination documentation
     */
    public function cursor(string $cursor) : AccountsRequestBuilder {
        return parent::cursor($cursor);
    }

    /**
     * Sets the maximum number of accounts to return
     *
     * Defines the maximum number of records in the response. The default limit
     * varies by endpoint. Maximum allowed is typically 200.
     *
     * @param int $number Maximum number of accounts to return (1-200)
     * @return AccountsRequestBuilder This instance for method chaining
     */
    public function limit(int $number) : AccountsRequestBuilder {
        return parent::limit($number);
    }

    /**
     * Sets the sort order for results
     *
     * Determines whether results are sorted in ascending or descending order.
     * For accounts, ordering is typically by creation time or ID.
     *
     * @param string $direction Sort direction: "asc" for ascending, "desc" for descending
     * @return AccountsRequestBuilder This instance for method chaining
     */
    public function order(string $direction = "asc") : AccountsRequestBuilder {
        return parent::order($direction);
    }
}

