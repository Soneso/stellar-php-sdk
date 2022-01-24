<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Soneso\StellarSDK\Requests\RequestType;
use Soneso\StellarSDK\Responses\Account\AccountsPageResponse;
use Soneso\StellarSDK\Responses\Asset\AssetsPageResponse;
use Soneso\StellarSDK\Responses\ClaimableBalances\ClaimableBalanceResponse;
use Soneso\StellarSDK\Responses\ClaimableBalances\ClaimableBalancesPageResponse;
use Soneso\StellarSDK\Responses\Effects\EffectsPageResponse;
use Soneso\StellarSDK\Responses\Account\AccountResponse;
use Soneso\StellarSDK\Responses\FeeStats\FeeStatsResponse;
use Soneso\StellarSDK\Responses\Ledger\LedgerResponse;
use Soneso\StellarSDK\Responses\Ledger\LedgersPageResponse;
use Soneso\StellarSDK\Responses\LiquidityPools\LiquidityPoolResponse;
use Soneso\StellarSDK\Responses\LiquidityPools\LiquidityPoolsPageResponse;
use Soneso\StellarSDK\Responses\Offers\OfferResponse;
use Soneso\StellarSDK\Responses\Offers\OffersPageResponse;
use Soneso\StellarSDK\Responses\Operations\OperationResponse;
use Soneso\StellarSDK\Responses\Operations\OperationsPageResponse;
use Soneso\StellarSDK\Responses\OrderBook\OrderBookResponse;
use Soneso\StellarSDK\Responses\PaymentPath\PathsPageResponse;
use Soneso\StellarSDK\Responses\Root\RootResponse;
use Soneso\StellarSDK\Responses\TradeAggregations\TradeAggregationsPageResponse;
use Soneso\StellarSDK\Responses\Trades\TradeResponse;
use Soneso\StellarSDK\Responses\Trades\TradesPageResponse;
use Soneso\StellarSDK\Responses\Transaction\SubmitTransactionResponse;
use Soneso\StellarSDK\Responses\Transaction\TransactionResponse;
use Soneso\StellarSDK\Responses\Transaction\TransactionsPageResponse;
use Soneso\StellarSDK\SEP\Federation\FederationResponse;
use Soneso\StellarSDK\SEP\KYCService\GetCustomerInfoResponse;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerInfoResponse;
use Soneso\StellarSDK\SEP\TransferServerService\AnchorTransactionResponse;
use Soneso\StellarSDK\SEP\TransferServerService\AnchorTransactionsResponse;
use Soneso\StellarSDK\SEP\TransferServerService\DepositResponse;
use Soneso\StellarSDK\SEP\TransferServerService\FeeResponse;
use Soneso\StellarSDK\SEP\TransferServerService\InfoResponse;
use Soneso\StellarSDK\SEP\TransferServerService\WithdrawResponse;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeResponse;

class ResponseHandler
{

    public function handleResponse(ResponseInterface $response, string $requestType, Client $httpClient) : Response {

        $content = $response->getBody()->__toString();
        
        //print($content);
        
        // not success
        // this should normally not happen since it will be handled by gruzzle (throwing corresponding gruzzle exception)
        if (300 <= $response->getStatusCode()) {
            throw new \RuntimeException($content);
        }

        // success
        $jsonData = @json_decode($content, true);

        if (null === $jsonData && json_last_error() != JSON_ERROR_NONE) {
            throw new \InvalidArgumentException(sprintf("Error in json_decode: %s", json_last_error_msg()));
        }

        $horizonResponse = match ($requestType) {
            RequestType::ROOT => RootResponse::fromJson($jsonData),
            RequestType::SINGLE_ACCOUNT => AccountResponse::fromJson($jsonData),
            RequestType::ACCOUNTS_PAGE => AccountsPageResponse::fromJson($jsonData),
            RequestType::ASSETS_PAGE => AssetsPageResponse::fromJson($jsonData),
            RequestType::SINGLE_LEDGER => LedgerResponse::fromJson($jsonData),
            RequestType::LEDGERS_PAGE => LedgersPageResponse::fromJson($jsonData),
            RequestType::SINGLE_TRANSACTION => TransactionResponse::fromJson($jsonData),
            RequestType::TRANSACTIONS_PAGE => TransactionsPageResponse::fromJson($jsonData),
            RequestType::SINGLE_TRADE => TradeResponse::fromJson($jsonData),
            RequestType::TRADES_PAGE => TradesPageResponse::fromJson($jsonData),
            RequestType::FEE_STATS => FeeStatsResponse::fromJson($jsonData),
            RequestType::SINGLE_CLAIMABLE_BALANCE => ClaimableBalanceResponse::fromJson($jsonData),
            RequestType::CLAIMABLE_BALANCES_PAGE => ClaimableBalancesPageResponse::fromJson($jsonData),
            RequestType::SINGLE_OFFER => OfferResponse::fromJson($jsonData),
            RequestType::OFFERS_PAGE => OffersPageResponse::fromJson($jsonData),
            RequestType::ORDER_BOOK => OrderBookResponse::fromJson($jsonData),
            RequestType::TRADE_AGGREGATIONS_PAGE => TradeAggregationsPageResponse::fromJson($jsonData),
            RequestType::SINGLE_LIQUIDITY_POOL => LiquidityPoolResponse::fromJson($jsonData),
            RequestType::LIQUIDITY_POOLS_PAGE => LiquidityPoolsPageResponse::fromJson($jsonData),
            RequestType::PATHS_PAGE => PathsPageResponse::fromJson($jsonData),
            RequestType::SINGLE_OPERATION => OperationResponse::fromJson($jsonData),
            RequestType::OPERATIONS_PAGE => OperationsPageResponse::fromJson($jsonData),
            RequestType::EFFECTS_PAGE => EffectsPageResponse::fromJson($jsonData),
            RequestType::SUBMIT_TRANSACTION => SubmitTransactionResponse::fromJson($jsonData),
            RequestType::FEDERATION => FederationResponse::fromJson($jsonData),
            RequestType::CHALLENGE => ChallengeResponse::fromJson($jsonData),
            RequestType::GET_CUSTOMER_INFO, RequestType::PUT_CUSTOMER_VERIFICATION => GetCustomerInfoResponse::fromJson($jsonData),
            RequestType::PUT_CUSTOMER_INFO => PutCustomerInfoResponse::fromJson($jsonData),
            RequestType::ANCHOR_INFO => InfoResponse::fromJson($jsonData),
            RequestType::ANCHOR_DEPOSIT => DepositResponse::fromJson($jsonData),
            RequestType::ANCHOR_WITHDRAW => WithdrawResponse::fromJson($jsonData),
            RequestType::ANCHOR_FEE => FeeResponse::fromJson($jsonData),
            RequestType::ANCHOR_TRANSACTIONS => AnchorTransactionsResponse::fromJson($jsonData),
            RequestType::ANCHOR_TRANSACTION => AnchorTransactionResponse::fromJson($jsonData),
            default => throw new \InvalidArgumentException(sprintf("Unknown request type: %s", $requestType)),
        };

        $horizonResponse?->setHeaders($response->getHeaders());
        $horizonResponse?->setHttpClient($httpClient);
        return $horizonResponse;
    }
}

