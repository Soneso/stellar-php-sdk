<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Recovery;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Soneso\StellarSDK\Requests\RequestBuilder;

/**
 * Implements SEP-0030 - Account Recovery: multi-party recovery of Stellar accounts.
 *
 * This service enables users to regain access to their Stellar accounts through alternative
 * authentication methods (phone, email, or other Stellar addresses) when they lose their private keys.
 * It supports multi-party recovery scenarios, account sharing, and multi-device access patterns.
 *
 * RECOVERY WORKFLOW:
 * 1. Registration: Register account with recovery server using registerAccount(), providing identities
 *    with authentication methods. Server returns one or more signer public keys.
 * 2. Add Signers: Add the server's signer keys to the Stellar account with appropriate weights.
 * 3. Recovery: When recovery is needed, authenticate as one of the registered identities.
 * 4. Sign Transaction: Use signTransaction() to get server signature on a transaction that
 *    adds a new signer or removes old signers from the account.
 * 5. Submit: Submit the signed transaction to the Stellar network to regain account control.
 *
 * SECURITY CONSIDERATIONS:
 * - JWT tokens must be kept secure and transmitted over HTTPS only
 * - Recovery operations grant control over accounts - implement proper authentication verification
 * - Multi-server recovery configurations should distribute trust to prevent single points of failure
 * - Configure account thresholds (e.g., high=2, med=2, low=2) and assign each server signer weight=1
 *   so no single server has full control
 * - Signer key rotation should be monitored and implemented regularly via accountDetails()
 * - Identity verification (phone, email) must meet security requirements for account value
 * - All endpoints require SEP-10 or external authentication provider JWT tokens
 * - Never expose JWT tokens in logs or error messages
 * - Use HTTPS for all recovery server communication in production environments
 * - Stellar address authentication (stellar_address) provides highest security via SEP-10
 * - Phone authentication is vulnerable to SIM swapping attacks - evaluate risk vs. account value
 * - Email authentication depends on email provider security
 *
 * MULTI-SERVER BEST PRACTICES:
 * - Register with 2 or more recovery servers for distributed trust
 * - Each server provides a signer with weight=1
 * - Configure account thresholds so multiple servers must cooperate to recover
 * - Periodically check for new signers and rotate keys across all servers
 *
 * @package Soneso\StellarSDK\SEP\Recovery
 * @see https://github.com/stellar/stellar-protocol/blob/v0.8.1/ecosystem/sep-0030.md SEP-30 Specification
 * @see https://github.com/stellar/stellar-protocol/blob/v3.4.1/ecosystem/sep-0010.md SEP-10 v3.4.1 Authentication
 */
class RecoveryService
{
    private string $serviceAddress;
    private Client $httpClient;

