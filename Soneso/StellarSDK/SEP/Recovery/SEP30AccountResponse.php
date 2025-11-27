<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Recovery;

/**
 * Response containing registered account information from SEP-0030 recovery server.
 *
 * This class represents account data including the address, registered identities,
 * and signers that can be used for multi-party account recovery operations.
 *
 * @package Soneso\StellarSDK\SEP\Recovery
 * @see https://github.com/stellar/stellar-protocol/blob/v0.8.1/ecosystem/sep-0030.md
 * @see RecoveryService
 * @see SEP30ResponseIdentity
 * @see SEP30ResponseSigner
 */
class SEP30AccountResponse
{
    /**
     * @param string $address The Stellar account address.
     * @param array<SEP30ResponseIdentity> $identities Registered identity owners.
     * @param array<SEP30ResponseSigner> $signers Account signers for recovery.
     */
    public function __construct(
        public string $address,
        public array $identities,
        public array $signers,
    ) {
    }

    /**
     * Constructs a SEP30AccountResponse from JSON data.
     *
     * @param array<array-key, mixed> $json The JSON data to parse.
     * @return SEP30AccountResponse The constructed response.
     */
    public static function fromJson(array $json) : SEP30AccountResponse
    {
        $address = $json['address'];
        $identities = array();
        foreach ($json['identities'] as $identity) {
            $identities[] = SEP30ResponseIdentity::fromJson($identity);
        }

        $signers = array();
        foreach ($json['signers'] as $signer) {
            $signers[] = SEP30ResponseSigner::fromJson($signer);
        }

        return new SEP30AccountResponse($address, $identities, $signers);
    }

    /**
     * Gets the Stellar account address.
     *
     * @return string The account address.
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * Sets the Stellar account address.
     *
     * @param string $address The account address.
     */
    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    /**
     * Gets the registered identities.
     *
     * @return array<SEP30ResponseIdentity> Array of identity information.
     */
    public function getIdentities(): array
    {
        return $this->identities;
    }

    /**
     * Sets the registered identities.
     *
     * @param array<SEP30ResponseIdentity> $identities Array of identity information.
     */
    public function setIdentities(array $identities): void
    {
        $this->identities = $identities;
    }

    /**
     * Gets the account signers.
     *
     * @return array<SEP30ResponseSigner> Array of signer information.
     */
    public function getSigners(): array
    {
        return $this->signers;
    }

    /**
     * Sets the account signers.
     *
     * @param array<SEP30ResponseSigner> $signers Array of signer information.
     */
    public function setSigners(array $signers): void
    {
        $this->signers = $signers;
    }

}