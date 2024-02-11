<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

use DateTimeInterface;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use Soneso\StellarSDK\SEP\Toml\StellarToml;

/**
 * Implements SEP-0024 - Hosted Deposit and Withdrawal.
 * See <https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0024.md" target="_blank">Hosted Deposit and Withdrawal</a>
 */
class InteractiveService
{
    private string $serviceAddress;
    private Client $httpClient;

    /**
     * Constructor.
     * @param string $serviceAddress the server address of the sep-24 service (e.g. from sep-01).
     * @param Client|null $httpClient optional http client to be used for the requests. If not provided, then uses an own client.
     */
    public function __construct(string $serviceAddress, ?Client $httpClient = null)
    {
        $this->serviceAddress = $serviceAddress;
        if (substr($this->serviceAddress, -1) === "/") {
            $this->serviceAddress = substr($this->serviceAddress, 0, -1);
        }
        if ($httpClient === null) {
            $this->httpClient = new Client([
                'exceptions' => false,
            ]);
        } else {
            $this->httpClient = $httpClient;
        }
    }

    /**
     * Constructs an InteractiveService instance by parsing the server service address from the given domain using sep-01.
     * @param string $domain the domain to parse the data from. e.g. 'testanchor.stellar.org'.
     * @param Client|null $httpClient optional http client to be used for the requests. If not provided, then uses an own client.
     * @return InteractiveService the constructed InteractiveService object.
     * @throws Exception if the service address could not be loaded from the given domain.
     */
    public static function fromDomain(string $domain, ?Client $httpClient = null) : InteractiveService {
        $stellarToml = StellarToml::fromDomain($domain, $httpClient);
        $address = $stellarToml->getGeneralInformation()->transferServerSep24;
        if (!$address) {
            throw new Exception("Transfer server SEP 24 not available for domain " . $domain);
        }
        return new InteractiveService($address, $httpClient);
    }

    /**
     * Get the anchors basic info about what their TRANSFER_SERVER_SEP0024 support to wallets and clients.
     * @param string|null $lang (optional) Language code specified using ISO 639-1. description fields in the response should be in this language. Defaults to en.
     * @return SEP24InfoResponse response the parsed response.
     * @throws GuzzleException if a request exception occurs.
     */
    public function info(?string $lang = null) : SEP24InfoResponse {
        $requestBuilder = new InfoRequestBuilder($this->httpClient, $this->serviceAddress, $this->serviceAddress);
        if($lang) {
            $requestBuilder = $requestBuilder->forQueryParameters(["lang" => $lang]);
        }
        return $requestBuilder->execute();
    }

    /**
     * Get the anchor's to reported fee that would be charged for a given deposit or withdraw operation.
     * This is important to allow an anchor to accurately report fees to a user even when the fee schedule is complex.
     * If a fee can be fully expressed with the fee_fixed, fee_percent or fee_minimum fields in the /info response,
     * then an anchor will not implement this endpoint.
     * @param SEP24FeeRequest $request the request data.
     * @return SEP24FeeResponse the parsed response.
     * @throws RequestErrorException if the server responds with an error and corresponding error message.
     * @throws SEP24AuthenticationRequiredException if the server responds with an authentication_required error.
     * @throws GuzzleException if another request error occurred.
     */
    public function fee(SEP24FeeRequest $request) : SEP24FeeResponse {
        $requestBuilder = new FeeRequestBuilder($this->httpClient, $this->serviceAddress, $request->jwt);

        /**
         * @var array<array-key, mixed> $queryParameters
         */
        $queryParameters = ["operation" => $request->operation,
            "asset_code" => $request->assetCode, "amount" => $request->amount];
        if ($request->type != null) {
            $queryParameters ["type"] = $request->type;
        }

        $requestBuilder = $requestBuilder->forQueryParameters($queryParameters);

        try {

            return $requestBuilder->execute();
        } catch (Exception $ex) {
            if ($ex->getCode() == 403) {
                $this->handleForbiddenResponse($ex);
            } else if ($ex->getCode() != 200) {
                $this->handleErrorResponse($ex);
            }
            throw $ex;
        }
    }