    /**
     * Constructs a new RecoveryService client.
     *
     * @param string $serviceAddress The base URL of the SEP-30 recovery server.
     *                               Format: "https://recovery.example.com" or "https://recovery.example.com/"
     *                               Both trailing slash formats are supported.
     *                               SECURITY: Must use HTTPS in production environments.
     * @param Client|null $httpClient Optional Guzzle HTTP client for custom configuration
     *                                (timeouts, proxies, etc.). If null, default client is used.
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
     * Registers an account with the recovery server.
     *
     * This endpoint creates a new recovery account registration, associating one or more
     * identities with authentication methods. The server responds with signer public keys
     * that should be added to the Stellar account with appropriate weights.
     *
     * After successful registration, add the returned signer keys to your Stellar account
     * using Set Options operations. The signing address should be added with appropriate
     * weight based on your security model (e.g., weight=1 with threshold=2 for multi-server).
     *
     * See: https://github.com/stellar/stellar-protocol/blob/v0.8.1/ecosystem/sep-0030.md#post-accountsaddress
     *
     * @param string $address The Stellar account address to register (G... format).
     * @param SEP30Request $request Registration request containing identity information with
     *                              authentication methods (phone, email, stellar_address, etc.).
     * @param string $jwt SEP-10 JWT token proving high threshold control of the account.
     *                    Format: "eyJ..." (do not include "Bearer " prefix).
     *                    SECURITY: Must be obtained via authenticated SEP-10 flow.
     *                    The JWT proves ownership and authorization to register this account.
     * @return SEP30AccountResponse Response containing account details and signer public keys
     *                              to be added to the Stellar account.
     * @throws GuzzleException If HTTP request fails.
     * @throws SEP30BadRequestResponseException If request data is invalid or malformed (HTTP 400).
     * @throws SEP30ConflictResponseException If account is already registered (HTTP 409).
     * @throws SEP30NotFoundResponseException If account not found (HTTP 404).
     * @throws SEP30UnauthorizedResponseException If JWT is invalid or insufficient (HTTP 401).
     * @throws SEP30UnknownResponseException For other unexpected errors.
     */
    public function registerAccount(string $address, SEP30Request $request, string $jwt) : SEP30AccountResponse {

        $url = $this->buildServiceUrl("accounts/" . $address);

        $response = $this->httpClient->post($url,
            [RequestOptions::JSON => $request->toJson(),
            RequestOptions::HEADERS => $this->buildHeaders($jwt), 'http_errors' => false]);

        $statusCode = $response->getStatusCode();
        $content = $response->getBody()->__toString();
        $jsonData = @json_decode($content, true);
        if (200 === $statusCode) {
            return SEP30AccountResponse::fromJson($jsonData);
        } else {
            $errorMsg = $content;
            if (null !== $jsonData && isset($jsonData['error'])) {
                $errorMsg = $jsonData['error'];
            }
            if (400 === $statusCode) {
                throw new SEP30BadRequestResponseException($errorMsg, $statusCode);
            } else if (401 === $statusCode) {
                throw new SEP30UnauthorizedResponseException($errorMsg, $statusCode);
            } else if (404 === $statusCode) {
                throw new SEP30NotFoundResponseException($errorMsg, $statusCode);
            } else if (409 === $statusCode) {
                throw new SEP30ConflictResponseException($errorMsg, $statusCode);
            } else {
                throw new SEP30UnknownResponseException($errorMsg, $statusCode);
            }
        }
    }

    /**
     * Updates the identities for a registered account.
     *
     * This endpoint replaces all existing identities with the identities provided in the request.
     * Identities are not merged - any identity not included in the request will be removed.
     * This allows adding new authentication methods, removing old ones, or changing identity roles.
     *
     * Common use cases:
     * - Add new authentication methods (e.g., add email when only phone was registered)
     * - Remove compromised authentication methods
     * - Update identity roles (e.g., change from "owner" to "sender")
     * - Add additional parties for account sharing scenarios
     *
     * See: https://github.com/stellar/stellar-protocol/blob/v0.8.1/ecosystem/sep-0030.md#put-accountsaddress
     *
     * @param string $address The Stellar account address to update (G... format).
     * @param SEP30Request $request Updated identity information. All identities in this request
     *                              will replace existing identities (not merged).
     * @param string $jwt SEP-10 JWT token proving high threshold control of the account.
     *                    Format: "eyJ..." (do not include "Bearer " prefix).
     *                    SECURITY: Must be obtained via authenticated SEP-10 flow.
     * @return SEP30AccountResponse Response containing updated account details and current signers.
     * @throws GuzzleException If HTTP request fails.
     * @throws SEP30BadRequestResponseException If request data is invalid or malformed (HTTP 400).
     * @throws SEP30ConflictResponseException If account update conflicts with server state (HTTP 409).
     * @throws SEP30NotFoundResponseException If account not registered (HTTP 404).
     * @throws SEP30UnauthorizedResponseException If JWT is invalid or insufficient (HTTP 401).
     * @throws SEP30UnknownResponseException For other unexpected errors.
     */
    public function updateIdentitiesForAccount(string $address, SEP30Request $request, string $jwt) : SEP30AccountResponse {

        $url = $this->buildServiceUrl("accounts/" . $address);

        $response = $this->httpClient->put($url,
            [RequestOptions::JSON => $request->toJson(),
                RequestOptions::HEADERS => $this->buildHeaders($jwt), 'http_errors' => false]);

        $statusCode = $response->getStatusCode();
        $content = $response->getBody()->__toString();
        $jsonData = @json_decode($content, true);
        if (200 === $statusCode) {
            return SEP30AccountResponse::fromJson($jsonData);
        } else {
            $errorMsg = $content;
            if (null !== $jsonData && isset($jsonData['error'])) {
                $errorMsg = $jsonData['error'];
            }
            if (400 === $statusCode) {
                throw new SEP30BadRequestResponseException($errorMsg, $statusCode);
            } else if (401 === $statusCode) {
                throw new SEP30UnauthorizedResponseException($errorMsg, $statusCode);
            } else if (404 === $statusCode) {
                throw new SEP30NotFoundResponseException($errorMsg, $statusCode);
            } else if (409 === $statusCode) {
                throw new SEP30ConflictResponseException($errorMsg, $statusCode);
            } else {
                throw new SEP30UnknownResponseException($errorMsg, $statusCode);
            }
        }
    }

