<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuthForContracts;

/**
 * Response from the SEP-45 token endpoint after submitting signed authorization entries.
 *
 * This response is returned by the authentication server when a client submits signed
 * authorization entries (POST to the WEB_AUTH_FOR_CONTRACTS_ENDPOINT). The response
 * either contains a JWT token for successful authentication or an error message.
 *
 * Response Fields:
 * - token: JWT token for authenticated session (present on success)
 * - error: Error message describing why authentication failed (present on failure)
 *
 * JWT Token Structure:
 * The JWT token contains the following claims:
 * - iss: Issuer (authentication server URI)
 * - sub: Subject (client contract account C... address)
 * - iat: Issued at timestamp
 * - exp: Expiration timestamp
 * - client_domain: Optional client domain if verification was performed
 *
 * Usage:
 * After submitting signed authorization entries, check if the response contains a token
 * or an error. If successful, the token can be used to authenticate subsequent requests
 * to protected services (SEP-12, SEP-24, SEP-31, etc.).
 *
 * Error Handling:
 * If an error is present, it indicates the server rejected the signed authorization entries.
 * Common reasons include invalid signatures, expired signatures, nonce reuse, or unauthorized
 * contract accounts.
 *
 * Security:
 * Store JWT tokens securely and never expose them in logs or URLs. Tokens grant access to
 * authenticated services and should be treated as credentials. Respect token expiration times.
 *
 * @package Soneso\StellarSDK\SEP\WebAuthForContracts
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0045.md SEP-45 Token Response
 * @see WebAuthForContracts::sendSignedChallenge() For submitting signed entries
 */
class SubmitContractChallengeResponse
{
    private ?string $jwtToken = null;
    private ?string $error = null;

    /**
     * Returns the JWT token if authentication was successful.
     *
     * @return string|null the JWT token, or null if authentication failed
     */
    public function getJwtToken(): ?string
    {
        return $this->jwtToken;
    }

    /**
     * Sets the JWT token.
     *
     * @param string|null $jwtToken the JWT token
     */
    public function setJwtToken(?string $jwtToken): void
    {
        $this->jwtToken = $jwtToken;
    }

    /**
     * Returns the error message if authentication failed.
     *
     * @return string|null the error message, or null if authentication was successful
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Sets the error message.
     *
     * @param string|null $error the error message
     */
    public function setError(?string $error): void
    {
        $this->error = $error;
    }

    /**
     * Creates a SubmitContractChallengeResponse from JSON data.
     *
     * @param array $json JSON data array from the server response
     * @return SubmitContractChallengeResponse the created response object
     */
    public static function fromJson(array $json): SubmitContractChallengeResponse
    {
        $result = new SubmitContractChallengeResponse();
        if (isset($json['token'])) {
            $result->jwtToken = $json['token'];
        }
        if (isset($json['error'])) {
            $result->error = $json['error'];
        }
        return $result;
    }
}