    /**
     * @throws RequestErrorException
     * @throws Exception
     */
    private function handleErrorResponse(Exception $ex) {
        if ($ex instanceof BadResponseException) {
            $response = $ex->getResponse();
            $content = $response->getBody()->__toString();

            $jsonData = @json_decode($content, true);

            if (null !== $jsonData && json_last_error() == JSON_ERROR_NONE) {
                if (isset($jsonData['error'])) {
                    throw new RequestErrorException(strval($jsonData['error']), $ex->getCode(), $ex);
                }
            }
        } else if ($ex instanceof \RuntimeException) {
            throw new RequestErrorException($ex->getMessage(), $ex->getCode(), $ex);
        }

        throw $ex;
    }

    /**
     * @throws SEP24AuthenticationRequiredException
     * @throws Exception
     */
    private function handleForbiddenResponse(Exception $ex) {
        if ($ex instanceof BadResponseException) {
            $response = $ex->getResponse();
            $content = $response->getBody()->__toString();

            $jsonData = @json_decode($content, true);

            if (null !== $jsonData && json_last_error() == JSON_ERROR_NONE) {
                if (isset($jsonData['type']) && 'authentication_required' === $jsonData['type']) {
                    throw new SEP24AuthenticationRequiredException("The endpoint requires authentication.", 403, $ex);
                }
            }
        } else if ($ex instanceof \RuntimeException) {
            throw new SEP24AuthenticationRequiredException($ex->getMessage(), $ex->getCode(), $ex);
        }

        throw $ex;
    }

    /**
     * A deposit is when a user sends an external token (BTC via Bitcoin, USD via bank transfer, etc...)
     * to an address held by an anchor. In turn, the anchor sends an equal amount of tokens on the
     * Stellar network (minus fees) to the user's Stellar account.
     * The deposit endpoint allows a wallet to get deposit information from an anchor, so a user has
     * all the information needed to initiate a deposit. It also lets the anchor specify additional
     * information that the user must submit interactively via a popup or embedded browser
     * window to be able to deposit.
     * @param SEP24DepositRequest $request the request data.
     * @return SEP24InteractiveResponse the parsed response.
     * @throws RequestErrorException if the server responds with an error and corresponding error message.
     * @throws SEP24AuthenticationRequiredException if the server responds with an authentication_required error.
     * @throws GuzzleException if another request error occurred.
     */
    public function deposit(SEP24DepositRequest $request) : SEP24InteractiveResponse {

        /**
         * @var array<array-key, mixed> $fields
         */
        $fields = ["asset_code" => $request->assetCode];

        /**
         * @var array<array-key, mixed> $files
         */
        $files = array();

        if ($request->assetIssuer != null) {
            $fields += ["asset_issuer" => $request->assetIssuer];
        }
        if ($request->sourceAsset != null) {
            $fields += ["source_asset" => $request->sourceAsset];
        }
        if ($request->amount != null) {
            $fields += ["amount" => $request->amount];
        }
        if ($request->quoteId != null) {
            $fields += ["quote_id" => $request->quoteId];
        }
        if ($request->account != null) {
            $fields += ["account" => $request->account];
        }
        if ($request->memoType != null) {
            $fields += ["memo_type" => $request->memoType];
        }
        if ($request->memo != null) {
            $fields += ["memo" => $request->memo];
        }
        if ($request->walletName != null) {
            $fields += ["wallet_name" => $request->walletName];
        }
        if ($request->walletUrl != null) {
            $fields += ["wallet_url" => $request->walletUrl];
        }
        if ($request->lang != null) {
            $fields += ["lang" => $request->lang];
        }
        if ($request->claimableBalanceSupported != null) {
            $fields += ["claimable_balance_supported" => $request->claimableBalanceSupported];
        }
        if ($request->customerId != null) {
            $fields += ["customer_id" => $request->customerId];
        }
        if ($request->kycFields != null && $request->kycFields->naturalPersonKYCFields != null) {
            $fields += $request->kycFields->naturalPersonKYCFields->fields();
        }
        if ($request->kycFields != null && $request->kycFields->organizationKYCFields != null) {
            $fields += $request->kycFields->organizationKYCFields->fields();
        }
        if ($request->kycFields != null && $request->kycFields->financialAccountKYCFields != null) {
            $fields += $request->kycFields->financialAccountKYCFields->fields();
        }
        if ($request->customFields != null) {
            $fields += $request->customFields;
        }
        if ($request->kycFields != null && $request->kycFields->naturalPersonKYCFields != null) {
            $files += $request->kycFields->naturalPersonKYCFields->files();
        }
        if ($request->kycFields != null && $request->kycFields->organizationKYCFields != null) {
            $files += $request->kycFields->organizationKYCFields->files();
        }
        if ($request->customFiles != null) {
            $files += $request->customFiles;
        }

        $requestBuilder = new Sep24PostRequestBuilder(
            httpClient: $this->httpClient,
            serviceAddress: $this->serviceAddress,
            endpoint: "transactions/deposit/interactive",
            jwtToken: $request->jwt,
            fields: $fields,
            files: $files,
        );

        try {

            return $requestBuilder->execute();
        } catch (Exception $ex) {
            if ($ex->getCode() == 403) {
                $this->handleForbiddenResponse($ex);
            } else if ($ex->getCode() != 200) {
                $this->handleErrorResponse($ex);
            }
            throw $ex;
        }
    }

