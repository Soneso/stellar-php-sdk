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

/**
 * Implements SEP-0006 - Deposit and Withdrawal API
 * See <a href="https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md" target="_blank">Deposit and Withdrawal API</a>
 */
class TransferServerService
{
    private string $serviceAddress;
    private Client $httpClient;

    /**
     * @param string $serviceAddress address of the service from stellar toml.
     * @param Client|null $httpClient optional http client to be used for the requests. If not provided, then uses an own client.
     */
    public function __construct(string $serviceAddress, ?Client $httpClient = null)
    {
        $this->serviceAddress = $serviceAddress;
        if (substr($this->serviceAddress, -1) === "/") {
            $this->serviceAddress = substr($this->serviceAddress, 0, -1);
        }
        if ($httpClient === null) {
            $this->httpClient = new Client();
        } else {
            $this->httpClient = $httpClient;
        }
    }

    /**
     * Constructs an TransferServerService instance by parsing the server service address from the given domain using sep-01.
     * @param string $domain the domain to parse the data from. e.g. 'testanchor.stellar.org'.
     * @param Client|null $httpClient optional http client to be used for the requests. If not provided, then uses an own client.
     * @return TransferServerService the constructed TransferServerService object.
     * @throws Exception if the service address could not be loaded from the given domain.
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
     * Get basic info from the anchor about what their TRANSFER_SERVER supports.
     * @param string|null $jwt token previously received from the anchor via the SEP-10 authentication flow
     * @param string|null $language (optional) Defaults to en if not specified or if the specified
     * language is not supported. Language code specified using RFC 4646.
     * Error fields and other human-readable messages in the response should be in this language.
     * @return InfoResponse response the parsed response.
     * @throws GuzzleException if a request exception occurs.
     */
    public function info(?string $jwt = null, ?string $language = null) : InfoResponse {
        $requestBuilder = new InfoRequestBuilder($this->httpClient, $this->serviceAddress, $jwt);
        if($language) {
            $requestBuilder = $requestBuilder->forQueryParameters(["lang" => $language]);
        }
        return $requestBuilder->execute();
    }

