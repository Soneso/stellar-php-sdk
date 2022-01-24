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

class TransferServerService
{
    private string $serviceAddress;
    private Client $httpClient;

    /**
     * @param string $serviceAddress
     */
    public function __construct(string $serviceAddress)
    {
        $this->serviceAddress = $serviceAddress;
        $this->httpClient = new Client([
            'base_uri' => $this->serviceAddress,
            'exceptions' => false,
        ]);
    }

    /**
     * @param string $domain
     * @return TransferServerService
     * @throws Exception
     */
    public static function fromDomain(string $domain) : TransferServerService {
        $stellarToml = StellarToml::fromDomain($domain);
        $address = $stellarToml->getGeneralInformation()->transferServer;
        if (!$address) {
            throw new Exception("No transfer service found in stellar.toml");
        }
        return new TransferServerService($address);
    }

    /**
     * Get basic info from the anchor about what their TRANSFER_SERVER supports.
     * @param string $jwt token previously received from the anchor via the SEP-10 authentication flow
     * @param string|null $language (optional) Language code specified using ISO 639-1. description fields in the response should be in this language. Defaults to en.
     * @return InfoResponse response
     * @throws GuzzleException if en request error occurs.
     */
    public function info(string $jwt, ?string $language = null) : InfoResponse {
        $requestBuilder = new InfoRequestBuilder($this->httpClient, $jwt);
        if($language) {
            $requestBuilder = $requestBuilder->forQueryParameters(["lang" => $language]);
        }
        return $requestBuilder->execute();
    }

    /**
     * A deposit is when a user sends an external token (BTC via Bitcoin, USD via bank transfer, etc...)
     * to an address held by an anchor. In turn, the anchor sends an equal amount of tokens on the
     * Stellar network (minus fees) to the user's Stellar account.
     * The deposit endpoint allows a wallet to get deposit information from an anchor, so a user has
     * all the information needed to initiate a deposit. It also lets the anchor specify
     * additional information (if desired) that the user must submit via the /customer endpoint
     * to be able to deposit.
     * @param DepositRequest $request request
     * @return DepositResponse response in case of success
     * @throws CustomerInformationNeededException The anchor needs more information about the customer.
     * @throws CustomerInformationStatusException Customer information was submitted for the account, but the information is either still being processed or was not accepted.
     * @throws GuzzleException if a request error occurs.
     */
    public function deposit(DepositRequest $request) : DepositResponse {
        $requestBuilder = new DepositRequestBuilder($this->httpClient, $request->jwt);
        $queryParameters = array();
        $queryParameters += ["asset_code" => $request->assetCode];
        $queryParameters += ["account" => $request->account];
        if ($request->memoType) {
            $queryParameters += ["memo_type" => $request->memoType];
        }
        if ($request->memo) {
            $queryParameters += ["memo" => $request->memo];
        }
        if ($request->emailAddress) {
            $queryParameters += ["email_address" => $request->emailAddress];
        }
        if ($request->type) {
            $queryParameters += ["type" => $request->type];
        }
        if ($request->walletName) {
            $queryParameters += ["wallet_name" => $request->walletName];
        }
        if ($request->walletUrl) {
            $queryParameters += ["wallet_url" => $request->walletUrl];
        }
        if ($request->lang) {
            $queryParameters += ["lang" => $request->lang];
        }
        if ($request->onChangeCallback) {
            $queryParameters += ["on_change_callback" => $request->onChangeCallback];
        }
        if ($request->amount) {
            $queryParameters += ["amount" => $request->amount];
        }
        if ($request->countryCode) {
            $queryParameters += ["country_code" => $request->countryCode];
        }
        if ($request->claimableBalanceSupported) {
            $queryParameters += ["claimable_balance_supported" => $request->claimableBalanceSupported];
        }

        $requestBuilder = $requestBuilder->forQueryParameters($queryParameters);
        return $requestBuilder->execute();
    }

    /**
     * @param WithdrawRequest $request request
     * @return WithdrawResponse response if success
     * @throws CustomerInformationNeededException The anchor needs more information about the customer.
     * @throws CustomerInformationStatusException Customer information was submitted for the account, but the information is either still being processed or was not accepted.
     * @throws GuzzleException in case of request error
     */
    public function withdraw(WithdrawRequest $request) : WithdrawResponse {
        $requestBuilder = new WithdrawRequestBuilder($this->httpClient, $request->jwt);
        $queryParameters = array();
        $queryParameters += ["asset_code" => $request->assetCode];
        $queryParameters += ["type" => $request->type];
        $queryParameters += ["dest" => $request->dest];
        if ($request->destExtra) {
            $queryParameters += ["dest_extra" => $request->destExtra];
        }
        if ($request->account) {
            $queryParameters += ["account" => $request->account];
        }
        if ($request->memo) {
            $queryParameters += ["memo" => $request->memo];
        }
        if ($request->memoType) {
            $queryParameters += ["memo_type" => $request->memoType];
        }
        if ($request->walletName) {
            $queryParameters += ["wallet_name" => $request->walletName];
        }
        if ($request->walletUrl) {
            $queryParameters += ["wallet_url" => $request->walletUrl];
        }
        if ($request->lang) {
            $queryParameters += ["lang" => $request->lang];
        }
        if ($request->onChangeCallback) {
            $queryParameters += ["on_change_callback" => $request->onChangeCallback];
        }
        if ($request->amount) {
            $queryParameters += ["amount" => $request->amount];
        }
        if ($request->countryCode) {
            $queryParameters += ["country_code" => $request->countryCode];
        }

        $requestBuilder = $requestBuilder->forQueryParameters($queryParameters);
        return $requestBuilder->execute();
    }

    /**
     * @param FeeRequest $request
     * @return FeeResponse
     * @throws GuzzleException
     */
    public function fee(FeeRequest $request) : FeeResponse {
        $requestBuilder = new FeeRequestBuilder($this->httpClient, $request->jwt);
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
     * With it, wallets can display the status of deposits and withdrawals while they process and a history of
     * past transactions with the anchor. It's only for transactions that are deposits to or withdrawals from the anchor.
     * @param AnchorTransactionsRequest $request
     * @return AnchorTransactionsResponse
     * @throws GuzzleException
     */
    public function transactions(AnchorTransactionsRequest $request) : AnchorTransactionsResponse {
        $requestBuilder = new AnchorTransactionsRequestBuilder($this->httpClient, $request->jwt);
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

        $requestBuilder = $requestBuilder->forQueryParameters($queryParameters);
        return $requestBuilder->execute();
    }

    /**
     * The transaction endpoint enables clients to query/validate a specific transaction at an anchor.
     * @param AnchorTransactionRequest $request
     * @return AnchorTransactionResponse
     * @throws GuzzleException
     */
    public function transaction(AnchorTransactionRequest $request) : AnchorTransactionResponse {
        $requestBuilder = new AnchorTransactionRequestBuilder($this->httpClient, $request->jwt);
        $queryParameters = array();

        if ($request->id) {
            $queryParameters += ["id" => $request->id];
        }

        if ($request->stallarTransactionId) {
            $queryParameters += ["stellar_transaction_id" => $request->stallarTransactionId];
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
        $headers = array_merge($headers, ['Authorization' => "Bearer " . $request->jwt]);
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