    /**
     * This operation allows a user to redeem an asset currently on the Stellar network for the real asset (BTC, USD, stock, etc...) via the anchor of the Stellar asset.
     * The withdraw endpoint allows a wallet to get withdrawal information from an anchor, so a user has all the information needed to initiate a withdrawal.
     * It also lets the anchor specify the url for the interactive webapp to continue with the anchor's side of the withdrawal.
     * @param SEP24WithdrawRequest $request the request data
     * @return SEP24InteractiveResponse the parsed response.
     * @throws RequestErrorException if the server responds with an error and corresponding error message.
     * @throws SEP24AuthenticationRequiredException if the server responds with an authentication_required error.
     * @throws GuzzleException if another request exception occurred.
     */
    public function withdraw(SEP24WithdrawRequest $request) : SEP24InteractiveResponse {
        /**
         * @var array<array-key, mixed> $fields
         */
        $fields = ["asset_code" => $request->assetCode];

        /**
         * @var array<array-key, mixed> $files
         */
        $files = array();

        if ($request->destinationAsset != null) {
            $fields += ["destination_asset" => $request->destinationAsset];
        }
        if ($request->assetIssuer != null) {
            $fields += ["asset_issuer" => $request->assetIssuer];
        }
        if ($request->amount != null) {
            $fields += ["amount" => $request->amount];
        }
        if ($request->quoteId != null) {
            $fields += ["quote_id" => $request->quoteId];
        }
        if ($request->account != null) {
            $fields += ["account" => $request->account];
        }
        if ($request->memoType != null) {
            $fields += ["memo_type" => $request->memoType];
        }
        if ($request->memo != null) {
            $fields += ["memo" => $request->memo];
        }
        if ($request->walletName != null) {
            $fields += ["wallet_name" => $request->walletName];
        }
        if ($request->walletUrl != null) {
            $fields += ["wallet_url" => $request->walletUrl];
        }
        if ($request->lang != null) {
            $fields += ["lang" => $request->lang];
        }
        if ($request->refundMemo != null) {
            $fields += ["refund_memo" => $request->refundMemo];
        }
        if ($request->refundMemoType != null) {
            $fields += ["refund_memo_type" => $request->refundMemoType];
        }
        if ($request->customerId != null) {
            $fields += ["customer_id" => $request->customerId];
        }
        if ($request->kycFields != null && $request->kycFields->naturalPersonKYCFields != null) {
            $fields += $request->kycFields->naturalPersonKYCFields->fields();
        }
        if ($request->kycFields != null && $request->kycFields->organizationKYCFields != null) {
            $fields += $request->kycFields->organizationKYCFields->fields();
        }
        if ($request->kycFields != null && $request->kycFields->financialAccountKYCFields != null) {
            $fields += $request->kycFields->financialAccountKYCFields->fields();
        }
        if ($request->customFields != null) {
            $fields += $request->customFields;
        }
        if ($request->kycFields != null && $request->kycFields->naturalPersonKYCFields != null) {
            $files += $request->kycFields->naturalPersonKYCFields->files();
        }
        if ($request->kycFields != null && $request->kycFields->organizationKYCFields != null) {
            $files += $request->kycFields->organizationKYCFields->files();
        }
        if ($request->customFiles != null) {
            $files += $request->customFiles;
        }

        $requestBuilder = new Sep24PostRequestBuilder(
            httpClient: $this->httpClient,
            serviceAddress: $this->serviceAddress,
            endpoint: "transactions/withdraw/interactive",
            jwtToken: $request->jwt,
            fields: $fields,
            files: $files,
        );

        try {

            return $requestBuilder->execute();
        } catch (Exception $ex) {
            if ($ex->getCode() == 403) {
                $this->handleForbiddenResponse($ex);
            } else if ($ex->getCode() != 200) {
                $this->handleErrorResponse($ex);
            }
            throw $ex;
        }
    }

