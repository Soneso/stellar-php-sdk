<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;


use GuzzleHttp\Client;
use Soneso\StellarSDK\Constants\NetworkConstants;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Requests\AccountsRequestBuilder;
use Soneso\StellarSDK\Requests\AssetsRequestBuilder;
use Soneso\StellarSDK\Requests\ClaimableBalancesRequestBuilder;
use Soneso\StellarSDK\Requests\EffectsRequestBuilder;
use Soneso\StellarSDK\Requests\FeeStatsRequestBuilder;
use Soneso\StellarSDK\Requests\FindPathsRequestBuilder;
use Soneso\StellarSDK\Requests\HealthRequestBuilder;
use Soneso\StellarSDK\Requests\LedgersRequestBuilder;
use Soneso\StellarSDK\Requests\LiquidityPoolsRequestBuilder;
use Soneso\StellarSDK\Requests\OffersRequestBuilder;
use Soneso\StellarSDK\Requests\OperationsRequestBuilder;
use Soneso\StellarSDK\Requests\OrderBookRequestBuilder;
use Soneso\StellarSDK\Requests\PaymentsRequestBuilder;
use Soneso\StellarSDK\Requests\RootRequestBuilder;
use Soneso\StellarSDK\Requests\StrictReceivePathsRequestBuilder;
use Soneso\StellarSDK\Requests\StrictSendPathsRequestBuilder;
use Soneso\StellarSDK\Requests\SubmitAsyncTransactionRequestBuilder;
use Soneso\StellarSDK\Requests\SubmitTransactionRequestBuilder;
use Soneso\StellarSDK\Requests\TradeAggregationsRequestBuilder;
use Soneso\StellarSDK\Requests\TradesRequestBuilder;
use Soneso\StellarSDK\Requests\TransactionsRequestBuilder;
use Soneso\StellarSDK\Responses\Account\AccountResponse;
use Soneso\StellarSDK\Responses\ClaimableBalances\ClaimableBalanceResponse;
use Soneso\StellarSDK\Responses\FeeStats\FeeStatsResponse;
use Soneso\StellarSDK\Responses\Health\HealthResponse;
use Soneso\StellarSDK\Responses\Ledger\LedgerResponse;
use Soneso\StellarSDK\Responses\LiquidityPools\LiquidityPoolResponse;
use Soneso\StellarSDK\Responses\Offers\OfferResponse;
use Soneso\StellarSDK\Responses\Operations\OperationResponse;
use Soneso\StellarSDK\Responses\Root\RootResponse;
use Soneso\StellarSDK\Responses\Transaction\SubmitAsyncTransactionResponse;
use Soneso\StellarSDK\Responses\Transaction\SubmitTransactionResponse;
use Soneso\StellarSDK\Responses\Transaction\TransactionResponse;

/**
 * Main entry point for interacting with Stellar Horizon servers
 *
 * This class provides a high-level interface for accessing Stellar network data and
 * submitting transactions through a Horizon server. It manages HTTP client connections,
 * provides convenient access to all Horizon API endpoints, and includes helper methods
 * for common operations like checking account existence and validating memo requirements.
 *
 * Usage:
 * <code>
 * // Connect to public network
 * $sdk = StellarSDK::getPublicNetInstance();
 *
 * // Or connect to testnet
 * $sdk = StellarSDK::getTestNetInstance();
 *
 * // Or connect to a custom Horizon server
 * $sdk = new StellarSDK("https://horizon-custom.example.com");
 *
 * // Request account details
 * $account = $sdk->requestAccount("GABC...");
 *
 * // Submit a transaction
 * $response = $sdk->submitTransaction($transaction);
 * </code>
 *
 * @package Soneso\StellarSDK
 * @see https://developers.stellar.org Stellar developer docs Horizon API Documentation
 * @since 1.0.0
 */
class StellarSDK
{
    
    public const VERSION_NR = "1.9.3";
    public static string $PUBLIC_NET_HORIZON_URL = "https://horizon.stellar.org";
    public static string $TEST_NET_HORIZON_URL = "https://horizon-testnet.stellar.org";
    public static string $FUTURE_NET_HORIZON_URL = "https://horizon-futurenet.stellar.org";
    
