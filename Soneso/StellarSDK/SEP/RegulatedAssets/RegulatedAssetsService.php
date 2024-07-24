<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\RegulatedAssets;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\Toml\StellarToml;
use Soneso\StellarSDK\StellarSDK;

/**
 * Implements SEP-0008 - Regulated Assets.
 * See <a href="https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-00008.md" target="_blank">Regulated Assets</a>
 */
class RegulatedAssetsService
{
    public StellarToml $tomlData;
    public StellarSDK $sdk;
    public Network $network;
    /**
     * @var array<RegulatedAsset> $regulatedAssets
     */
    public array $regulatedAssets = array();

    private Client $httpClient;

    /**
     * Constructor
     * @param StellarToml $tomlData toml data obtained via SEP01 from the server
     * @param String|null $horizonUrl (optional) horizon url used to check if an asset needs authorization. If not provided
     * it will be extracted from the toml data. If not provided by the toml data the constructor will throw an exception
     * @param Network|null $network (optional) stellar network to be used. If not provided, the constructor will try to extract it from the stellar toml data.
     * @param Client|null $httpClient (optional) http client to be used for requests
     * @throws SEP08IncompleteInitData if horizon url or network passphrase could not be found
     */
    public function __construct(StellarToml $tomlData, ?String $horizonUrl = null, ?Network $network = null, ?Client $httpClient = null)
    {
        if ($httpClient != null) {
            $this->httpClient = $httpClient;
        } else {
            $this->httpClient = new Client();
        }

        if ($horizonUrl !== null) {
            $this->sdk = new StellarSDK($horizonUrl);
        }

        if ($network !== null) {
            $this->network = $network;
        }

        $tomlDataNetworkPassphrase = $tomlData->getGeneralInformation()?->networkPassphrase;


        if ($network === null && $tomlDataNetworkPassphrase !== null) {
            $this->network = new Network($tomlDataNetworkPassphrase);
        } else {
            throw new SEP08IncompleteInitData('could not find a network passphrase');
        }

        $tomlDataHorizonUrl = $tomlData->getGeneralInformation()?->horizonUrl;
        if ($horizonUrl == null && $tomlDataHorizonUrl != null) {
            $this->sdk = new StellarSDK($tomlDataHorizonUrl);
        } else if ($horizonUrl == null) {
            // try to init from known horizon urls
            if ($this->network->getNetworkPassphrase() == Network::public()->getNetworkPassphrase()) {
                $this->sdk = StellarSDK::getPublicNetInstance();
            } else if ($this->network->getNetworkPassphrase() == Network::testnet()->getNetworkPassphrase()) {
                $this->sdk = StellarSDK::getTestNetInstance();
            } else if ($this->network->getNetworkPassphrase() == Network::futurenet()->getNetworkPassphrase()) {
                $this->sdk = StellarSDK::getFutureNetInstance();
            } else {
                throw new SEP08IncompleteInitData("could not find a horizon url");
            }
        }

        $tomlDataCurrencies = $tomlData->getCurrencies();
        if ($tomlDataCurrencies !== null) {
            foreach($tomlDataCurrencies as $currency) {
                if ($currency->issuer !== null && $currency->code !== null && $currency->regulated && $currency->approvalServer !== null) {
                    $this->regulatedAssets[] = new RegulatedAsset(
                        code: $currency->code,
                        issuer: $currency->issuer,
                        approvalServer: $currency->approvalServer,
                        approvalCriteria: $currency->approvalCriteria,
                    );
                }
            }
        }
    }

    /**
     * Creates an instance of this class by loading the toml data from the given domain's stellar toml file.
     * @param String $domain to load the stellar toml file from
     * @return RegulatedAssetsService the initialized RegulatedAssetsService
     * @throws Exception if the stellar toml file could not be loaded or data is invalid
     */
    public static function fromDomain(
        String $domain,
        ?String $horizonUrl = null,
        ?Network $network = null,
        ?Client $httpClient = null,
    ): RegulatedAssetsService
    {
        $stellarToml = StellarToml::fromDomain($domain, $httpClient);
        return new RegulatedAssetsService(
            $stellarToml,
            horizonUrl: $horizonUrl,
            network: $network,
            httpClient: $httpClient,
        );
    }

    /**
     * Checks if authorization is required for the given asset.
     * To do so, it loads the issuer account data from the stellar network
     * and checks if the both flags 'authRequired' and 'authRevocable' are set.
     * @throws HorizonRequestException if the issuer account data could not be loaded from the stellar network.
     * @return bool true if authorization is required
     */
    public function authorizationRequired(RegulatedAsset $asset) : bool {
        $issuerAccount = $this->sdk->requestAccount($asset->getIssuer());
        return $issuerAccount->getFlags()->isAuthRequired() && $issuerAccount->getFlags()->isAuthRevocable();
    }

    /**
     * Sends a transaction to be evaluated and signed by the approval server.
     * @param String $tx transaction base64 xdr
     * @param String $approvalServer url of the approval server
     * @return SEP08PostTransactionResponse the response
     * @throws SEP08InvalidPostTransactionResponse if the response data is invalid
     * @throws GuzzleException if a connection error occurs
     */
    public function postTransaction(String $tx, String $approvalServer) : SEP08PostTransactionResponse {

        $response = $this->httpClient->post($approvalServer, [RequestOptions::JSON => ['tx' => $tx], 'http_errors' => false]);

        $statusCode = $response->getStatusCode();
        $content = $response->getBody()->__toString();
        $jsonData = @json_decode($content, true);
        if ((200 === $statusCode && null !== $jsonData) ||
            (400 === $statusCode && null !== $jsonData && isset($jsonData['error']))) {
            return SEP08PostTransactionResponse::fromJson($jsonData);
        } else {
            $errorMsg = $content;
            if (null !== $jsonData && isset($jsonData['error'])) {
                $errorMsg = $jsonData['error'];
            }
            throw new SEP08InvalidPostTransactionResponse($errorMsg, $statusCode);
        }
    }

    /**
     * Post action if action is required.
     * @param String $url url to post action to
     * @param array<array-key, mixed> $actionFields action fields to send
     * @return SEP08PostActionResponse response
     * @throws SEP08InvalidPostActionResponse if the response data is invalid
     * @throws GuzzleException if a connection error occurs
    */
    public function postAction(String $url, array $actionFields) : SEP08PostActionResponse {
        $response = $this->httpClient->post($url, [RequestOptions::JSON => $actionFields, 'http_errors' => false]);
        $statusCode = $response->getStatusCode();
        $content = $response->getBody()->__toString();
        $jsonData = @json_decode($content, true);
        if (200 === $statusCode && null !== $jsonData) {
            return SEP08PostActionResponse::fromJson($jsonData);
        } else {
            $errorMsg = $content;
            throw new SEP08InvalidPostActionResponse($errorMsg, $statusCode);
        }
    }

}