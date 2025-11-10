<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuth;

use Soneso\StellarSDK\Responses\Response;

/**
 * Response from the SEP-10 token endpoint after submitting a signed challenge transaction.
 *
 * This response is returned by the authentication server when a client submits the signed
 * challenge transaction (POST to the auth endpoint). A successful response contains a JWT
 * token that can be used to authenticate subsequent requests to protected services.
 *
 * Structure:
 * The response can contain either:
 * - 'token': A JWT token string on successful authentication (HTTP 200)
 * - 'error': An error message string on authentication failure (HTTP 400)
 *
 * JWT Token Format:
 * The token contains standard JWT claims including:
 * - 'sub': The authenticated account (G... or M... address, optionally with :memo suffix)
 * - 'iss': The token issuer (authentication server URL)
 * - 'iat': Token issued at timestamp
 * - 'exp': Token expiration timestamp (typically 15 minutes to 24 hours)
 * - 'client_domain': Optional, present if client domain verification was performed
 *
 * Usage:
 * On success, extract the JWT token and use it as a Bearer token in the Authorization header
 * for subsequent requests to SEP-24, SEP-31, SEP-12, or other authenticated endpoints. The
 * token should be stored securely and refreshed when it expires.
 *
 * On error, the error field contains a human-readable description of why authentication failed,
 * such as insufficient signatures, invalid signatures, or server policy violations.
 *
 * @package Soneso\StellarSDK\SEP\WebAuth
 * @see https://github.com/stellar/stellar-protocol/blob/v3.4.1/ecosystem/sep-0010.md#token SEP-10 Token Response
 * @see WebAuth::jwtToken() For the complete authentication flow
 */
class SubmitCompletedChallengeResponse extends Response {

    private ?string $jwtToken = null;
    private ?string $error = null;

    /**
     * @return string|null
     */
    public function getJwtToken(): ?string
    {
        return $this->jwtToken;
    }

    /**
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * @param string|null $jwtToken
     */
    public function setJwtToken(?string $jwtToken): void
    {
        $this->jwtToken = $jwtToken;
    }

    /**
     * @param string|null $error
     */
    public function setError(?string $error): void
    {
        $this->error = $error;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['token'])) $this->jwtToken = $json['token'];
        if (isset($json['error'])) $this->error = $json['error'];
    }

    public static function fromJson(array $json) : SubmitCompletedChallengeResponse
    {
        $result = new SubmitCompletedChallengeResponse();
        $result->loadFromJson($json);
        return $result;
    }

}