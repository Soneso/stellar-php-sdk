<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

use DateTimeInterface;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\ResponseInterface;
use Soneso\StellarSDK\Requests\RequestBuilder;
use Soneso\StellarSDK\SEP\Toml\StellarToml;
use Soneso\StellarSDK\Util\UrlValidator;

/**
 * Main service class for SEP-06 Transfer Server protocol implementation.
 *
 * Implements SEP-06 Deposit and Withdrawal API for anchor integration.
 * This service enables wallets and clients to facilitate fiat on/off ramps through
 * deposit and withdrawal operations. Unlike SEP-24, SEP-06 provides more direct
 * programmatic access without requiring interactive web flows, though it supports
 * various deposit and withdrawal methods including bank transfers, cash, and crypto.
 *
 * Provides endpoints for:
 * - Info: Query anchor capabilities and supported assets
 * - Deposit: Initiate deposits from external assets to Stellar
 * - Deposit Exchange: Initiate cross-asset deposits with quotes
 * - Withdraw: Initiate withdrawals from Stellar to external assets
 * - Withdraw Exchange: Initiate cross-asset withdrawals with quotes
 * - Fee: Query fee information for operations
 * - Transactions: Query transaction history
 * - Transaction: Query individual transaction status
 * - Patch Transaction: Update pending transaction information
 *
 * @package Soneso\StellarSDK\SEP\TransferServerService
 * @see https://github.com/stellar/stellar-protocol/blob/v4.3.0/ecosystem/sep-0006.md SEP-06 v4.3.0 Specification
 * @see https://github.com/stellar/stellar-protocol/blob/v3.4.1/ecosystem/sep-0010.md SEP-10 v3.4.1 Authentication
 * @see https://github.com/stellar/stellar-protocol/blob/v1.15.0/ecosystem/sep-0012.md SEP-12 v1.15.0 KYC API
 * @see https://github.com/stellar/stellar-protocol/blob/v2.5.0/ecosystem/sep-0038.md SEP-38 v2.5.0 Quotes
 */
class TransferServerService
{
    private string $serviceAddress;
    private Client $httpClient;

    /**
     * Constructor.
     *
     * @param string $serviceAddress Transfer server base URL from stellar.toml TRANSFER_SERVER field
     * @param Client|null $httpClient Optional HTTP client for requests. If not provided, creates default client
     */
    public function __construct(string $serviceAddress, ?Client $httpClient = null)
    {
        UrlValidator::validateHttpsRequired($serviceAddress);
        $this->serviceAddress = $serviceAddress;
        if (str_ends_with($this->serviceAddress, "/")) {
            $this->serviceAddress = substr($this->serviceAddress, 0, -1);
        }
        if ($httpClient === null) {
            $this->httpClient = new Client();
        } else {
            $this->httpClient = $httpClient;
        }
    }

    /**
     * Constructs TransferServerService by discovering service URL from domain using SEP-01.
     *
     * Fetches the stellar.toml file from the domain and extracts the TRANSFER_SERVER URL.
     *
     * @param string $domain Domain to query (e.g. 'testanchor.stellar.org')
     * @param Client|null $httpClient Optional HTTP client for requests. If not provided, creates default client
     * @return TransferServerService Constructed service instance
     * @throws Exception If service address cannot be loaded from domain or TRANSFER_SERVER not defined
     */
    public static function fromDomain(string $domain, ?Client $httpClient = null) : TransferServerService {
        $stellarToml = StellarToml::fromDomain($domain);
        $address = $stellarToml->getGeneralInformation()->transferServer;
        if (!$address) {
            throw new Exception("Transfer server SEP 06 not available for domain " . $domain);
        }
        return new TransferServerService($address, $httpClient);
    }

    /**
     * Query anchor capabilities and supported assets via info endpoint.
     *
     * Returns information about supported assets, deposit/withdrawal methods, fees,
     * and feature flags. This is the discovery endpoint that clients should query
     * first to understand what operations the anchor supports.
     *
     * @param string|null $jwt Optional SEP-10 JWT token from authentication flow
     * @param string|null $language Optional language code (RFC 4646). Defaults to 'en'. Error fields use this language
     * @return InfoResponse Parsed response containing anchor capabilities
     * @throws GuzzleException If request error occurs
     */
    public function info(?string $jwt = null, ?string $language = null) : InfoResponse {
        $requestBuilder = new InfoRequestBuilder($this->httpClient, $this->serviceAddress, $jwt);
        if($language) {
            $requestBuilder = $requestBuilder->forQueryParameters(["lang" => $language]);
        }
        return $requestBuilder->execute();
    }