    /**
     * Signs a transaction using the recovery server's signer key.
     *
     * This is the core recovery operation. The server signs a transaction that typically adds
     * a new signer to the account or removes old signers, enabling account recovery. The client
     * authenticates as one of the registered identities to prove their right to recover the account.
     *
     * TRANSACTION REQUIREMENTS:
     * - Transaction source account must be the authenticated account or an allowed sponsoring account
     * - All operation source accounts must be the authenticated account or not set
     * - Transaction must be base64-encoded XDR format
     * - Server validates transaction only contains authorized operations
     * - Typical use: Transaction with Set Options operation to add new signer for recovery
     *
     * SIGNING ADDRESS:
     * - Must be one of the server-provided signer keys from registration or account details
     * - Use the most recent signer key for key rotation best practices
     * - Periodically check accountDetails() for new signers and update account signers
     *
     * See: https://github.com/stellar/stellar-protocol/blob/v0.8.1/ecosystem/sep-0030.md#post-accountsaddresssignsigning-address
     *
     * @param string $address The Stellar account address being recovered (G... format).
     * @param string $signingAddress The server signer public key to use for signing (G... format).
     *                               Must be one of the keys provided by the server during registration
     *                               or from accountDetails() query.
     * @param string $transaction Base64-encoded XDR transaction envelope to sign.
     *                            Transaction must meet security requirements (authorized operations only).
     * @param string $jwt SEP-10 or external authentication JWT token proving identity authorization.
     *                    SECURITY: JWT must authenticate as one of the account's registered identities.
     *                    For recovery scenarios, this proves the user can authenticate via alternate methods.
     * @return SEP30SignatureResponse Response containing the signature and network passphrase.
     *                                Add this signature to the transaction envelope before submitting.
     * @throws GuzzleException If HTTP request fails.
     * @throws SEP30BadRequestResponseException If transaction format invalid or contains unauthorized operations (HTTP 400).
     * @throws SEP30ConflictResponseException If signing conflicts with server state (HTTP 409).
     * @throws SEP30NotFoundResponseException If account not registered or signing address not recognized (HTTP 404).
     * @throws SEP30UnauthorizedResponseException If JWT is invalid or doesn't authenticate an authorized identity (HTTP 401).
     * @throws SEP30UnknownResponseException For other unexpected errors.
     */
    public function signTransaction(string $address, string $signingAddress, string $transaction, string $jwt) : SEP30SignatureResponse {

        $url = $this->buildServiceUrl("accounts/" . $address . "/sign/" . $signingAddress);

        $response = $this->httpClient->post($url,
            [RequestOptions::JSON => ['transaction' => $transaction],
                RequestOptions::HEADERS => $this->buildHeaders($jwt), 'http_errors' => false]);

        $statusCode = $response->getStatusCode();
        $content = $response->getBody()->__toString();
        $jsonData = @json_decode($content, true);
        if (200 === $statusCode) {
            return SEP30SignatureResponse::fromJson($jsonData);
        } else {
            $errorMsg = $content;
            if (null !== $jsonData && isset($jsonData['error'])) {
                $errorMsg = $jsonData['error'];
            }
            if (400 === $statusCode) {
                throw new SEP30BadRequestResponseException($errorMsg, $statusCode);
            } else if (401 === $statusCode) {
                throw new SEP30UnauthorizedResponseException($errorMsg, $statusCode);
            } else if (404 === $statusCode) {
                throw new SEP30NotFoundResponseException($errorMsg, $statusCode);
            } else if (409 === $statusCode) {
                throw new SEP30ConflictResponseException($errorMsg, $statusCode);
            } else {
                throw new SEP30UnknownResponseException($errorMsg, $statusCode);
            }
        }
    }