    private static ?StellarSDK $publicNetInstance = null;
    private static ?StellarSDK $testnetInstance = null;
    private static ?StellarSDK $futurenetInstance = null;
    
    private string $serverUri;
    private Client $httpClient;
        
    /**
     * Returns a singleton instance connected to the Stellar public network
     *
     * This is the main production Stellar network. Use this for real transactions
     * involving actual assets with value.
     *
     * @return StellarSDK Singleton instance configured for the public network
     */
    public static function getPublicNetInstance() : StellarSDK
    {
        if (!self::$publicNetInstance) {
            self::$publicNetInstance = new StellarSDK(self::$PUBLIC_NET_HORIZON_URL);
        }
        return self::$publicNetInstance;
    }
    
    /**
     * Returns a singleton instance connected to the Stellar test network
     *
     * Use this network for testing and development. Accounts can be funded using
     * the friendbot service and testnet assets have no real-world value.
     *
     * @return StellarSDK Singleton instance configured for the test network
     * @see https://developers.stellar.org Stellar developer docs
     */
    public static function getTestNetInstance() : StellarSDK
    {
        if (!self::$testnetInstance) {
            self::$testnetInstance = new StellarSDK(self::$TEST_NET_HORIZON_URL);
        }
        return self::$testnetInstance;
    }

    /**
     * Returns a singleton instance connected to the Stellar future network
     *
     * This network is used for testing upcoming protocol features before they are
     * released to testnet or the public network. Use with caution as this network
     * may undergo frequent resets.
     *
     * @return StellarSDK Singleton instance configured for the future network
     * @see https://developers.stellar.org Stellar developer docs
     */
    public static function getFutureNetInstance() : StellarSDK
    {
        if (!self::$futurenetInstance) {
            self::$futurenetInstance = new StellarSDK(self::$FUTURE_NET_HORIZON_URL);
        }
        return self::$futurenetInstance;
    }
    
    /**
     * Creates a new StellarSDK instance connected to the specified Horizon server
     *
     * @param string $uri The base URI of the Horizon server (e.g., "https://horizon.stellar.org")
     */
    public function __construct(string $uri)
    {
        $this->serverUri = $uri;
        $this->httpClient = new Client([
            'base_uri' => $uri,
        ]);
    }
    
    /**
     * Returns the underlying Guzzle HTTP client used for Horizon requests
     *
     * @return Client The configured HTTP client
     */
    public function getHttpClient() : Client {
        return $this->httpClient;
    }

    /**
     * Sets a custom Guzzle HTTP client for Horizon requests
     *
     * Use this to configure custom timeout, proxy, or middleware settings.
     *
     * @param Client $httpClient The HTTP client to use for subsequent requests
     */
    public function setHttpClient(Client $httpClient) : void {
        $this->httpClient = $httpClient;
    }

    /**
     * Requests root information from the Horizon server
     *
     * The root endpoint provides metadata about the Horizon server including
     * supported protocol versions, network passphrase, and available endpoints.
     *
     * @return RootResponse Server metadata and capabilities
     * @throws HorizonRequestException If the request fails
     */
    public function root() : RootResponse {
        $requestBuilder = new RootRequestBuilder($this->httpClient);
        return $requestBuilder->getRoot($this->serverUri);
    }

    /**
     * Requests the health status of the Horizon server
     *
     * Returns health information including whether the Horizon server is in sync with
     * the Stellar network and ready to serve requests.
     *
     * @return HealthResponse Current health status of the Horizon server
     * @throws HorizonRequestException If the request fails
     */
    public function health() : HealthResponse {
        $requestBuilder = new HealthRequestBuilder($this->httpClient);
        return $requestBuilder->getHealth();
    }

    /**
     * Returns a request builder for querying account data
     *
     * @return AccountsRequestBuilder Builder for constructing account queries
     */
    public function accounts() : AccountsRequestBuilder {
        return new AccountsRequestBuilder($this->httpClient);
    }

    /**
     * Returns a request builder for querying asset data
     *
     * @return AssetsRequestBuilder Builder for constructing asset queries
     */
    public function assets() : AssetsRequestBuilder {
        return new AssetsRequestBuilder($this->httpClient);
    }

