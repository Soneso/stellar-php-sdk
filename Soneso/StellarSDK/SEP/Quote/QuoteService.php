<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Quote;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use Soneso\StellarSDK\Requests\RequestBuilder;
use Soneso\StellarSDK\SEP\Toml\StellarToml;

/**
 * Implements SEP-0038 - Anchor RFQ API.
 * See <a href="https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md" target="_blank">Anchor RFQ API.</a>
 */
class QuoteService
{
    private string $serviceAddress;
    private Client $httpClient;

    /**
     * Constructor.
     * @param string $serviceAddress for the server (ANCHOR_QUOTE_SERVER in stellar.toml).
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
     * Creates an instance of this class by loading the anchor quote server SEP-38 url from the given domain stellar toml file (ANCHOR_QUOTE_SERVER).
     * @param string $domain to load the service address from.
     * @return QuoteService the initialized QuoteService
     * @throws Exception if the loading of the service address for the given domain failed.
     */
    public static function fromDomain(string $domain, ?Client $httpClient = null) : QuoteService {
        $stellarToml = StellarToml::fromDomain($domain, $httpClient);
        $address = $stellarToml->getGeneralInformation()->anchorQuoteServer;
        if (!$address) {
            throw new Exception("No anchor quote service found in stellar.toml");
        }
        return new QuoteService($address, $httpClient);
    }

    /**
     * This endpoint returns the supported Stellar assets and off-chain assets available for trading.
     * See: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md#get-info
     *
     * @param string|null $jwt optional jwtToken token obtained before with SEP-0010.
     * @return SEP38InfoResponse object containing the response data.
     * @throws GuzzleException
     * @throws SEP38BadRequestException
     * @throws SEP38UnknownResponseException
     */
    public function info(?string $jwt = null) : SEP38InfoResponse {

        $url = $this->buildServiceUrl("info");

        $response = $this->httpClient->get($url,
            [RequestOptions::HEADERS => $this->buildHeaders($jwt), 'http_errors' => false]);

        $statusCode = $response->getStatusCode();
        $content = $response->getBody()->__toString();
        $jsonData = @json_decode($content, true);
        if (200 === $statusCode) {
            return SEP38InfoResponse::fromJson($jsonData);
        } else {
            $errorMsg = $content;
            if (null !== $jsonData && isset($jsonData['error'])) {
                $errorMsg = $jsonData['error'];
            }
            if (400 === $statusCode) {
                throw new SEP38BadRequestException($errorMsg, $statusCode);
            } else {
                throw new SEP38UnknownResponseException($errorMsg, $statusCode);
            }
        }
    }


    /**
     * This endpoint can be used to fetch the indicative prices of available off-chain assets in exchange for a Stellar asset and vice versa.
     *
     * @param string $sellAsset The asset you want to sell, using the Asset Identification Format.
     * @param string $sellAmount The amount of sell_asset the client would exchange for each of the buy_assets.
     * @param string|null $sellDeliveryMethod Optional, one of the name values specified by the sell_delivery_methods array for the associated asset returned from GET /info. Can be provided if the user is delivering an off-chain asset to the anchor but is not strictly required.
     * @param string|null $buyDeliveryMethod Optional, one of the name values specified by the buy_delivery_methods array for the associated asset returned from GET /info. Can be provided if the user intends to receive an off-chain asset from the anchor but is not strictly required.
     * @param string|null $countryCode Optional, The ISO 3166-2 or ISO-3166-1 alpha-2 code of the user's current address. Should be provided if there are two or more country codes available for the desired asset in GET /info.
     * @param string|null $jwt Optional, token obtained before with SEP-0010.
     * @return SEP38PricesResponse Object containing the response data.
     * @throws GuzzleException
     * @throws SEP38BadRequestException
     * @throws SEP38UnknownResponseException
     */
    public function prices(
        string $sellAsset,
        string $sellAmount,
        ?string $sellDeliveryMethod = null,
        ?string $buyDeliveryMethod = null,
        ?string $countryCode = null,
        ?string $jwt = null,
    ) : SEP38PricesResponse {

        $url = $this->buildServiceUrl("prices");
        $url .= '?sell_asset=' . $sellAsset . '&sell_amount=' . $sellAmount;

        if ($sellDeliveryMethod !== null) {
            $url .= '&sell_delivery_method=' . $sellDeliveryMethod;
        }
        if ($buyDeliveryMethod !== null) {
            $url .= '&buy_delivery_method=' . $buyDeliveryMethod;
        }
        if ($countryCode !== null) {
            $url .= '&country_code=' . $countryCode;
        }

        $response = $this->httpClient->get($url,
            [RequestOptions::HEADERS => $this->buildHeaders($jwt), 'http_errors' => false]);

        $statusCode = $response->getStatusCode();
        $content = $response->getBody()->__toString();
        $jsonData = @json_decode($content, true);
        if (200 === $statusCode) {
            return SEP38PricesResponse::fromJson($jsonData);
        } else {
            $errorMsg = $content;
            if (null !== $jsonData && isset($jsonData['error'])) {
                $errorMsg = $jsonData['error'];
            }
            if (400 === $statusCode) {
                throw new SEP38BadRequestException($errorMsg, $statusCode);
            } else {
                throw new SEP38UnknownResponseException($errorMsg, $statusCode);
            }
        }
    }