    /**
     * Initiate deposit of external asset to Stellar network.
     *
     * Initiates a deposit where the user sends an external asset (BTC via Bitcoin,
     * USD via bank transfer, etc.) to an anchor-controlled address. The anchor then
     * sends equivalent Stellar tokens (minus fees) to the user's Stellar account.
     *
     * For cross-asset deposits with SEP-38 quote support, use depositExchange() instead.
     * The response provides deposit instructions including destination addresses,
     * memos, and any additional requirements.
     *
     * @param DepositRequest $request Deposit request parameters including asset code and account
     * @return DepositResponse Deposit instructions and transaction details
     * @throws CustomerInformationNeededException Anchor needs more customer information via SEP-12
     * @throws CustomerInformationStatusException Customer information pending or rejected
     * @throws AuthenticationRequiredException Endpoint requires authentication but no JWT provided
     * @throws GuzzleException If request error occurs
     */
    public function deposit(DepositRequest $request) : DepositResponse {
        $requestBuilder = new DepositRequestBuilder($this->httpClient, $this->serviceAddress, $request->jwt);
        $queryParameters = array();
        $queryParameters += ["asset_code" => $request->assetCode];
        $queryParameters += ["account" => $request->account];
        if ($request->memoType !== null) {
            $queryParameters += ["memo_type" => $request->memoType];
        }
        if ($request->memo !== null) {
            $queryParameters += ["memo" => $request->memo];
        }
        if ($request->emailAddress !== null) {
            $queryParameters += ["email_address" => $request->emailAddress];
        }
        if ($request->type !== null) {
            $queryParameters += ["type" => $request->type];
        }
        if ($request->walletName !== null) {
            $queryParameters += ["wallet_name" => $request->walletName];
        }
        if ($request->walletUrl !== null) {
            $queryParameters += ["wallet_url" => $request->walletUrl];
        }
        if ($request->lang !== null) {
            $queryParameters += ["lang" => $request->lang];
        }
        if ($request->onChangeCallback !== null) {
            $queryParameters += ["on_change_callback" => $request->onChangeCallback];
        }
        if ($request->amount !== null) {
            $queryParameters += ["amount" => $request->amount];
        }
        if ($request->countryCode !== null) {
            $queryParameters += ["country_code" => $request->countryCode];
        }
        if ($request->claimableBalanceSupported !== null) {
            $queryParameters += ["claimable_balance_supported" => $request->claimableBalanceSupported];
        }
        if ($request->customerId !== null) {
            $queryParameters += ["customer_id" => $request->customerId];
        }
        if ($request->locationId !== null) {
            $queryParameters += ["location_id" => $request->locationId];
        }
        if ($request->extraFields != null) {
            $queryParameters = array_merge($queryParameters, $request->extraFields);
        }

        $requestBuilder = $requestBuilder->forQueryParameters($queryParameters);
        return $requestBuilder->execute();
    }