    /**
     * Returns a request builder for querying ledger data
     *
     * @return LedgersRequestBuilder Builder for constructing ledger queries
     */
    public function ledgers() : LedgersRequestBuilder {
        return new LedgersRequestBuilder($this->httpClient);
    }

    /**
     * Returns a request builder for querying transaction data
     *
     * @return TransactionsRequestBuilder Builder for constructing transaction queries
     */
    public function transactions() : TransactionsRequestBuilder {
        return new TransactionsRequestBuilder($this->httpClient);
    }

    /**
     * Returns a request builder for querying trade data
     *
     * @return TradesRequestBuilder Builder for constructing trade queries
     */
    public function trades() : TradesRequestBuilder {
        return new TradesRequestBuilder($this->httpClient);
    }

    /**
     * Returns a request builder for querying fee statistics
     *
     * @return FeeStatsRequestBuilder Builder for constructing fee stats queries
     */
    public function feeStats() : FeeStatsRequestBuilder {
        return new FeeStatsRequestBuilder($this->httpClient);
    }

    /**
     * Returns a request builder for querying claimable balances
     *
     * @return ClaimableBalancesRequestBuilder Builder for constructing claimable balance queries
     */
    public function claimableBalances() : ClaimableBalancesRequestBuilder {
        return new ClaimableBalancesRequestBuilder($this->httpClient);
    }

    /**
     * Returns a request builder for querying offers
     *
     * @return OffersRequestBuilder Builder for constructing offer queries
     */
    public function offers() : OffersRequestBuilder {
        return new OffersRequestBuilder($this->httpClient);
    }

    /**
     * Returns a request builder for querying order book data
     *
     * @return OrderBookRequestBuilder Builder for constructing order book queries
     */
    public function orderBook() : OrderBookRequestBuilder {
        return new OrderBookRequestBuilder($this->httpClient);
    }

    /**
     * Returns a request builder for querying operation data
     *
     * @return OperationsRequestBuilder Builder for constructing operation queries
     */
    public function operations() : OperationsRequestBuilder {
        return new OperationsRequestBuilder($this->httpClient);
    }

    /**
     * Returns a request builder for querying payment operations
     *
     * @return PaymentsRequestBuilder Builder for constructing payment queries
     */
    public function payments() : PaymentsRequestBuilder {
        return new PaymentsRequestBuilder($this->httpClient);
    }

    /**
     * Returns a request builder for querying account effects
     *
     * @return EffectsRequestBuilder Builder for constructing effect queries
     */
    public function effects() : EffectsRequestBuilder {
        return new EffectsRequestBuilder($this->httpClient);
    }

    /**
     * Returns a request builder for querying aggregated trade data
     *
     * @return TradeAggregationsRequestBuilder Builder for constructing trade aggregation queries
     */
    public function tradeAggregations() : TradeAggregationsRequestBuilder {
        return new TradeAggregationsRequestBuilder($this->httpClient);
    }

    /**
     * Returns a request builder for querying liquidity pools
     *
     * @return LiquidityPoolsRequestBuilder Builder for constructing liquidity pool queries
     */
    public function liquidityPools() : LiquidityPoolsRequestBuilder {
        return new LiquidityPoolsRequestBuilder($this->httpClient);
    }

    /**
     * Returns a request builder for finding payment paths
     *
     * @return FindPathsRequestBuilder Builder for finding payment paths between assets
     */
    public function findPaths() : FindPathsRequestBuilder {
        return new FindPathsRequestBuilder($this->httpClient);
    }

    /**
     * Returns a request builder for finding strict send payment paths
     *
     * @return StrictSendPathsRequestBuilder Builder for finding paths with fixed source amount
     */
    public function findStrictSendPaths() : StrictSendPathsRequestBuilder {
        return new StrictSendPathsRequestBuilder($this->httpClient);
    }

    /**
     * Returns a request builder for finding strict receive payment paths
     *
     * @return StrictReceivePathsRequestBuilder Builder for finding paths with fixed destination amount
     */
    public function findStrictReceivePaths() : StrictReceivePathsRequestBuilder {
        return new StrictReceivePathsRequestBuilder($this->httpClient);
    }

    /**
     * Requests account details from Horizon for the specified account ID
     *
     * @param string $accountId The account ID (public key starting with G) to query
     * @return AccountResponse Account details including balances, sequence number, and signers
     * @throws HorizonRequestException If the account is not found or the request fails
     */
    public function requestAccount(string $accountId) : AccountResponse {
        return $this->accounts()->account($accountId);
    }

