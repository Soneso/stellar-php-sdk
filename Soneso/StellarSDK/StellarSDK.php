<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;


use GuzzleHttp\Client;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Requests\AccountsRequestBuilder;
use Soneso\StellarSDK\Requests\AssetsRequestBuilder;
use Soneso\StellarSDK\Requests\ClaimableBalancesRequestBuilder;
use Soneso\StellarSDK\Requests\EffectsRequestBuilder;
use Soneso\StellarSDK\Requests\FeeStatsRequestBuilder;
use Soneso\StellarSDK\Requests\FindPathsRequestBuilder;
use Soneso\StellarSDK\Requests\LedgersRequestBuilder;
use Soneso\StellarSDK\Requests\LiquidityPoolsRequestBuilder;
use Soneso\StellarSDK\Requests\OffersRequestBuilder;
use Soneso\StellarSDK\Requests\OperationsRequestBuilder;
use Soneso\StellarSDK\Requests\OrderBookRequestBuilder;
use Soneso\StellarSDK\Requests\PaymentsRequestBuilder;
use Soneso\StellarSDK\Requests\RootRequestBuilder;
use Soneso\StellarSDK\Requests\StrictReceivePathsRequestBuilder;
use Soneso\StellarSDK\Requests\StrictSendPathsRequestBuilder;
use Soneso\StellarSDK\Requests\SubmitTransactionRequestBuilder;
use Soneso\StellarSDK\Requests\TradeAggregationsRequestBuilder;
use Soneso\StellarSDK\Requests\TradesRequestBuilder;
use Soneso\StellarSDK\Requests\TransactionsRequestBuilder;
use Soneso\StellarSDK\Responses\Account\AccountResponse;
use Soneso\StellarSDK\Responses\ClaimableBalances\ClaimableBalanceResponse;
use Soneso\StellarSDK\Responses\FeeStats\FeeStatsResponse;
use Soneso\StellarSDK\Responses\Ledger\LedgerResponse;
use Soneso\StellarSDK\Responses\LiquidityPools\LiquidityPoolResponse;
use Soneso\StellarSDK\Responses\Offers\OfferResponse;
use Soneso\StellarSDK\Responses\Operations\OperationResponse;
use Soneso\StellarSDK\Responses\Root\RootResponse;
use Soneso\StellarSDK\Responses\Transaction\SubmitTransactionResponse;
use Soneso\StellarSDK\Responses\Transaction\TransactionResponse;

/**
 * Main class of the stellar php sdk.
 */
class StellarSDK
{
    
    public const VERSION_NR = "1.2.4";
    public static string $PUBLIC_NET_HORIZON_URL = "https://horizon.stellar.org";
    public static string $TEST_NET_HORIZON_URL = "https://horizon-testnet.stellar.org";
    public static string $FUTURE_NET_HORIZON_URL = "https://horizon-futurenet.stellar.org";
    
    private static ?StellarSDK $publicNetInstance = null;
    private static ?StellarSDK $testnetInstance = null;
    private static ?StellarSDK $futurenetInstance = null;
    
    private string $serverUri;
    private Client $httpClient;
        
    public static function getPublicNetInstance() : ?StellarSDK
    {
        if (!self::$publicNetInstance) {
            self::$publicNetInstance = new StellarSDK(self::$PUBLIC_NET_HORIZON_URL);
        }    
        return self::$publicNetInstance;
    }
    
    public static function getTestNetInstance() : ?StellarSDK
    {
        if (!self::$testnetInstance) {
            self::$testnetInstance = new StellarSDK(self::$TEST_NET_HORIZON_URL);
        }
        return self::$testnetInstance;
    }

    public static function getFutureNetInstance() : ?StellarSDK
    {
        if (!self::$futurenetInstance) {
            self::$futurenetInstance = new StellarSDK(self::$FUTURE_NET_HORIZON_URL);
        }
        return self::$futurenetInstance;
    }
    
    public function __construct(string $uri)
    {
        $this->serverUri = $uri;
        $this->httpClient = new Client([
            'base_uri' => $uri,
            'exceptions' => false,
        ]);
    }
    
    public function getHttpClient() : Client {
        return $this->httpClient;
    }
    
    public function setHttpClient(Client $httpClient) {
        $this->httpClient = $httpClient;
    }

