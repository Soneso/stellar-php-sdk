<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\KYCService;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\ResponseInterface;
use Soneso\StellarSDK\Requests\RequestBuilder;
use Soneso\StellarSDK\SEP\Toml\StellarToml;

class KYCService
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
     * creates a KYCService by parsing server address from stellar.toml of given domain.
     * @param string $domain
     * @return KYCService
     * @throws Exception
     */
    public static function fromDomain(string $domain) : KYCService {
        $stellarToml = StellarToml::fromDomain($domain);
        $address = $stellarToml->getGeneralInformation()->kYCServer;
        if (!$address) {
            $address = $stellarToml->getGeneralInformation()->transferServer;
        }
        if (!$address) {
            throw new Exception("No KYC service or transfer service found in stellar.toml");
        }
        return new KYCService($address);
    }


    /**
     * Check the status of a customers info (customer GET)
     * This endpoint allows clients to:
     * 1. Fetch the fields the server requires in order to register a  customer:
     * If the server does not have a customer registered for the parameters sent in the request, it will return the fields required in the response. The same response will be returned when no parameters are sent.
     * 2. Check the status of a customer that may already be registered
     * This allows clients to check whether the customers information was accepted, rejected, or still needs more info. If the server still needs more info, or the server needs updated information, it will return the fields required.
     *
     * @param GetCustomerInfoRequest $request
     * @return GetCustomerInfoResponse
     * @throws GuzzleException
     */
    public function getCustomerInfo(GetCustomerInfoRequest $request) : GetCustomerInfoResponse {
        $requestBuilder = new GetCustomerInfoRequestBuilder($this->httpClient, $request->jwt);
        $queryParameters = array();
        if ($request->id) {
            $queryParameters += ["id" => $request->id];
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
        if ($request->type) {
            $queryParameters += ["type" => $request->type];
        }
        if ($request->lang) {
            $queryParameters += ["lang" => $request->lang];
        }
        $requestBuilder = $requestBuilder->forQueryParameters($queryParameters);
        return $requestBuilder->execute();
    }

    /**
     * Upload customer information to an anchor in an authenticated and idempotent fashion.
     * @param PutCustomerInfoRequest $request
     * @return PutCustomerInfoResponse
     * @throws GuzzleException
     */
    public function putCustomerInfo(PutCustomerInfoRequest $request) : PutCustomerInfoResponse {

        $fields = array();
        if ($request->id) {
            $fields += ["id" => $request->id];
        }
        if ($request->account) {
            $fields += ["account" => $request->account];
        }
        if ($request->memo) {
            $fields += ["memo" => $request->memo];
        }
        if ($request->memoType) {
            $fields += ["memo_type" => $request->memoType];
        }
        if ($request->type) {
            $fields += ["type" => $request->type];
        }
        if ($request->KYCFields?->naturalPersonKYCFields) {
            $fields = array_merge($fields, $request->KYCFields?->naturalPersonKYCFields->fields());
        }
        if ($request->KYCFields?->organizationKYCFields) {
            $fields = array_merge($fields, $request->KYCFields?->organizationKYCFields->fields());
        }
        if ($request->customFields) {
            $fields = array_merge($fields, $request->customFields);
        }

        $files = array();
        if ($request->KYCFields?->naturalPersonKYCFields) {
            $files = array_merge($files, $request->KYCFields?->naturalPersonKYCFields->files());
        }
        if ($request->KYCFields?->organizationKYCFields) {
            $files = array_merge($files, $request->KYCFields?->organizationKYCFields->files());
        }
        if ($request->customFiles) {
            $files = array_merge($files, $request->customFiles);
        }
        if (count($files) == 0) {
            $files = null;
        }

        $requestBuilder = new PutCustomerInfoRequestBuilder($this->httpClient, $fields, $files, $request->jwt);
        return $requestBuilder->execute();
    }

    /**
     * This endpoint allows servers to accept data values, usually confirmation codes, that verify a previously provided field via PUT /customer,
     * such as mobile_number or email_address.
     * See: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md#customer-put-verification
     * @param PutCustomerVerificationRequest $request
     * @return GetCustomerInfoResponse
     * @throws GuzzleException
     */
    public function putCustomerVerification(PutCustomerVerificationRequest $request) : GetCustomerInfoResponse {

        $fields = array();
        if ($request->id) {
            $fields += ["id" => $request->id];
        }
        if ($request->verificationFields) {
            $fields = array_merge($fields, $request->verificationFields);
        }

        $requestBuilder = new PutCustomerVerificationRequestBuilder($this->httpClient, $fields, $request->jwt);
        return $requestBuilder->execute();
    }

    /**
     * Delete all personal information that the anchor has stored about a given customer.
     * [account] is the Stellar account ID (G...) of the customer to delete.
     * If account does not uniquely identify an individual customer (a shared account), the client should include the [memo] and [memoType] fields in the request.
     * This request must be authenticated (via SEP-10) as coming from the owner of the account that will be deleted - [jwt].
     * @param string $account is the Stellar account ID (G...) of the customer to delete.
     * @param string $jwt jwt token from authentication (SEP-10)
     * @param string|null $memo (optional) the client-generated memo that uniquely identifies the customer. If a memo is present in the decoded SEP-10 JWT's sub value, it must match this parameter value. If a muxed account is used as the JWT's sub value, memos sent in requests must match the 64-bit integer subaccount ID of the muxed account.
     * @param string|null $memoType (deprecated, optional) type of memo. One of text, id or hash. Deprecated because memos should always be of type id, although anchors should continue to support this parameter for outdated clients. If hash, memo should be base64-encoded. If a memo is present in the decoded SEP-10 JWT's sub value, this parameter can be ignored.
     * @return ResponseInterface response
     * @throws GuzzleException if a request error occurs
     */
    public function deleteCustomer(string $account, string $jwt, ?string $memo = null, ?string $memoType = null) : ResponseInterface {
        $headers = array();
        $headers = array_merge($headers, RequestBuilder::HEADERS);
        if ($jwt) {
            $headers = array_merge($headers, ['Authorization' => "Bearer ".$jwt]);
        }

        $multipartFields = array();
        if ($memo) {
            $multipartFields += ["memo" => $memo];
        }
        if ($memoType) {
            $multipartFields += ["memo_type" => $memoType];
        }

        $multipart = array();
        foreach(array_keys($multipartFields) as $key) {
            $arr = array();
            $arr += ["name" => $key];
            $arr += ["contents" => $multipartFields[$key]];
            array_push($multipart, $arr);
        }

        $url = "/customer/" . $account;
        return $this->httpClient->request("DELETE", $url, [
            "multipart" => $multipart,
            "headers" => $headers
        ]);
    }

    /**
     * Allow the wallet to provide a callback URL to the anchor. The provided callback URL will replace (and supercede) any previously-set callback URL for this account.
     * See: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md#customer-callback-put
     * @param PutCustomerCallbackRequest $request request fields
     * @return ResponseInterface response
     * @throws GuzzleException if a request error occurs
     */
    public function putCustomerCallback(PutCustomerCallbackRequest $request) : ResponseInterface {
        $headers = array();
        $headers = array_merge($headers, RequestBuilder::HEADERS);
        if ($request->jwt) {
            $headers = array_merge($headers, ['Authorization' => "Bearer ".$request->jwt]);
        }

        $multipartFields = array();
        if ($request->url) {
            $multipartFields += ["url" => $request->url];
        }
        if ($request->id) {
            $multipartFields += ["id" => $request->id];
        }
        if ($request->account) {
            $multipartFields += ["account" => $request->account];
        }
        if ($request->memo) {
            $multipartFields += ["memo" => $request->memo];
        }
        if ($request->memoType) {
            $multipartFields += ["memo_type" => $request->memoType];
        }

        $multipart = array();
        foreach(array_keys($multipartFields) as $key) {
            $arr = array();
            $arr += ["name" => $key];
            $arr += ["contents" => $multipartFields[$key]];
            array_push($multipart, $arr);
        }

        $url = "/customer/callback";
        return $this->httpClient->request("PUT", $url, [
            "multipart" => $multipart,
            "headers" => $headers
        ]);
    }

    public function setMockHandlerStack(HandlerStack $handlerStack) {
        $this->httpClient = new Client(['handler' => $handlerStack]);
    }
}