    /**
     * Initiate cross-asset deposit with currency conversion using SEP-38 quotes.
     *
     * Enables deposits with currency conversion where the user sends one asset
     * (e.g., BRL via bank transfer) and receives a different asset on Stellar
     * (e.g., USDC). Requires anchor to support SEP-38 quotes for exchange rates.
     *
     * The response provides deposit instructions and quote details for the conversion.
     * Use the quote_id from a prior SEP-38 quote request to lock in exchange rates.
     *
     * @param DepositExchangeRequest $request Deposit exchange request with source and destination assets
     * @return DepositResponse Deposit instructions with quote information
     * @throws CustomerInformationNeededException Anchor needs more customer information via SEP-12
     * @throws CustomerInformationStatusException Customer information pending or rejected
     * @throws AuthenticationRequiredException Endpoint requires authentication but no JWT provided
     * @throws GuzzleException If request error occurs
     */
    public function depositExchange(DepositExchangeRequest $request) : DepositResponse {
        $requestBuilder = new DepositExchangeRequestBuilder($this->httpClient, $this->serviceAddress, $request->jwt);
        $queryParameters = array();
        $queryParameters += ["destination_asset" => $request->destinationAsset];
        $queryParameters += ["source_asset" => $request->sourceAsset];
        $queryParameters += ["amount" => $request->amount];
        $queryParameters += ["account" => $request->account];

        if ($request->quoteId !== null) {
            $queryParameters += ["quote_id" => $request->quoteId];
        }
        if ($request->memoType !== null) {
            $queryParameters += ["memo_type" => $request->memoType];
        }
        if ($request->memo !== null) {
            $queryParameters += ["memo" => $request->memo];
        }
        if ($request->emailAddress !== null) {
            $queryParameters += ["email_address" => $request->emailAddress];
        }
        if ($request->type !== null) {
            $queryParameters += ["type" => $request->type];
        }
        if ($request->walletName !== null) {
            $queryParameters += ["wallet_name" => $request->walletName];
        }
        if ($request->walletUrl !== null) {
            $queryParameters += ["wallet_url" => $request->walletUrl];
        }
        if ($request->lang !== null) {
            $queryParameters += ["lang" => $request->lang];
        }
        if ($request->onChangeCallback !== null) {
            $queryParameters += ["on_change_callback" => $request->onChangeCallback];
        }
        if ($request->amount !== null) {
            $queryParameters += ["amount" => $request->amount];
        }
        if ($request->countryCode !== null) {
            $queryParameters += ["country_code" => $request->countryCode];
        }
        if ($request->claimableBalanceSupported !== null) {
            $queryParameters += ["claimable_balance_supported" => $request->claimableBalanceSupported];
        }
        if ($request->customerId !== null) {
            $queryParameters += ["customer_id" => $request->customerId];
        }
        if ($request->locationId !== null) {
            $queryParameters += ["location_id" => $request->locationId];
        }
        if ($request->extraFields != null) {
            $queryParameters = array_merge($queryParameters, $request->extraFields);
        }

        $requestBuilder = $requestBuilder->forQueryParameters($queryParameters);
        return $requestBuilder->execute();
    }

    /**
     * Initiate withdrawal from Stellar network to external asset.
     *
     * Initiates a withdrawal where the user redeems Stellar assets for their
     * off-chain equivalent via the anchor. For example, redeeming NGNT tokens
     * for fiat NGN sent to a bank account.
     *
     * For cross-asset withdrawals with SEP-38 quote support, use withdrawExchange() instead.
     * The response provides withdrawal instructions including the anchor's Stellar account,
     * memo requirements, and any additional user information needed.
     *
     * @param WithdrawRequest $request Withdrawal request parameters including asset code and type
     * @return WithdrawResponse Withdrawal instructions and transaction details
     * @throws CustomerInformationNeededException Anchor needs more customer information via SEP-12
     * @throws CustomerInformationStatusException Customer information pending or rejected
     * @throws AuthenticationRequiredException Endpoint requires authentication but no JWT provided
     * @throws GuzzleException If request error occurs
     */
    public function withdraw(WithdrawRequest $request) : WithdrawResponse {
        $requestBuilder = new WithdrawRequestBuilder($this->httpClient, $this->serviceAddress, $request->jwt);
        $queryParameters = array();
        $queryParameters += ["asset_code" => $request->assetCode];
        $queryParameters += ["type" => $request->type];
        if ($request->dest !== null) {
            $queryParameters += ["dest" => $request->dest];
        }
        if ($request->destExtra !== null) {
            $queryParameters += ["dest_extra" => $request->destExtra];
        }
        if ($request->account !== null) {
            $queryParameters += ["account" => $request->account];
        }
        if ($request->memo !== null) {
            $queryParameters += ["memo" => $request->memo];
        }
        if ($request->memoType !== null) {
            $queryParameters += ["memo_type" => $request->memoType];
        }
        if ($request->walletName !== null) {
            $queryParameters += ["wallet_name" => $request->walletName];
        }
        if ($request->walletUrl !== null) {
            $queryParameters += ["wallet_url" => $request->walletUrl];
        }
        if ($request->lang !== null) {
            $queryParameters += ["lang" => $request->lang];
        }
        if ($request->onChangeCallback !== null) {
            $queryParameters += ["on_change_callback" => $request->onChangeCallback];
        }
        if ($request->amount !== null) {
            $queryParameters += ["amount" => $request->amount];
        }
        if ($request->countryCode !== null) {
            $queryParameters += ["country_code" => $request->countryCode];
        }
        if ($request->refundMemo !== null) {
            $queryParameters += ["refund_memo" => $request->refundMemo];
        }
        if ($request->refundMemoType !== null) {
            $queryParameters += ["refund_memo_type" => $request->refundMemoType];
        }
        if ($request->customerId !== null) {
            $queryParameters += ["customer_id" => $request->customerId];
        }
        if ($request->locationId !== null) {
            $queryParameters += ["location_id" => $request->locationId];
        }
        if ($request->extraFields != null) {
            $queryParameters = array_merge($queryParameters, $request->extraFields);
        }

        $requestBuilder = $requestBuilder->forQueryParameters($queryParameters);
        return $requestBuilder->execute();
    }