    /**
     * Requests specific root server url and returns {@link RootResponse}.
     * @throws HorizonRequestException
     */
    public function root() : RootResponse {
        $requestBuilder = new RootRequestBuilder($this->httpClient);
        return $requestBuilder->getRoot($this->serverUri);
    }

    public function accounts() : AccountsRequestBuilder {
        return new AccountsRequestBuilder($this->httpClient);
    }

    public function assets() : AssetsRequestBuilder {
        return new AssetsRequestBuilder($this->httpClient);
    }

    public function ledgers() : LedgersRequestBuilder {
        return new LedgersRequestBuilder($this->httpClient);
    }

    public function transactions() : TransactionsRequestBuilder {
        return new TransactionsRequestBuilder($this->httpClient);
    }

    public function trades() : TradesRequestBuilder {
        return new TradesRequestBuilder($this->httpClient);
    }

    public function feeStats() : FeeStatsRequestBuilder {
        return new FeeStatsRequestBuilder($this->httpClient);
    }

    public function claimableBalances() : ClaimableBalancesRequestBuilder {
        return new ClaimableBalancesRequestBuilder($this->httpClient);
    }

    public function offers() : OffersRequestBuilder {
        return new OffersRequestBuilder($this->httpClient);
    }

    public function orderBook() : OrderBookRequestBuilder {
        return new OrderBookRequestBuilder($this->httpClient);
    }

    public function operations() : OperationsRequestBuilder {
        return new OperationsRequestBuilder($this->httpClient);
    }

    public function payments() : PaymentsRequestBuilder {
        return new PaymentsRequestBuilder($this->httpClient);
    }

    public function effects() : EffectsRequestBuilder {
        return new EffectsRequestBuilder($this->httpClient);
    }

    public function tradeAggregations() : TradeAggregationsRequestBuilder {
        return new TradeAggregationsRequestBuilder($this->httpClient);
    }

    public function liquidityPools() : LiquidityPoolsRequestBuilder {
        return new LiquidityPoolsRequestBuilder($this->httpClient);
    }

    public function findPaths() : FindPathsRequestBuilder {
        return new FindPathsRequestBuilder($this->httpClient);
    }

    public function findStrictSendPaths() : StrictSendPathsRequestBuilder {
        return new StrictSendPathsRequestBuilder($this->httpClient);
    }

    public function findStrictReceivePaths() : StrictReceivePathsRequestBuilder {
        return new StrictReceivePathsRequestBuilder($this->httpClient);
    }

    /**
     * Requests the account details from horizon for the given accountId (public key)
     * @throws HorizonRequestException
     */
    public function requestAccount(string $accountId) : AccountResponse {
        return $this->accounts()->account($accountId);
    }

    /**
     * Checks if the account exists by querying from horizon.
     * @throws HorizonRequestException
     */
    public function accountExists(string $accountId) : bool {
        try {
            $account = $this->requestAccount($accountId);
        } catch (HorizonRequestException $e) {
            if ($e->getStatusCode() == 404) {
                return false;
            }
            throw $e;
        }
        return true;
    }

    /**
     * Requests the ledger details from horizon for the given ledger sequence
     * @throws HorizonRequestException
     */
    public function requestLedger(string $ledgerSeq) : LedgerResponse {
        return $this->ledgers()->ledger($ledgerSeq);
    }

    /**
     * Requests the transaction details from horizon for the given transaction id
     * @throws HorizonRequestException
     */
    public function requestTransaction(string $transactionId) : TransactionResponse {
        return $this->transactions()->transaction($transactionId);
    }

    /**
     * Requests the fee stats from horizon
     * @return FeeStatsResponse
     * @throws HorizonRequestException
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
     * @throws HorizonRequestException
     */
    public function submitTransaction(AbstractTransaction $transaction) : SubmitTransactionResponse {
        $builder = new SubmitTransactionRequestBuilder($this->httpClient);
        $builder->setTransaction($transaction);
        return $builder->execute();
    }

    /**
     * SEP-029 implementation. See https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0029.md
     * Account Memo Requirements. An account owner configures memo requirements by adding a data entry that
     * specifies whether a transaction memo is required when transferring funds to the account.
     * A payment sender ensures that a memo is attached before submitting the transaction to the network.
     * @param AbstractTransaction $transaction
     * @return string|false account id of the first destination found that requires memo, false if none is found.
     * @throws HorizonRequestException if thrown during requesting destination account data from horizon
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