    /**
     * Checks if an account exists on the Stellar network
     *
     * This is a convenience method that returns false if the account is not found
     * instead of throwing an exception.
     *
     * @param string $accountId The account ID (public key) to check
     * @return bool True if the account exists, false otherwise
     * @throws HorizonRequestException If the request fails for reasons other than account not found
     */
    public function accountExists(string $accountId) : bool {
        try {
            $account = $this->requestAccount($accountId);
        } catch (HorizonRequestException $e) {
            if ($e->getStatusCode() == NetworkConstants::HTTP_NOT_FOUND) {
                return false;
            }
            throw $e;
        }
        return true;
    }

    /**
     * Requests ledger details from Horizon for the specified ledger sequence
     *
     * @param string $ledgerSeq The ledger sequence number to query
     * @return LedgerResponse Ledger details including transaction count and close time
     * @throws HorizonRequestException If the ledger is not found or the request fails
     */
    public function requestLedger(string $ledgerSeq) : LedgerResponse {
        return $this->ledgers()->ledger($ledgerSeq);
    }

    /**
     * Requests transaction details from Horizon for the specified transaction hash
     *
     * @param string $transactionId The transaction hash to query
     * @return TransactionResponse Transaction details including operations and result
     * @throws HorizonRequestException If the transaction is not found or the request fails
     */
    public function requestTransaction(string $transactionId) : TransactionResponse {
        return $this->transactions()->transaction($transactionId);
    }

    /**
     * Requests current fee statistics from Horizon
     *
     * Fee stats provide information about recent transaction fees including minimum,
     * maximum, and percentile-based fee recommendations for different priority levels.
     *
     * @return FeeStatsResponse Current network fee statistics
     * @throws HorizonRequestException If the request fails
     */
    public function  requestFeeStats() : FeeStatsResponse {
        return $this->feeStats()->getFeeStats();
    }

    /**
     * Requests the claimable balance details from horizon for the given claimable balance id
     *
     * @param string $claimableBalanceId The id of the claimable balance to request.
     * @return ClaimableBalanceResponse The claimable balance details.
     * @throws HorizonRequestException
     */
    public function requestClaimableBalance(string $claimableBalanceId) : ClaimableBalanceResponse {
        return $this->claimableBalances()->claimableBalance($claimableBalanceId);
    }

    /**
     * Requests the offer details from horizon for the given offer id.
     *
     * @param string $offerId The id of the offer to request.
     * @return OfferResponse The details of the offer.
     * @throws HorizonRequestException
     */
    public function requestOffer(string $offerId) : OfferResponse {
        return $this->offers()->offer($offerId);
    }

    /**
     * Requests the liquidity pool details from horizon for the given pool id.
     *
     * @param string $poolId The id of the pool to request.
     * @return LiquidityPoolResponse The details of the pool.
     * @throws HorizonRequestException
     */
    public function requestLiquidityPool(string $poolId) : LiquidityPoolResponse {
        return $this->liquidityPools()->forPoolId($poolId);
    }

    /**
     * Requests the operation details for the given operation id.
     * @param string $operationId The operation id for the request.
     * @return OperationResponse The operation details.
     * @throws HorizonRequestException
     */
    public function requestOperation(string $operationId) : OperationResponse {
        return $this->operations()->operation($operationId);
    }

    /**
     * Submits a synchronous transaction to the network. Unlike the asynchronous version 'submitAsyncTransaction',
     * which relays the response from core directly back to the user, this endpoint blocks and waits for the transaction
     * to be ingested in Horizon.
     *
     * @param AbstractTransaction $transaction the transaction to be submitted.
     * @return SubmitTransactionResponse the response received from Horizon.
     * @throws HorizonRequestException if there was a problem, such as an error response from Horizon. The details of the problem can be found within the exception object.
     */
    public function submitTransaction(AbstractTransaction $transaction) : SubmitTransactionResponse {
        $builder = new SubmitTransactionRequestBuilder($this->httpClient);
        $builder->setTransaction($transaction);
        return $builder->execute();
    }