    /**
     * Retrieves the registered account's details and current signer keys.
     *
     * Use this endpoint to:
     * - Check if an account is registered before attempting registration
     * - Retrieve current signer keys for transaction signing operations
     * - Monitor for new signer keys as part of key rotation process
     * - Verify current identity configurations
     *
     * The response includes all registered identities and their authentication methods,
     * plus the current signer keys that can be used for signTransaction().
     *
     * See: https://github.com/stellar/stellar-protocol/blob/v0.8.1/ecosystem/sep-0030.md#get-accountsaddress
     *
     * @param string $address The Stellar account address to query (G... format).
     * @param string $jwt SEP-10 JWT token or identity authentication token.
     *                    Must authenticate as the account owner or a registered identity
     *                    with access to this account.
     * @return SEP30AccountResponse Response containing account details, identities, and signer keys.
     * @throws GuzzleException If HTTP request fails.
     * @throws SEP30BadRequestResponseException If request is invalid (HTTP 400).
     * @throws SEP30ConflictResponseException If query conflicts with server state (HTTP 409).
     * @throws SEP30NotFoundResponseException If account not registered or JWT lacks access (HTTP 404).
     * @throws SEP30UnauthorizedResponseException If JWT is invalid (HTTP 401).
     * @throws SEP30UnknownResponseException For other unexpected errors.
     */
    public function accountDetails(string $address, string $jwt) : SEP30AccountResponse {

        $url = $this->buildServiceUrl("accounts/" . $address);

        $response = $this->httpClient->get($url,
            [RequestOptions::HEADERS => $this->buildHeaders($jwt), 'http_errors' => false]);

        $statusCode = $response->getStatusCode();
        $content = $response->getBody()->__toString();
        $jsonData = @json_decode($content, true);
        if (200 === $statusCode) {
            return SEP30AccountResponse::fromJson($jsonData);
        } else {
            $errorMsg = $content;
            if (null !== $jsonData && isset($jsonData['error'])) {
                $errorMsg = $jsonData['error'];
            }
            if (400 === $statusCode) {
                throw new SEP30BadRequestResponseException($errorMsg, $statusCode);
            } else if (401 === $statusCode) {
                throw new SEP30UnauthorizedResponseException($errorMsg, $statusCode);
            } else if (404 === $statusCode) {
                throw new SEP30NotFoundResponseException($errorMsg, $statusCode);
            } else if (409 === $statusCode) {
                throw new SEP30ConflictResponseException($errorMsg, $statusCode);
            } else {
                throw new SEP30UnknownResponseException($errorMsg, $statusCode);
            }
        }
    }

    /**
     * Deletes the account registration from the recovery server.
     *
     * This operation is IRRECOVERABLE. Once deleted, the account cannot be recovered through
     * this server. The server's signer keys should be removed from the Stellar account after deletion.
     *
     * Use this when:
     * - Discontinuing recovery services for an account
     * - Switching to a different recovery server configuration
     * - Closing or abandoning an account
     *
     * SECURITY: After deletion, remove the server's signer keys from the Stellar account
     * to prevent any residual access risk.
     *
     * See: https://github.com/stellar/stellar-protocol/blob/v0.8.1/ecosystem/sep-0030.md#delete-accountsaddress
     *
     * @param string $address The Stellar account address to delete (G... format).
     * @param string $jwt SEP-10 JWT token proving high threshold control of the account.
     *                    SECURITY: Requires account owner authentication.
     * @return SEP30AccountResponse Response containing final account state before deletion.
     * @throws GuzzleException If HTTP request fails.
     * @throws SEP30BadRequestResponseException If request is invalid (HTTP 400).
     * @throws SEP30ConflictResponseException If deletion conflicts with server state (HTTP 409).
     * @throws SEP30NotFoundResponseException If account not registered (HTTP 404).
     * @throws SEP30UnauthorizedResponseException If JWT is invalid or insufficient (HTTP 401).
     * @throws SEP30UnknownResponseException For other unexpected errors.
     */
    public function deleteAccount(string $address, string $jwt) : SEP30AccountResponse {

        $url = $this->buildServiceUrl("accounts/" . $address);

        $response = $this->httpClient->delete($url,
            [RequestOptions::HEADERS => $this->buildHeaders($jwt), 'http_errors' => false]);

        $statusCode = $response->getStatusCode();
        $content = $response->getBody()->__toString();
        $jsonData = @json_decode($content, true);
        if (200 === $statusCode) {
            return SEP30AccountResponse::fromJson($jsonData);
        } else {
            $errorMsg = $content;
            if (null !== $jsonData && isset($jsonData['error'])) {
                $errorMsg = $jsonData['error'];
            }
            if (400 === $statusCode) {
                throw new SEP30BadRequestResponseException($errorMsg, $statusCode);
            } else if (401 === $statusCode) {
                throw new SEP30UnauthorizedResponseException($errorMsg, $statusCode);
            } else if (404 === $statusCode) {
                throw new SEP30NotFoundResponseException($errorMsg, $statusCode);
            } else if (409 === $statusCode) {
                throw new SEP30ConflictResponseException($errorMsg, $statusCode);
            } else {
                throw new SEP30UnknownResponseException($errorMsg, $statusCode);
            }
        }
    }

