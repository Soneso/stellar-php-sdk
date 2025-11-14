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
 * Service for interacting with SEP-0008 Regulated Assets approval servers.
 *
 * This service facilitates the compliance approval workflow for regulated assets by:
 * - Loading regulated asset definitions from stellar.toml files (SEP-1)
 * - Checking if assets require authorization flags
 * - Submitting transactions to approval servers
 * - Handling action_required workflows with SEP-9 fields
 *
 * Regulated assets are assets that require issuer approval for each transaction before
 * it can be submitted to the Stellar network, enabling compliance with securities regulations,
 * KYC/AML requirements, velocity limits, and jurisdiction-based restrictions.
 *
 * @package Soneso\StellarSDK\SEP\RegulatedAssets
 * @see https://github.com/stellar/stellar-protocol/blob/v1.7.4/ecosystem/sep-0008.md SEP-0008 v1.7.4 Specification
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
     * Constructs a RegulatedAssetsService instance from stellar.toml data.
     *
     * The constructor initializes the service by extracting network configuration and regulated
     * assets information from the provided stellar.toml data. It automatically identifies assets
     * that have the 'regulated' flag set to true and have an approval_server URL configured.
     *
     * Priority order for configuration:
     * 1. Parameters provided to constructor take precedence
     * 2. Values from stellar.toml data (NETWORK_PASSPHRASE, HORIZON_URL)
     * 3. Default Stellar network URLs for known networks (public, testnet, futurenet)
     *
     * @param StellarToml $tomlData Stellar.toml data obtained via SEP-1 containing currency definitions
     *                               with regulated asset information
     * @param string|null $horizonUrl (optional) Horizon server URL for checking authorization flags.
     *                                 If not provided, extracted from toml HORIZON_URL or derived from network.
     * @param Network|null $network (optional) Stellar network to use. If not provided, extracted from
     *                              toml NETWORK_PASSPHRASE field.
     * @param Client|null $httpClient (optional) Guzzle HTTP client for approval server requests.
     *                                 If not provided, a new Client instance is created.
     *
     * @throws SEP08IncompleteInitData If network passphrase or Horizon URL cannot be determined
     *                                  from any available source
     *
     * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0001.md SEP-0001 stellar.toml
     * @see https://github.com/stellar/stellar-protocol/blob/v1.7.4/ecosystem/sep-0008.md#sep-1-stellartoml SEP-0008 v1.7.4
     */
    public function __construct(StellarToml $tomlData, ?string $horizonUrl = null, ?Network $network = null, ?Client $httpClient = null)
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
     * Creates an instance by loading stellar.toml from the given domain.
     *
     * This factory method is a convenience wrapper that fetches the stellar.toml file
     * from the specified domain using SEP-1 discovery and initializes the service.
     *
     * @param string $domain Domain to load the stellar.toml file from (e.g., "example.com")
     * @param string|null $horizonUrl (optional) Horizon server URL override
     * @param Network|null $network (optional) Network override
     * @param Client|null $httpClient (optional) HTTP client for requests
     *
     * @return RegulatedAssetsService The initialized RegulatedAssetsService instance
     *
     * @throws Exception If the stellar.toml file could not be loaded or data is invalid
     * @throws SEP08IncompleteInitData If required configuration cannot be determined
     *
     * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0001.md SEP-0001 stellar.toml
     */
    public static function fromDomain(
        string $domain,
        ?string $horizonUrl = null,
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
     * Checks if authorization is required for the given regulated asset.
     *
     * Per SEP-0008 specification, regulated asset issuers MUST have both Authorization Required
     * and Authorization Revocable flags set on their account. This allows the issuer to grant
     * and revoke authorization to transact the asset at will, which is essential for the
     * per-transaction approval workflow.
     *
     * This method loads the issuer's account data from the Stellar network and verifies both
     * flags are set. Wallets should call this method before attempting to submit transactions
     * to the approval server to ensure the asset is properly configured.
     *
     * @param RegulatedAsset $asset The regulated asset to check
     *
     * @return bool True if both AUTH_REQUIRED and AUTH_REVOCABLE flags are set, false otherwise
     *
     * @throws HorizonRequestException If the issuer account data cannot be loaded from Horizon.
     *                                  This may occur if the account doesn't exist or Horizon is unreachable.
     *
     * @see https://github.com/stellar/stellar-protocol/blob/v1.7.4/ecosystem/sep-0008.md#authorization-flags SEP-0008 v1.7.4
     */
    public function authorizationRequired(RegulatedAsset $asset) : bool {
        $issuerAccount = $this->sdk->requestAccount($asset->getIssuer());
        return $issuerAccount->getFlags()->isAuthRequired() && $issuerAccount->getFlags()->isAuthRevocable();
    }

    /**
     * Submits a transaction to the approval server for compliance evaluation and signing.
     *
     * This method sends a signed transaction envelope to the approval server specified in the
     * regulated asset's stellar.toml file. The server evaluates the transaction against its
     * compliance criteria and responds with one of five possible statuses.
     *
     * Request Details:
     * - HTTP Method: POST
     * - Content-Type: application/json
     * - Request Body: {"tx": "base64_xdr_transaction_envelope"}
     * - The transaction must be signed by the user before submission
     *
     * Response Handling:
     * - HTTP 200 with status success/revised/pending/action_required: Returns appropriate response object
     * - HTTP 400 with status rejected: Returns SEP08PostTransactionRejected
     * - Invalid response format: Throws SEP08InvalidPostTransactionResponse
     *
     * The method automatically parses the response and instantiates the appropriate response
     * class based on the status field. Use instanceof checks to handle different response types.
     *
     * @param string $tx Base64-encoded XDR transaction envelope signed by the user
     * @param string $approvalServer Full URL of the approval server endpoint (from RegulatedAsset::$approvalServer)
     *
     * @return SEP08PostTransactionResponse Concrete response object (Success/Revised/Pending/ActionRequired/Rejected)
     *
     * @throws SEP08InvalidPostTransactionResponse If the response format is invalid, missing required
     *                                              fields, or contains an unknown status value. The exception
     *                                              message contains details about what was invalid.
     * @throws GuzzleException If a network error occurs (connection timeout, DNS failure, etc.)
     *
     * @see https://github.com/stellar/stellar-protocol/blob/v1.7.4/ecosystem/sep-0008.md#post-endpoint SEP-0008 v1.7.4
     */
    public function postTransaction(string $tx, string $approvalServer) : SEP08PostTransactionResponse {

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
     * Submits action fields to the approval server when action_required response is received.
     *
     * This method is used after receiving a SEP08PostTransactionActionRequired response with
     * action_method set to "POST". It allows the wallet to programmatically provide the requested
     * SEP-9 KYC/AML fields without requiring the user to manually enter them in a browser.
     *
     * The approval server may respond with:
     * - no_further_action_required: The provided fields are sufficient; resubmit the transaction
     * - follow_next_url: Additional action needed; open next_url in browser for user completion
     *
     * Workflow:
     * 1. Receive SEP08PostTransactionActionRequired with action_method "POST"
     * 2. If wallet has the requested action_fields, call this method
     * 3. If response is SEP08PostActionDone, resubmit original transaction
     * 4. If response is SEP08PostActionNextUrl, open next_url in browser
     *
     * Request Details:
     * - HTTP Method: POST
     * - Content-Type: application/json
     * - Request Body: JSON object with SEP-9 field names as keys
     *
     * @param string $url Action URL from SEP08PostTransactionActionRequired::$actionUrl
     * @param array<array-key, mixed> $actionFields Associative array of SEP-9 field names to values
     *                                              (e.g., ['email_address' => 'user@example.com'])
     *
     * @return SEP08PostActionResponse Either SEP08PostActionDone or SEP08PostActionNextUrl
     *
     * @throws SEP08InvalidPostActionResponse If the response format is invalid, missing required
     *                                         fields, or contains an unknown result value
     * @throws GuzzleException If a network error occurs during the request
     *
     * @see https://github.com/stellar/stellar-protocol/blob/v1.7.4/ecosystem/sep-0008.md#following-the-action-url SEP-0008 v1.7.4
     * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0009.md SEP-0009 Standard KYC/AML Fields
    */
    public function postAction(string $url, array $actionFields) : SEP08PostActionResponse {
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