    /**
     * This endpoint can be used to fetch the indicative price for a given asset pair.
     * See: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md#get-price
     * The client must provide either sellAmount or buyAmount, but not both.
     *
     * @param string $context The context for what this quote will be used for. Must be one of 'sep6' or 'sep31'.
     * @param string $sellAsset The asset the client would like to sell. Ex. stellar:USDC:G..., iso4217:ARS
     * @param string $buyAsset The asset the client would like to exchange for sellAsset.
     * @param string|null $sellAmount Optional, the amount of sellAsset the client would like to exchange for buyAsset.
     * @param string|null $buyAmount Optional, the amount of buyAsset the client would like to exchange for sellAsset.
     * @param string|null $sellDeliveryMethod Optional, one of the name values specified by the sell_delivery_methods array for the associated asset returned from GET /info. Can be provided if the user is delivering an off-chain asset to the anchor but is not strictly required.
     * @param string|null $buyDeliveryMethod Optional, one of the name values specified by the buy_delivery_methods array for the associated asset returned from GET /info. Can be provided if the user intends to receive an off-chain asset from the anchor but is not strictly required.
     * @param string|null $countryCode Optional, The ISO 3166-2 or ISO-3166-1 alpha-2 code of the user's current address. Should be provided if there are two or more country codes available for the desired asset in GET /info.
     * @param string|null $jwt Optional, token obtained before with SEP-0010.
     * @return SEP38PriceResponse Object containing the response data.
     * @throws GuzzleException
     * @throws SEP38BadRequestException
     * @throws SEP38UnknownResponseException
     */
    public function price(
        string $context,
        string $sellAsset,
        string $buyAsset,
        ?string $sellAmount = null,
        ?string $buyAmount = null,
        ?string $sellDeliveryMethod = null,
        ?string $buyDeliveryMethod = null,
        ?string $countryCode = null,
        ?string $jwt = null,
    ) : SEP38PriceResponse {

        if (($sellAmount !== null && $buyAmount !== null) ||
            ($sellAmount === null && $buyAmount === null)) {
            throw new InvalidArgumentException('The caller must provide either sellAmount or buyAmount, but not both.');
        }

        $url = $this->buildServiceUrl("price");
        $url .= '?sell_asset=' . $sellAsset . '&buy_asset=' . $buyAsset .'&context=' . $context;

        if ($sellAmount !== null) {
            $url .= '&sell_amount=' . $sellAmount;
        } else if ($buyAmount !== null) {
            $url .= '&buy_amount=' . $buyAmount;
        }
        if ($sellDeliveryMethod !== null) {
            $url .= '&sell_delivery_method=' . $sellDeliveryMethod;
        }
        if ($buyDeliveryMethod !== null) {
            $url .= '&buy_delivery_method=' . $buyDeliveryMethod;
        }
        if ($countryCode !== null) {
            $url .= '&country_code=' . $countryCode;
        }

        $response = $this->httpClient->get($url,
            [RequestOptions::HEADERS => $this->buildHeaders($jwt), 'http_errors' => false]);

        $statusCode = $response->getStatusCode();
        $content = $response->getBody()->__toString();
        $jsonData = @json_decode($content, true);
        if (200 === $statusCode) {
            return SEP38PriceResponse::fromJson($jsonData);
        } else {
            $errorMsg = $content;
            if (null !== $jsonData && isset($jsonData['error'])) {
                $errorMsg = $jsonData['error'];
            }
            if (400 === $statusCode) {
                throw new SEP38BadRequestException($errorMsg, $statusCode);
            } else {
                throw new SEP38UnknownResponseException($errorMsg, $statusCode);
            }
        }
    }