    /**
     * Initiate cross-asset withdrawal with currency conversion using SEP-38 quotes.
     *
     * Enables withdrawals with currency conversion where the user sends one Stellar asset
     * (e.g., USDC) and receives a different off-chain asset (e.g., NGN to bank account).
     * Requires anchor to support SEP-38 quotes for exchange rates.
     *
     * The response provides withdrawal instructions and quote details for the conversion.
     * Use the quote_id from a prior SEP-38 quote request to lock in exchange rates.
     *
     * @param WithdrawExchangeRequest $request Withdrawal exchange request with source and destination assets
     * @return WithdrawResponse Withdrawal instructions with quote information
     * @throws CustomerInformationNeededException Anchor needs more customer information via SEP-12
     * @throws CustomerInformationStatusException Customer information pending or rejected
     * @throws AuthenticationRequiredException Endpoint requires authentication but no JWT provided
     * @throws GuzzleException If request error occurs
     */
    public function withdrawExchange(WithdrawExchangeRequest $request) : WithdrawResponse {
        $requestBuilder = new WithdrawExchangeRequestBuilder($this->httpClient, $this->serviceAddress, $request->jwt);
        $queryParameters = array();
        $queryParameters += ["source_asset" => $request->sourceAsset];
        $queryParameters += ["destination_asset" => $request->destinationAsset];
        $queryParameters += ["amount" => $request->amount];
        $queryParameters += ["type" => $request->type];

        if ($request->quoteId !== null) {
            $queryParameters += ["quote_id" => $request->quoteId];
        }

        if ($request->dest !== null) {
            $queryParameters += ["dest" => $request->dest];
        }
        if ($request->destExtra !== null) {
            $queryParameters += ["dest_extra" => $request->destExtra];
        }
        if ($request->account !== null) {
            $queryParameters += ["account" => $request->account];
        }
        if ($request->memo !== null) {
            $queryParameters += ["memo" => $request->memo];
        }
        if ($request->memoType !== null) {
            $queryParameters += ["memo_type" => $request->memoType];
        }
        if ($request->walletName !== null) {
            $queryParameters += ["wallet_name" => $request->walletName];
        }
        if ($request->walletUrl !== null) {
            $queryParameters += ["wallet_url" => $request->walletUrl];
        }
        if ($request->lang !== null) {
            $queryParameters += ["lang" => $request->lang];
        }
        if ($request->onChangeCallback !== null) {
            $queryParameters += ["on_change_callback" => $request->onChangeCallback];
        }
        if ($request->countryCode !== null) {
            $queryParameters += ["country_code" => $request->countryCode];
        }
        if ($request->refundMemo !== null) {
            $queryParameters += ["refund_memo" => $request->refundMemo];
        }
        if ($request->refundMemoType !== null) {
            $queryParameters += ["refund_memo_type" => $request->refundMemoType];
        }
        if ($request->customerId !== null) {
            $queryParameters += ["customer_id" => $request->customerId];
        }
        if ($request->locationId !== null) {
            $queryParameters += ["location_id" => $request->locationId];
        }
        if ($request->extraFields != null) {
            $queryParameters = array_merge($queryParameters, $request->extraFields);
        }

        $requestBuilder = $requestBuilder->forQueryParameters($queryParameters);
        return $requestBuilder->execute();
    }

    /**
     * Query fee information for deposit or withdrawal operations.
     *
     * Allows wallets to query the fee that would be charged for a given operation.
     * Important when fee schedules are complex and cannot be fully expressed in the
     * info endpoint's fee_fixed, fee_percent, or fee_minimum fields.
     *
     * @param FeeRequest $request Fee request with operation type, asset, and amount
     * @return FeeResponse Fee amount and details
     * @throws GuzzleException If request error occurs
     */
    public function fee(FeeRequest $request) : FeeResponse {
        $requestBuilder = new FeeRequestBuilder($this->httpClient, $this->serviceAddress, $request->jwt);
        $queryParameters = array();
        $queryParameters += ["operation" => $request->operation];
        $queryParameters += ["asset_code" => $request->assetCode];
        $queryParameters += ["amount" => $request->amount];

        if ($request->type) {
            $queryParameters += ["type" => $request->type];
        }

        $requestBuilder = $requestBuilder->forQueryParameters($queryParameters);
        return $requestBuilder->execute();
    }

