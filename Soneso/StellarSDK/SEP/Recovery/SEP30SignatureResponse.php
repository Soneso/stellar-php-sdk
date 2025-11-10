<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Recovery;

/**
 * Response containing transaction signature from SEP-0030 recovery server.
 *
 * This class represents the response from POST /accounts/{address}/sign/{signing_address}
 * endpoint, containing the signature and network passphrase for the signed transaction.
 *
 * @package Soneso\StellarSDK\SEP\Recovery
 * @see https://github.com/stellar/stellar-protocol/blob/v0.8.1/ecosystem/sep-0030.md#post-accountsaddresssignsigning-address
 * @see RecoveryService::signTransaction()
 */
class SEP30SignatureResponse
{
    public string $signature;
    public string $networkPassphrase;

    /**
     * Constructor.
     *
     * @param string $signature The transaction signature (base64).
     * @param string $networkPassphrase The Stellar network passphrase.
     */
    public function __construct(string $signature, string $networkPassphrase)
    {
        $this->signature = $signature;
        $this->networkPassphrase = $networkPassphrase;
    }

    /**
     * Constructs a SEP30SignatureResponse from JSON data.
     *
     * @param array<array-key, mixed> $json The JSON data to parse.
     * @return SEP30SignatureResponse The constructed response.
     */
    public static function fromJson(array $json) : SEP30SignatureResponse
    {
        return new SEP30SignatureResponse($json['signature'], $json['network_passphrase']);
    }

    /**
     * Gets the transaction signature.
     *
     * @return string The signature in base64 format.
     */
    public function getSignature(): string
    {
        return $this->signature;
    }

    /**
     * Sets the transaction signature.
     *
     * @param string $signature The signature in base64 format.
     */
    public function setSignature(string $signature): void
    {
        $this->signature = $signature;
    }

    /**
     * Gets the network passphrase.
     *
     * @return string The network passphrase.
     */
    public function getNetworkPassphrase(): string
    {
        return $this->networkPassphrase;
    }

    /**
     * Sets the network passphrase.
     *
     * @param string $networkPassphrase The network passphrase.
     */
    public function setNetworkPassphrase(string $networkPassphrase): void
    {
        $this->networkPassphrase = $networkPassphrase;
    }

}