    /**
     * This endpoint can be used to request a firm quote for a Stellar asset and off-chain asset pair.
     *
     * @param SEP38PostQuoteRequest $request request Data.
     * @param string $jwt jwtToken obtained with SEP-10.
     * @return SEP38QuoteResponse Object containing the response data.
     * @throws GuzzleException
     * @throws SEP38BadRequestException
     * @throws SEP38PermissionDeniedException
     * @throws SEP38UnknownResponseException
     */
    public function postQuote(SEP38PostQuoteRequest $request, string $jwt) : SEP38QuoteResponse {

        if (($request->sellAmount !== null && $request->buyAmount !== null) ||
            ($request->sellAmount === null && $request->buyAmount === null)) {
            throw new InvalidArgumentException('The caller must provide either sellAmount or buyAmount, but not both.');
        }

        $url = $this->buildServiceUrl('quote');

        $response = $this->httpClient->post($url,
            [RequestOptions::JSON => $request->toJson(),
                RequestOptions::HEADERS => $this->buildHeaders($jwt), 'http_errors' => false]);

        $statusCode = $response->getStatusCode();
        $content = $response->getBody()->__toString();
        $jsonData = @json_decode($content, true);
        if (200 === $statusCode || 201 === $statusCode) {
            return SEP38QuoteResponse::fromJson($jsonData);
        } else {
            $errorMsg = $content;
            if (null !== $jsonData && isset($jsonData['error'])) {
                $errorMsg = $jsonData['error'];
            }
            if (400 == $statusCode) {
                throw new SEP38BadRequestException($errorMsg, $statusCode);
            } elseif (403 == $statusCode) {
                throw new SEP38PermissionDeniedException($errorMsg, $statusCode);
            } else {
                throw new SEP38UnknownResponseException($errorMsg, $statusCode);
            }
        }
    }

    /**
     * This endpoint can be used to fetch a previously-provided firm quote by id.
     * @param string $id of the quote to load.
     * @param string $jwt Jwt token previously received with SEP-10.
     * @return SEP38QuoteResponse Object containing the response data.
     * @throws GuzzleException
     * @throws SEP38BadRequestException
     * @throws SEP38NotFoundException
     * @throws SEP38PermissionDeniedException
     * @throws SEP38UnknownResponseException
     */
    public function getQuote(string $id, string $jwt) : SEP38QuoteResponse {

        $url = $this->buildServiceUrl('quote/' . $id);

        $response = $this->httpClient->get($url,
            [RequestOptions::HEADERS => $this->buildHeaders($jwt), 'http_errors' => false]);

        $statusCode = $response->getStatusCode();
        $content = $response->getBody()->__toString();
        $jsonData = @json_decode($content, true);
        if (200 === $statusCode) {
            return SEP38QuoteResponse::fromJson($jsonData);
        } else {
            $errorMsg = $content;
            if (null !== $jsonData && isset($jsonData['error'])) {
                $errorMsg = $jsonData['error'];
            }
            if (400 === $statusCode) {
                throw new SEP38BadRequestException($errorMsg, $statusCode);
            } elseif (403 === $statusCode) {
                throw new SEP38PermissionDeniedException($errorMsg, $statusCode);
            } elseif (404 === $statusCode) {
                throw new SEP38NotFoundException($errorMsg, $statusCode);
            } else {
                throw new SEP38UnknownResponseException($errorMsg, $statusCode);
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