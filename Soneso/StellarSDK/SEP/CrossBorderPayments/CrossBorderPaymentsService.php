<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\CrossBorderPayments;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Soneso\StellarSDK\Requests\RequestBuilder;
use Soneso\StellarSDK\SEP\Toml\StellarToml;

/**
 * Implements SEP-0031 - Cross-Border Payments API (for sending anchors).
 * See <a href="https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0031.md" target="_blank">Cross-Border Payments API</a>
 */
class CrossBorderPaymentsService
{
    private string $serviceAddress;
    private Client $httpClient;

    /**
     * Constructor.
     * @param string $serviceAddress for the server (DIRECT_PAYMENT_SERVER in stellar.toml).
     * @param Client|null $httpClient to be used for requests. If not provided, this service will use its own http client.
     */
    public function __construct(string $serviceAddress, ?Client $httpClient = null)
    {
        $this->serviceAddress = $serviceAddress;
        if ($httpClient != null) {
            $this->httpClient = $httpClient;
        } else {
            $this->httpClient = new Client();
        }
    }

    /**
     * Creates an instance of this class by loading the anchor direct payment server SEP-31 url from the given domain stellar toml file (DIRECT_PAYMENT_SERVER).
     * @param string $domain to load the service address from.
     * @return CrossBorderPaymentsService the initialized QuoteService
     * @param Client|null $httpClient to be used for requests. If not provided, this service will use its own http client.
     * @throws Exception if the loading of the service address for the given domain failed.
     */
    public static function fromDomain(string $domain, ?Client $httpClient = null) : CrossBorderPaymentsService {
        $stellarToml = StellarToml::fromDomain($domain, $httpClient);
        $address = $stellarToml->getGeneralInformation()->directPaymentServer;
        if (!$address) {
            throw new Exception("No anchor direct payment service found in stellar.toml");
        }
        return new CrossBorderPaymentsService($address, $httpClient);
    }

    /**
     * Allows an anchor to communicate basic info about what currencies their DIRECT_PAYMENT_SERVER supports receiving from partner anchors.
     * See: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0031.md#get-info
     *
     * @param string $jwt jwtToken token obtained before with SEP-0010.
     * @param string|null $lang  Defaults to en. Language code specified using ISO 639-1.
     * @return SEP31InfoResponse object containing the response data on response with status 200.
     * @throws GuzzleException on http exceptions.
     * @throws SEP31BadRequestException on response with status 400
     * @throws SEP31UnknownResponseException on response with other, unknown status responses.
     */
    public function info(string $jwt, ?string $lang = null) : SEP31InfoResponse {

        $url = $this->buildServiceUrl("info");

        $response = $this->httpClient->get($url,
            [RequestOptions::HEADERS => $this->buildHeaders($jwt), 'http_errors' => false]);

        $statusCode = $response->getStatusCode();
        $content = $response->getBody()->__toString();
        $jsonData = @json_decode($content, true);
        if (200 === $statusCode) {
            return SEP31InfoResponse::fromJson($jsonData);
        } else {
            $errorMsg = $content;
            if (null !== $jsonData && isset($jsonData['error'])) {
                $errorMsg = $jsonData['error'];
            }
            if (400 === $statusCode) {
                throw new SEP31BadRequestException($errorMsg, $statusCode);
            } else {
                throw new SEP31UnknownResponseException($errorMsg, $statusCode);
            }
        }
    }

