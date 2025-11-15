<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Recovery;

/**
 * Request for registering or updating account identities via SEP-0030.
 *
 * This class represents the request payload for POST /accounts/{address}
 * and PUT /accounts/{address} operations, containing identity information
 * for multi-party account recovery.
 *
 * @package Soneso\StellarSDK\SEP\Recovery
 * @see https://github.com/stellar/stellar-protocol/blob/v0.8.1/ecosystem/sep-0030.md#post-accountsaddress
 * @see RecoveryService::registerAccount()
 * @see RecoveryService::updateIdentitiesForAccount()
 * @see SEP30RequestIdentity
 */
class SEP30Request
{
    /**
     * @param array<SEP30RequestIdentity> $identities Array of identity information for account recovery.
     */
    public function __construct(
        public array $identities,
    ) {
    }

    /**
     * Converts the request to JSON format.
     *
     * @return array<array-key, mixed> The JSON representation.
     */
    public function toJson() : array {

        $identitiesJson = array();

        foreach ($this->identities as $identity) {
            if ($identity instanceof SEP30RequestIdentity) {
                array_push($identitiesJson, $identity->toJson());
            }
        }
        return array(
            'identities' => $identitiesJson
        );
    }

    /**
     * Gets the identities array.
     *
     * @return array<SEP30RequestIdentity> Array of identity information.
     */
    public function getIdentities(): array
    {
        return $this->identities;
    }

    /**
     * Sets the identities array.
     *
     * @param array<SEP30RequestIdentity> $identities Array of identity information.
     */
    public function setIdentities(array $identities): void
    {
        $this->identities = $identities;
    }

}