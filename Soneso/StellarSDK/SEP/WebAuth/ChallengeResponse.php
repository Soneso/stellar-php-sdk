<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuth;

use Soneso\StellarSDK\Responses\Response;

/**
 * Response from the SEP-10 challenge endpoint containing the authentication challenge transaction.
 *
 * This response is returned by the authentication server when a client requests a challenge
 * transaction (GET to the auth endpoint). The response contains a base64-encoded XDR transaction
 * envelope that the client must sign to prove control of their account.
 *
 * Structure:
 * The response contains 'transaction' which is the challenge transaction as a
 * base64-encoded XDR TransactionEnvelope. This transaction has sequence number 0, time bounds
 * set to expire in approximately 15 minutes, and ManageData operations for authentication.
 *
 * Usage:
 * After receiving this response, clients should:
 * 1. Decode and validate the challenge transaction
 * 2. Verify the transaction has sequence number 0 (cannot be executed)
 * 3. Verify the server's signature on the transaction
 * 4. Verify time bounds, home domain, and other security requirements
 * 5. Sign the transaction with the client's private key(s)
 * 6. Submit the signed transaction back to the token endpoint
 *
 * The optional 'network_passphrase' field may be included to help clients verify they're using
 * the correct network passphrase when signing.
 *
 * @package Soneso\StellarSDK\SEP\WebAuth
 * @see https://github.com/stellar/stellar-protocol/blob/v3.4.1/ecosystem/sep-0010.md#challenge SEP-10 Challenge Response
 * @see WebAuth::jwtToken() For the complete authentication flow
 */
class ChallengeResponse extends Response
{
    private ?string $transaction = null;
    private ?string $networkPassphrase = null;

    /**
     * @param string $transaction
     */
    public function setTransaction(string $transaction): void
    {
        $this->transaction = $transaction;
    }

    /**
     * @return string|null The challenge transaction as base64-encoded XDR, or null when the response holds no transaction string.
     */
    public function getTransaction(): ?string
    {
        return $this->transaction;
    }

    /**
     * Returns the optional network passphrase advertised by the server.
     *
     * @return string|null The advertised passphrase, or null when no string is supplied.
     */
    public function getNetworkPassphrase(): ?string
    {
        return $this->networkPassphrase;
    }

    protected function loadFromJson(array $json) : void {
        // Treat non-string fields as absent to keep the typed properties valid: WebAuth rejects a missing
        // transaction and skips the network check without a passphrase.
        if (is_string($json['transaction'] ?? null)) $this->transaction = $json['transaction'];
        if (is_string($json['network_passphrase'] ?? null)) $this->networkPassphrase = $json['network_passphrase'];
    }

    public static function fromJson(array $json) : ChallengeResponse
    {
        $result = new ChallengeResponse();
        $result->loadFromJson($json);
        return $result;
    }
}
