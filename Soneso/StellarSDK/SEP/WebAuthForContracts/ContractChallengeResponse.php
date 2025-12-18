<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuthForContracts;

use Exception;
use Soneso\StellarSDK\Responses\Response;

/**
 * Response from the SEP-45 challenge endpoint containing contract authentication authorization entries.
 *
 * This response is returned by the authentication server when a client requests a challenge
 * for contract account authentication (GET to the WEB_AUTH_FOR_CONTRACTS_ENDPOINT). The response
 * contains base64-encoded XDR authorization entries that the client must sign to prove control
 * of their contract account.
 *
 * Structure:
 * The response contains an 'authorization_entries' field which is a base64-encoded XDR array
 * of SorobanAuthorizationEntry objects. Each entry contains:
 * - credentials: Address-based credentials with signature placeholder
 * - rootInvocation: The web_auth_verify function call with no sub-invocations
 *
 * The entries include:
 * 1. A server entry (already signed by the server's signing key)
 * 2. A client entry (to be signed by the client)
 * 3. Optionally, a client domain entry (to be signed by the client domain key)
 *
 * Usage:
 * After receiving this response, clients should:
 * 1. Decode and validate the authorization entries
 * 2. Verify no sub-invocations exist in any entry
 * 3. Verify the contract address matches WEB_AUTH_CONTRACT_ID
 * 4. Verify the function name is "web_auth_verify"
 * 5. Verify all function arguments (account, home_domain, web_auth_domain, etc.)
 * 6. Verify the server entry has a valid signature
 * 7. Verify nonce consistency across all entries
 * 8. Sign the client entry with the client's key(s)
 * 9. Submit the signed entries back to the token endpoint
 *
 * The optional 'network_passphrase' field may be included to help clients verify they're using
 * the correct network passphrase when signing.
 *
 * @package Soneso\StellarSDK\SEP\WebAuthForContracts
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0045.md SEP-45 Challenge Response
 * @see WebAuthForContracts::jwtToken() For the complete authentication flow
 */
class ContractChallengeResponse extends Response
{
    private string $authorizationEntries;
    private ?string $networkPassphrase = null;

    /**
     * Returns the base64-encoded XDR array of SorobanAuthorizationEntry objects.
     *
     * @return string base64-encoded XDR authorization entries
     */
    public function getAuthorizationEntries(): string
    {
        return $this->authorizationEntries;
    }

    /**
     * Sets the authorization entries.
     *
     * @param string $authorizationEntries base64-encoded XDR authorization entries
     */
    public function setAuthorizationEntries(string $authorizationEntries): void
    {
        $this->authorizationEntries = $authorizationEntries;
    }

    /**
     * Returns the network passphrase if provided by the server.
     *
     * @return string|null the network passphrase, or null if not provided
     */
    public function getNetworkPassphrase(): ?string
    {
        return $this->networkPassphrase;
    }

    /**
     * Sets the network passphrase.
     *
     * @param string|null $networkPassphrase the network passphrase
     */
    public function setNetworkPassphrase(?string $networkPassphrase): void
    {
        $this->networkPassphrase = $networkPassphrase;
    }

    /**
     * Loads this response from JSON data.
     *
     * @param array $json JSON data array from the server response
     * @throws Exception if required field authorization_entries is missing
     */
    protected function loadFromJson(array $json): void
    {
        if (isset($json['authorization_entries'])) {
            $this->authorizationEntries = $json['authorization_entries'];
        } else if (isset($json['authorizationEntries'])) {
            $this->authorizationEntries = $json['authorizationEntries'];
        } else {
            throw new Exception("Missing required field: authorization_entries");
        }

        if (isset($json['network_passphrase'])) {
            $this->networkPassphrase = $json['network_passphrase'];
        } else if (isset($json['networkPassphrase'])) {
            $this->networkPassphrase = $json['networkPassphrase'];
        }
    }

    /**
     * Creates a ContractChallengeResponse from JSON data.
     *
     * @param array $json JSON data array from the server response
     * @return ContractChallengeResponse the created response object
     */
    public static function fromJson(array $json): ContractChallengeResponse
    {
        $result = new ContractChallengeResponse();
        $result->loadFromJson($json);
        return $result;
    }
}