    /**
     * Query transaction history for deposits and withdrawals.
     *
     * Retrieves transaction history for the authenticated account, enabling wallets
     * to display status updates and historical records. Only returns deposit and
     * withdrawal transactions processed through this anchor.
     *
     * Supports filtering by asset, transaction kind, and time range, plus pagination
     * for large result sets.
     *
     * @param AnchorTransactionsRequest $request Transactions query parameters with filters and pagination
     * @return AnchorTransactionsResponse List of matching transactions
     * @throws GuzzleException If request error occurs
     */
    public function transactions(AnchorTransactionsRequest $request) : AnchorTransactionsResponse {
        $requestBuilder = new AnchorTransactionsRequestBuilder($this->httpClient, $this->serviceAddress, $request->jwt);
        $queryParameters = array();
        $queryParameters += ["asset_code" => $request->assetCode];
        $queryParameters += ["account" => $request->account];

        if ($request->noOlderThan) {
            $queryParameters += ["no_older_than" => $request->noOlderThan->format(DateTimeInterface::ATOM)];
        }

        if ($request->limit) {
            $queryParameters += ["limit" => $request->limit];
        }

        if ($request->kind) {
            $queryParameters += ["kind" => $request->kind];
        }

        if ($request->pagingId) {
            $queryParameters += ["paging_id" => $request->pagingId];
        }

        if ($request->lang) {
            $queryParameters += ["lang" => $request->lang];
        }

        $requestBuilder = $requestBuilder->forQueryParameters($queryParameters);
        return $requestBuilder->execute();
    }

    /**
     * Query individual transaction details and status.
     *
     * Retrieves detailed information about a specific transaction using one of:
     * transaction ID, Stellar transaction hash, or external transaction ID.
     *
     * Useful for validating transaction status and getting real-time updates
     * on deposit or withdrawal progress.
     *
     * @param AnchorTransactionRequest $request Transaction query with at least one identifier
     * @return AnchorTransactionResponse Detailed transaction information
     * @throws GuzzleException If request error occurs
     */
    public function transaction(AnchorTransactionRequest $request) : AnchorTransactionResponse {
        $requestBuilder = new AnchorTransactionRequestBuilder($this->httpClient, $this->serviceAddress, $request->jwt);
        $queryParameters = array();

        if ($request->id) {
            $queryParameters += ["id" => $request->id];
        }

        if ($request->stellarTransactionId) {
            $queryParameters += ["stellar_transaction_id" => $request->stellarTransactionId];
        }

        if ($request->externalTransactionId) {
            $queryParameters += ["external_transaction_id" => $request->externalTransactionId];
        }

        $requestBuilder = $requestBuilder->forQueryParameters($queryParameters);
        return $requestBuilder->execute();
    }

    /**
     * Update pending transaction with additional required information.
     *
     * Updates transaction details when the anchor requests more information via
     * the pending_transaction_info_update status. The transaction's required_info_updates
     * field specifies which fields need updating.
     *
     * Should only be called when anchor explicitly requests updates. Attempting to
     * update when no information is requested will result in an error.
     *
     * @param PatchTransactionRequest $request Transaction ID and fields to update
     * @return ResponseInterface Raw HTTP response
     * @throws GuzzleException If request error occurs
     */
    public function patchTransaction(PatchTransactionRequest $request) : ResponseInterface {
        $headers = array();
        $headers = array_merge($headers, RequestBuilder::HEADERS);
        if ($request->jwt !== null) {
            $headers = array_merge($headers, ['Authorization' => "Bearer " . $request->jwt]);
        }

        $url = "/transaction/" . $request->id;
        return $this->httpClient->request("PATCH", $url, [
            "json" => $request->fields,
            "headers" => $headers
        ]);
    }

    /**
     * Sets a mock handler stack for testing purposes.
     *
     * @internal This method is intended for testing and should not be used in production code
     * @param HandlerStack $handlerStack the handler stack to use for mocking requests.
     * @return void
     */
    public function setMockHandlerStack(HandlerStack $handlerStack) : void {
        $this->httpClient = new Client(['handler' => $handlerStack]);
    }
}