    /**
     * A deposit is when a user sends an external token (BTC via Bitcoin,
     * USD via bank transfer, etc...) to an address held by an anchor. In turn,
     * the anchor sends an equal amount of tokens on the Stellar network
     * (minus fees) to the user's Stellar account.
     *
     * If the anchor supports SEP-38 quotes, it can also provide a bridge
     * between non-equivalent tokens. For example, the anchor can receive ARS
     * via bank transfer and in return send the equivalent value (minus fees)
     * as USDC on the Stellar network to the user's Stellar account.
     * That kind of deposit is covered in GET /deposit-exchange.
     *
     * The deposit endpoint allows a wallet to get deposit information from
     * an anchor, so a user has all the information needed to initiate a deposit.
     * It also lets the anchor specify additional information (if desired) that
     * the user must submit via SEP-12 to be able to deposit.
     *
     * @param DepositRequest $request the request data.
     * @return DepositResponse the parsed response in case of success
     * @throws CustomerInformationNeededException The anchor needs more information about the customer.
     * @throws CustomerInformationStatusException Customer information was submitted for the account, but the information is either still being processed or was not accepted.
     * @throws AuthenticationRequiredException is the endpoint needs authentication but no jwt token provided in the request.
     * @throws GuzzleException if a request error occurs.
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

        $requestBuilder = $requestBuilder->forQueryParameters($queryParameters);
        return $requestBuilder->execute();
    }

    /**
     * If the anchor supports SEP-38 quotes, it can provide a deposit that makes
     * a bridge between non-equivalent tokens by receiving, for instance BRL
     * via bank transfer and in return sending the equivalent value (minus fees)
     * as USDC to the user's Stellar account.
     *
     * The /deposit-exchange endpoint allows a wallet to get deposit information
     * from an anchor when the user intends to make a conversion between
     * non-equivalent tokens. With this endpoint, a user has all the information
     * needed to initiate a deposit and it also lets the anchor specify
     * additional information (if desired) that the user must submit via SEP-12.
     *
     * @param DepositExchangeRequest $request the request data.
     * @return DepositResponse the parsed response in case of success
     * @throws CustomerInformationNeededException The anchor needs more information about the customer.
     * @throws CustomerInformationStatusException Customer information was submitted for the account, but the information is either still being processed or was not accepted.
     * @throws AuthenticationRequiredException is the endpoint needs authentication but no jwt token provided in the request.
     * @throws GuzzleException if a request error occurs.
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

        $requestBuilder = $requestBuilder->forQueryParameters($queryParameters);
        return $requestBuilder->execute();
    }

    /**
     * A withdraw is when a user redeems an asset currently on the
     * Stellar network for its equivalent off-chain asset via the Anchor.
     * For instance, a user redeeming their NGNT in exchange for fiat NGN.
     *
     * If the anchor supports SEP-38 quotes, it can also provide a bridge
     * between non-equivalent tokens. For example, the anchor can receive USDC
     * from the Stellar network and in return send the equivalent value
     * (minus fees) as NGN to the user's bank account.
     * That kind of withdrawal is covered in GET /withdraw-exchange.
     *
     * The /withdraw endpoint allows a wallet to get withdrawal information
     * from an anchor, so a user has all the information needed to initiate
     * a withdrawal. It also lets the anchor specify additional information
     * (if desired) that the user must submit via SEP-12 to be able to withdraw.
     *
     * @param WithdrawRequest $request the request data.
     * @return WithdrawResponse the parsed response in case of success
     * @throws CustomerInformationNeededException The anchor needs more information about the customer.
     * @throws CustomerInformationStatusException Customer information was submitted for the account, but the information is either still being processed or was not accepted.
     * @throws AuthenticationRequiredException is the endpoint needs authentication but no jwt token provided in the request.
     * @throws GuzzleException in case of request error
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

        $requestBuilder = $requestBuilder->forQueryParameters($queryParameters);
        return $requestBuilder->execute();
    }

    /**
     * If the anchor supports SEP-38 quotes, it can provide a withdraw that makes
     * a bridge between non-equivalent tokens by receiving, for instance USDC
     * from the Stellar network and in return sending the equivalent value
     * (minus fees) as NGN to the user's bank account.
     *
     * The /withdraw-exchange endpoint allows a wallet to get withdraw
     * information from an anchor when the user intends to make a conversion
     * between non-equivalent tokens. With this endpoint, a user has all the
     * information needed to initiate a withdraw and it also lets the anchor
     * specify additional information (if desired) that the user must submit via SEP-12.
     *
     * @param WithdrawExchangeRequest $request the request data
     * @return WithdrawResponse the parsed response in case of success
     * @throws CustomerInformationNeededException The anchor needs more information about the customer.
     * @throws CustomerInformationStatusException Customer information was submitted for the account, but the information is either still being processed or was not accepted.
     * @throws AuthenticationRequiredException is the endpoint needs authentication but no jwt token provided in the request.
     * @throws GuzzleException in case of request error
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

        $requestBuilder = $requestBuilder->forQueryParameters($queryParameters);
        return $requestBuilder->execute();
    }

    /**
     * Fee endpoint.
     * @param FeeRequest $request the request data
     * @return FeeResponse the parsed response in case of success
     * @throws GuzzleException in case of request error
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
     * The transaction history endpoint helps anchors enable a better experience for users using an external wallet.
     * With it, wallets can display the status of deposits and withdrawals while they process and a history of past
     * transactions with the anchor. It's only for transactions that are deposits to or withdrawals from the anchor.
     *
     * @param AnchorTransactionsRequest $request the request data
     * @return AnchorTransactionsResponse the parsed response in case of success
     * @throws GuzzleException in case of request error
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
     * The transaction endpoint enables clients to query/validate a specific transaction at an anchor.
     * @param AnchorTransactionRequest $request the request data
     * @return AnchorTransactionResponse the parsed response in case of success
     * @throws GuzzleException in case of request error
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
     * Updates a transaction. This endpoint should only be used when the anchor requests more info via the pending_transaction_info_update status.
     * The required_info_updates transaction field should contain the fields required for the update.
     * If the sender tries to update at a time when no info is requested the receiver will fail with an error response.
     * @param PatchTransactionRequest $request request data
     * @return ResponseInterface response
     * @throws GuzzleException if a request error occurs
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

    public function setMockHandlerStack(HandlerStack $handlerStack) {
        $this->httpClient = new Client(['handler' => $handlerStack]);
    }
}