    /**
     * Returns a paginated list of accounts that the authenticated identity has access to.
     *
     * This endpoint lists all accounts where the authenticated identity is registered,
     * useful for identity providers or users managing multiple accounts.
     *
     * Pagination is cursor-based: use the last account address from the current page
     * as the $after parameter to retrieve the next page.
     *
     * See: https://github.com/stellar/stellar-protocol/blob/v0.8.1/ecosystem/sep-0030.md#get-accounts
     *
     * @param string $jwt SEP-10 JWT token or identity authentication token.
     *                    The response includes only accounts where this JWT's identity is registered.
     * @param string|null $after Cursor for pagination. Use the address value of the last account
     *                           in the current page to fetch the next page.
     *                           Example: If last account address is "GXYZ...", set $after = "GXYZ..."
     *                           Omit or pass null for the first page.
     * @return SEP30AccountsResponse Response containing array of account details with pagination support.
     * @throws GuzzleException If HTTP request fails.
     * @throws SEP30BadRequestResponseException If request parameters are invalid (HTTP 400).
     * @throws SEP30ConflictResponseException If query conflicts with server state (HTTP 409).
     * @throws SEP30NotFoundResponseException If no accounts found for this identity (HTTP 404).
     * @throws SEP30UnauthorizedResponseException If JWT is invalid (HTTP 401).
     * @throws SEP30UnknownResponseException For other unexpected errors.
     */
    public function accounts(string $jwt, ?string $after = null) : SEP30AccountsResponse {

        $url = $this->buildServiceUrl("accounts");
        if ($after != null) {
            $url .= "?after=" . $after;
        }

        $response = $this->httpClient->get($url,
            [RequestOptions::HEADERS => $this->buildHeaders($jwt), 'http_errors' => false]);

        $statusCode = $response->getStatusCode();
        $content = $response->getBody()->__toString();
        $jsonData = @json_decode($content, true);
        if (200 === $statusCode) {
            return SEP30AccountsResponse::fromJson($jsonData);
        } else {
            $errorMsg = $content;
            if (null !== $jsonData && isset($jsonData['error'])) {
                $errorMsg = $jsonData['error'];
            }
            if (400 === $statusCode) {
                throw new SEP30BadRequestResponseException($errorMsg, $statusCode);
            } else if (401 === $statusCode) {
                throw new SEP30UnauthorizedResponseException($errorMsg, $statusCode);
            } else if (404 === $statusCode) {
                throw new SEP30NotFoundResponseException($errorMsg, $statusCode);
            } else if (409 === $statusCode) {
                throw new SEP30ConflictResponseException($errorMsg, $statusCode);
            } else {
                throw new SEP30UnknownResponseException($errorMsg, $statusCode);
            }
        }
    }

    private function buildHeaders(string $jwt) : array {
        $headers = array();
        $headers = array_merge($headers, RequestBuilder::HEADERS);
        return array_merge($headers, ['Authorization' => "Bearer ". $jwt]);
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