    /**
     * Submits a synchronous transaction envelope xdr base 64 string to the network.
     * Unlike the asynchronous version 'submitAsyncTransactionEnvelopeXdrBase64',
     * which relays the response from core directly back to the user, this endpoint blocks and waits for the transaction
     * to be ingested in Horizon.
     * @param string $transactionEnvelopeXdrBase64 transaction envelope xdr base 64 string to be submitted to the network.
     * @return SubmitTransactionResponse the response received from Horizon.
     * @throws HorizonRequestException if there was a problem, such as an error response from Horizon. The details of the problem can be found within the exception object.
     */
    public function submitTransactionEnvelopeXdrBase64(string $transactionEnvelopeXdrBase64) : SubmitTransactionResponse {
        $builder = new SubmitTransactionRequestBuilder($this->httpClient);
        $builder->setTransactionEnvelopeXdrBase64($transactionEnvelopeXdrBase64);
        return $builder->execute();
    }

    /**
     * Submits an asynchronous transaction to the network. Unlike the synchronous version 'submitTransaction',
     * which blocks and waits for the transaction to be ingested in Horizon, this endpoint relays the response from
     * core directly back to the user.
     *
     * @param AbstractTransaction $transaction the transaction to be submitted.
     * @return SubmitAsyncTransactionResponse the response received from Horizon.
     * @throws HorizonRequestException if there was a problem, such as an error response from Horizon. The details of the problem can be found within the exception object.
     */
    public function submitAsyncTransaction(AbstractTransaction $transaction) : SubmitAsyncTransactionResponse {
        $builder = new SubmitAsyncTransactionRequestBuilder($this->httpClient);
        $builder->setTransaction($transaction);
        return $builder->execute();
    }

    /**
     * Submits an asynchronous transaction envelope xdr base 64 string to the network.
     * Unlike the synchronous version 'submitTransactionEnvelopeXdrBase64',
     * which blocks and waits for the transaction to be ingested in Horizon, this endpoint relays the response from
     * core directly back to the user.
     *
     * @param string $transactionEnvelopeXdrBase64 transaction envelope xdr base 64 string to be submitted to the network.
     * @return SubmitAsyncTransactionResponse the response received from Horizon.
     * @throws HorizonRequestException if there was a problem, such as an error response from Horizon. The details of the problem can be found within the exception object.
     */
    public function submitAsyncTransactionEnvelopeXdrBase64(string $transactionEnvelopeXdrBase64) : SubmitAsyncTransactionResponse {
        $builder = new SubmitAsyncTransactionRequestBuilder($this->httpClient);
        $builder->setTransactionEnvelopeXdrBase64($transactionEnvelopeXdrBase64);
        return $builder->execute();
    }


    /**
     * Validates memo requirements according to SEP-0029 specification
     *
     * SEP-0029 allows account owners to require that incoming payments include a memo.
     * This method checks if any destination accounts in the transaction require a memo
     * and returns the first account ID that requires one if the transaction lacks a memo.
     *
     * @param AbstractTransaction $transaction The transaction to validate
     * @return string|false The account ID of the first destination requiring a memo, or false if none found
     * @throws HorizonRequestException If the request to check account data fails
     * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0029.md
     */
    public function checkMemoRequired(AbstractTransaction $transaction) : string | false {
        if ($transaction instanceof FeeBumpTransaction) {
            return false;
        }
        $destinations = array();
        if ($transaction instanceof Transaction) {
            if ($transaction->getMemo()->getType() != Memo::MEMO_TYPE_NONE) {
                return false;
            }
            foreach ($transaction->getOperations() as $operation) {
                if ($operation instanceof PaymentOperation
                    || $operation instanceof PathPaymentStrictSendOperation
                    || $operation instanceof PathPaymentStrictReceiveOperation
                    || $operation instanceof AccountMergeOperation) {
                    $destination = $operation->getDestination();
                    if (!$destination->getId()) {
                        array_push($destinations, $destination->getAccountId());
                    }
                }
            }
        }
        if (count($destinations) == 0) {
            return false;
        }

        $key = "config.memo_required";
        foreach ($destinations as $destination) {
            $account = $this->requestAccount($destination);
            if ($account->getData()->get($key) == "1") {
                return $destination;
            }
        }
        return false;
    }
}