    /**
     * This request initiates a payment. The Sending and Receiving Client must be registered via SEP-12 if required by the Receiving Anchor.
     * See: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0031.md#post-transactions
     * @param SEP31PostTransactionsRequest $request the request data.
     * @param string $jwt the jwt token received via SEP-10.
     * @return SEP31PostTransactionsResponse the response data if status code is 201 or 200.
     * @throws SEP31CustomerInfoNeededException if response status code is 400 and the response body error code is customer_info_needed
     * @throws SEP31TransactionInfoNeededException if response status code is 400 and the response body error code is transaction_info_needed
     * @throws SEP31BadRequestException if response status code is 400 and no response error code is present.
     * @throws SEP31UnknownResponseException if response status code is other than 201, 200 or 400.
     * @throws GuzzleException if http exception occurs.
     */
    public function postTransactions(SEP31PostTransactionsRequest $request, string $jwt) : SEP31PostTransactionsResponse {

        $url = $this->buildServiceUrl('transactions');

        $response = $this->httpClient->post($url,
            [RequestOptions::JSON => $request->toJson(),
                RequestOptions::HEADERS => $this->buildHeaders($jwt), 'http_errors' => false]);

        $statusCode = $response->getStatusCode();
        $content = $response->getBody()->__toString();
        $jsonData = @json_decode($content, true);
        if (200 === $statusCode || 201 === $statusCode) {
            return SEP31PostTransactionsResponse::fromJson($jsonData);
        } else {
            $errorMsg = $content;
            if (null !== $jsonData && isset($jsonData['error'])) {
                $errorMsg = $jsonData['error'];
            }
            if (400 == $statusCode) {
                if ($errorMsg === 'customer_info_needed') {
                    $type = null;
                    if (isset($jsonData['type'])) {
                        $type = $jsonData['type'];
                    }
                    throw new SEP31CustomerInfoNeededException(type: $type);
                } else if ($errorMsg === 'transaction_info_needed') {
                    throw new SEP31TransactionInfoNeededException(fields: $jsonData['fields']);
                }
                throw new SEP31BadRequestException($errorMsg, $statusCode);
            } else {
                throw new SEP31UnknownResponseException($errorMsg, $statusCode);
            }
        }
    }

    /**
     * The transaction endpoint enables Sending Clients to fetch information on a specific transaction with the Receiving Anchor.
     * @param string $id the id of the transaction.
     * @param string $jwt the jwt token received by SEP-10.
     *
     * @return SEP31TransactionResponse the transaction data if the response status code is 200.
     *
     * @throws SEP31TransactionNotFoundException if the response status code is 404.
     * @throws SEP31BadRequestException if the response status code is 400.
     * @throws SEP31UnknownResponseException if the response status code other than 200, 404 or 400.
     * @throws GuzzleException on http exception
     */
    public function getTransaction(string $id, string $jwt) : SEP31TransactionResponse {

        $url = $this->buildServiceUrl("transactions/" . $id);

        $response = $this->httpClient->get($url,
            [RequestOptions::HEADERS => $this->buildHeaders($jwt), 'http_errors' => false]);

        $statusCode = $response->getStatusCode();
        $content = $response->getBody()->__toString();
        $jsonData = @json_decode($content, true);
        if (200 === $statusCode) {
            return SEP31TransactionResponse::fromJson($jsonData);
        } elseif (404 == $statusCode) {
            throw new SEP31TransactionNotFoundException('transaction not found', $statusCode);
        } else {
            $errorMsg = $content;
            if (null !== $jsonData && isset($jsonData['error'])) {
                $errorMsg = $jsonData['error'];
            }
            if (400 === $statusCode) {
                throw new SEP31BadRequestException($errorMsg, $statusCode);
            } else {
                throw new SEP31UnknownResponseException($errorMsg, $statusCode);
            }
        }
    }

