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
use Soneso\StellarSDK\Util\UrlValidator;

/**
 * Implements SEP-0031 - Cross-Border Payments API (for sending anchors).
 *
 * SECURITY: Payment matching relies exclusively on the stellar_memo field.
 * The source account may differ from the SEP-10 authenticated account.
 * Anchors must NEVER use source account for payment matching.
 *
 * SECURITY: When handling PII/KYC data, ensure HTTPS is enforced, sensitive
 * data is encrypted at rest, and GDPR/privacy regulations are followed.
 *
 * @see <a href="https://github.com/stellar/stellar-protocol/blob/v3.1.0/ecosystem/sep-0031.md" target="_blank">SEP-31 v3.1.0</a>
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
        UrlValidator::validateHttpsRequired($serviceAddress);
        $this->serviceAddress = $serviceAddress;
        if ($httpClient !== null) {
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
     *
     * @param string $jwt JWT token obtained from SEP-10 Web Authentication using the Sending Anchor's
     * account. The authenticated account must be pre-authorized by the Receiving Anchor via bilateral agreement.
     * @param string|null $lang  Defaults to en. Language code specified using ISO 639-1.
     * @return SEP31InfoResponse object containing the response data on response with status 200 OK.
     * @throws GuzzleException on http exceptions.
     * @throws SEP31BadRequestException on response with status 400 Bad Request
     * @throws SEP31UnknownResponseException on response with other, unknown status responses.
     * @see https://github.com/stellar/stellar-protocol/blob/v3.1.0/ecosystem/sep-0031.md#get-info
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
     *
     * @param SEP31PostTransactionsRequest $request the request data.
     * @param string $jwt JWT token obtained from SEP-10 Web Authentication using the Sending Anchor's
     * account. The authenticated account must be pre-authorized by the Receiving Anchor via bilateral agreement.
     * @return SEP31PostTransactionsResponse the response data if status code is 201 Created or 200 OK.
     * @throws SEP31CustomerInfoNeededException if response status code is 400 Bad Request and the response body error code is customer_info_needed
     * @throws SEP31TransactionInfoNeededException if response status code is 400 Bad Request and the response body error code is transaction_info_needed
     * @throws SEP31BadRequestException if response status code is 400 Bad Request and no response error code is present.
     * @throws SEP31UnknownResponseException if response status code is other than 201, 200 or 400.
     * @throws GuzzleException if http exception occurs.
     * @see https://github.com/stellar/stellar-protocol/blob/v3.1.0/ecosystem/sep-0031.md#post-transactions
     * @see SEP31PostTransactionsRequest
     * @see SEP31PostTransactionsResponse
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
     *
     * @param string $id the id of the transaction.
     * @param string $jwt JWT token obtained from SEP-10 Web Authentication using the Sending Anchor's
     * account. The authenticated account must be pre-authorized by the Receiving Anchor via bilateral agreement.
     * @return SEP31TransactionResponse the transaction data if the response status code is 200 OK.
     * @throws SEP31TransactionNotFoundException if the response status code is 404 Not Found.
     * @throws SEP31BadRequestException if the response status code is 400 Bad Request.
     * @throws SEP31UnknownResponseException if the response status code other than 200, 404 or 400.
     * @throws GuzzleException on http exception
     * @see https://github.com/stellar/stellar-protocol/blob/v3.1.0/ecosystem/sep-0031.md#get-transaction
     * @see SEP31TransactionResponse
     */
    public function getTransaction(string $id, string $jwt) : SEP31TransactionResponse {

        UrlValidator::validatePathSegment($id, 'id');
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
     *
     * SECURITY: Receiving Anchors must include a signature in the Signature or X-Stellar-Signature HTTP header
     * for all callback requests. Sending Anchors must verify this signature using the Receiving Anchor's
     * SIGNING_KEY from their stellar.toml file.
     *
     * The signature covers: timestamp + "." + hostname + "." + request_body
     *
     * Sending Anchors must:
     * 1. Verify signature against Receiving Anchor's SIGNING_KEY from stellar.toml
     * 2. Check timestamp freshness (reject requests older than 1-2 minutes)
     * 3. Validate request body matches transaction schema
     * 4. Use HTTPS exclusively for callback URLs
     *
     * @param string $id id of the transaction.
     * @param string $callbackUrl the callback url (must use HTTPS)
     * @param string $jwt JWT token obtained from SEP-10 Web Authentication using the Sending Anchor's
     * account. The authenticated account must be pre-authorized by the Receiving Anchor via bilateral agreement.
     * @throws SEP31TransactionCallbackNotSupportedException on response with status code 404 Not Found
     * @throws SEP31BadRequestException on response with status code 400 Bad Request
     * @throws SEP31UnknownResponseException if the response has a status code other than 204 or 400
     * @throws GuzzleException on http exception.
     * @see https://github.com/stellar/stellar-protocol/blob/v3.1.0/ecosystem/sep-0031.md#put-transaction-callback
    */
    public function putTransactionCallback(string $id, string $callbackUrl, string $jwt) : void {

        UrlValidator::validatePathSegment($id, 'id');
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
     * with an error response.
     *
     * DEPRECATED: This approach is deprecated in favor of using per-customer fields provided via SEP-9
     * fields in SEP-12 PUT /customer requests. Use SEP-12 for all new implementations.
     *
     * @param string $id the id of the transaction.
     * @param array<array-key, mixed> $fields A key-pair object containing the values requested to be updated by the
     * receiving anchor in the same format as fields in the POST /transactions request.
     * @param string $jwt JWT token obtained from SEP-10 Web Authentication.
     * @throws SEP31TransactionNotFoundException if the status code of the response is 404 Not Found
     * @throws SEP31BadRequestException if the status code of the response is 400 Bad Request
     * @throws SEP31UnknownResponseException if the status code of the response is other than 200, 404 or 400
     * @throws GuzzleException on http exceptions.
     * @see https://github.com/stellar/stellar-protocol/blob/v3.1.0/ecosystem/sep-0031.md#patch-transaction-deprecated
     * @deprecated since SEP-31 v2.5.0, use SEP-12 PUT /customer instead
     */
    public function patchTransaction(string $id, array $fields, string $jwt) : void {

        UrlValidator::validatePathSegment($id, 'id');
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
        if($jwt !== null) {
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