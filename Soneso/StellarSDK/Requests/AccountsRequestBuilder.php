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

class AccountsRequestBuilder extends RequestBuilder
{
    private const ASSET_PARAMETER_NAME = "asset";
    private const LIQUIDITY_POOL_PARAMETER_NAME = "liquidity_pool";
    private const SIGNER_PARAMETER_NAME = "signer";
    private const SPONSOR_PARAMETER_NAME = "sponsor";

    public function __construct(Client $httpClient)
    {
        parent::__construct($httpClient, "accounts");
    }

    /**
     * Requests <code>GET /accounts/{account}</code>
     * @param string accountId Public key of the account to fetch
     * @throws HorizonRequestException
     * @see <a href="https://developers.stellar.org/api/resources/accounts/single/">Account Details</a>
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
     * @see <a href="https://developers.stellar.org/docs/data/apis/horizon/api-reference/get-data-by-account-id">Account Data Details</a>
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
     * @see <a href="https://developers.stellar.org/api/resources/accounts/single/">Account Details</a>
     */
    public function streamAccount(string $accountId, ?callable $callback = null)
    {
        $this->setSegments("accounts", $accountId);
        $this->getAndStream($this->buildUrl(), function($rawData) use ($callback) {
            $parsedObject = AccountResponse::fromJson($rawData);
            $callback($parsedObject);
        });
    }

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

    public function forAsset(AssetTypeCreditAlphaNum $asset) : AccountsRequestBuilder { // TODO: allow AssetTypeCreditAlphaNum as soon as implemented

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
     * Requests specific <code>url</code> and returns {@link AccountsPageResponse}.
     * @throws HorizonRequestException
     */
    public function request(string $url) : AccountsPageResponse {
        return parent::executeRequest($url,RequestType::ACCOUNTS_PAGE);
    }

    /**
     *  Build and execute request.
     *  @throws HorizonRequestException
     */
    public function execute() : AccountsPageResponse {
        return $this->request($this->buildUrl());
    }

    /**
     * Sets <code>cursor</code> parameter on the request.
     * A cursor is a value that points to a specific location in a collection of resources.
     * The cursor attribute itself is an opaque value meaning that users should not try to parse it.
     * @see <a href="https://developers.stellar.org/api/introduction/pagination/">Page documentation</a>
     * @param string cursor
     */
    public function cursor(string $cursor) : AccountsRequestBuilder {
        return parent::cursor($cursor);
    }

    /**
     * Sets <code>limit</code> parameter on the request.
     * It defines maximum number of records to return.
     * For range and default values check documentation of the endpoint requested.
     * @param int number maximum number of records to return
     */
    public function limit(int $number) : AccountsRequestBuilder {
        return parent::limit($number);
    }

    /**
     * Sets <code>order</code> parameter on the request.
     * @param string direction "asc" or "desc"
     */
    public function order(string $direction = "asc") : AccountsRequestBuilder {
        return parent::order($direction);
    }
}