    /**
     * This endpoint can be used by the Sending Anchor to register a callback URL that the Receiving Anchor will make
     * application/json POST requests to containing the transaction object defined in the response to
     * GET /transaction/:id whenever the transaction's status value has changed. Note that a callback does not need to
     * be made for the initial status of the transaction, which in most cases is pending_sender.
     * @param string $id id of the transaction.
     * @param string $callbackUrl the callback url
     * @param string $jwt the jwt token received by SEP-10
     * @throws SEP31TransactionCallbackNotSupportedException on response with status code 404
     * @throws SEP31BadRequestException on response with status code 400
     * @throws SEP31UnknownResponseException if the response has a status code other than 204 or 400
     * @throws GuzzleException on http exception.
    */
    public function putTransactionCallback(string $id, string $callbackUrl, string $jwt) : void {

        $url = $this->buildServiceUrl('transactions/'.$id.'/callback');

        $response = $this->httpClient->put($url,
            [RequestOptions::JSON => ['url' => $callbackUrl],
                RequestOptions::HEADERS => $this->buildHeaders($jwt), 'http_errors' => false]);

        $statusCode = $response->getStatusCode();
        $content = $response->getBody()->__toString();
        $jsonData = @json_decode($content, true);
        if (204 === $statusCode) {
            return;
        } elseif (404 === $statusCode) {
            throw new SEP31TransactionCallbackNotSupportedException(
                'transaction callback not supported',
                $statusCode
            );
        } else {
            $errorMsg = $content;
            if (null !== $jsonData && isset($jsonData['error'])) {
                $errorMsg = $jsonData['error'];
            }
            if (400 == $statusCode) {
                throw new SEP31BadRequestException($errorMsg, $statusCode);
            } else {
                throw new SEP31UnknownResponseException($errorMsg, $statusCode);
            }
        }
    }

    /**
     * (Deprecated)
     * This endpoint should only be used when the Receiving Anchor needs more info via the
     * pending_transaction_info_update status from the Sending Anchor.
     * The required_info_updates transaction field should contain the fields required for the update.
     * If the Sending Anchor tries to update at a time when no info is requested, the Receiving Anchor should fail
     * with an error response. This approach is deprecated in favor of using per-customer fields provided via SEP-9
     * fields in SEP-12 PUT /customer requests.
     * See: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0031.md#patch-transaction-deprecated
     *
     * @param string $id the id of the transaction.
     * @param array<array-key, mixed> $fields A key-pair object containing the values requested to be updated by the
     * receiving anchor in the same format as fields in the POST /transactions request.
     *
     * @throws SEP31TransactionNotFoundException if the status code of the response is 404
     * @throws SEP31BadRequestException if the status code of the response is 400
     * @throws SEP31UnknownResponseException if the status code of the response is other than 200, 404 or 400
     * @throws GuzzleException on http exceptions.
     */
    public function patchTransaction(string $id, array $fields, string $jwt) : void {

        $url = $this->buildServiceUrl('transactions/'.$id);

        $response = $this->httpClient->patch($url,
            [RequestOptions::JSON => ['fields' => $fields],
                RequestOptions::HEADERS => $this->buildHeaders($jwt), 'http_errors' => false]);

        $statusCode = $response->getStatusCode();
        $content = $response->getBody()->__toString();
        $jsonData = @json_decode($content, true);
        if (200 === $statusCode) {
            return;
        } elseif (404 === $statusCode) {
            throw new SEP31TransactionNotFoundException(
                'transaction not found for id ' .$id,
                $statusCode
            );
        } else {
            $errorMsg = $content;
            if (null !== $jsonData && isset($jsonData['error'])) {
                $errorMsg = $jsonData['error'];
            }
            if (400 == $statusCode) {
                throw new SEP31BadRequestException($errorMsg, $statusCode);
            } else {
                throw new SEP31UnknownResponseException($errorMsg, $statusCode);
            }
        }
    }

    private function buildHeaders(?string $jwt = null) : array {
        $headers = array();
        $headers = array_merge($headers, RequestBuilder::HEADERS);
        if($jwt != null) {
            $headers = array_merge($headers, ['Authorization' => "Bearer ". $jwt]);
        }

        return $headers;
    }

    private function buildServiceUrl(string $segment): string
    {

        if (str_ends_with($this->serviceAddress, "/")) {
            return $this->serviceAddress . $segment;
        } else {
            return $this->serviceAddress . "/" . $segment;
        }
    }
}