    /**
     * The transaction history endpoint helps anchors enable a better experience for users using an external wallet.
     * With it, wallets can display the status of deposits and withdrawals while they process and a history of past transactions with the anchor.
     * It's only for transactions that are deposits to or withdrawals from the anchor.
     * It returns a list of transactions from the account encoded in the authenticated JWT.
     * @param SEP24TransactionsRequest $request the request data.
     * @return SEP24TransactionsResponse the parsed response.
     * @throws RequestErrorException if the server responds with an error and corresponding error message.
     * @throws SEP24AuthenticationRequiredException if the server responds with an authentication_required error.
     * @throws GuzzleException if another request error occurred.
     */
    public function transactions(SEP24TransactionsRequest $request) : SEP24TransactionsResponse {
        $requestBuilder = new AnchorTransactionsRequestBuilder($this->httpClient, $this->serviceAddress, $request->jwt);

        /**
         * @var array<array-key, mixed> $queryParameters
         */
        $queryParameters = ["asset_code" => $request->assetCode];

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
        try {

            return $requestBuilder->execute();
        } catch (Exception $ex) {
            if ($ex->getCode() == 403) {
                $this->handleForbiddenResponse($ex);
            } else if ($ex->getCode() != 200) {
                $this->handleErrorResponse($ex);
            }
            throw $ex;
        }
    }


    /**
     * The transaction endpoint enables clients to query/validate a specific transaction at an anchor.
     * Anchors must ensure that the SEP-10 JWT included in the request contains the Stellar account
     * and optional memo value used when making the original deposit or withdraw request
     * that resulted in the transaction requested using this endpoint.
     * @param SEP24TransactionRequest $request the request data.
     * @return SEP24TransactionResponse the parsed response.
     * @throws RequestErrorException if the server responds with an error and corresponding error message.
     * @throws SEP24AuthenticationRequiredException if the server responds with an authentication_required error.
     * @throws SEP24TransactionNotFoundException if the anchor could not find the transaction.
     * @throws GuzzleException if another request error occurred.
     */
    public function transaction(SEP24TransactionRequest $request) : SEP24TransactionResponse {
        $requestBuilder = new AnchorTransactionRequestBuilder($this->httpClient, $this->serviceAddress, $request->jwt);

        /**
         * @var array<array-key, mixed> $queryParameters
         */
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

        if ($request->lang) {
            $queryParameters += ["lang" => $request->lang];
        }

        $requestBuilder = $requestBuilder->forQueryParameters($queryParameters);
        try {

            return $requestBuilder->execute();
        } catch (Exception $ex) {
            if ($ex->getCode() == 403) {
                $this->handleForbiddenResponse($ex);
            }
            else if ($ex->getCode() == 404) {
                throw new SEP24TransactionNotFoundException("The anchor could not find the transaction.", 404, $ex);
            } else if ($ex->getCode() != 200) {
                $this->handleErrorResponse($ex);
            }
            throw $ex;
        }
    }

    public function setMockHandlerStack(HandlerStack $handlerStack) {
        $this->httpClient = new Client(['handler' => $handlerStack]);
    }
}