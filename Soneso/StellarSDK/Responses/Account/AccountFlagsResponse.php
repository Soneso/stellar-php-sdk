<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Account;

/**
 * Represents authorization flags set on an issuing account
 *
 * Authorization flags control how an asset issuer manages trustlines to their issued assets.
 * These flags determine whether holders need authorization and whether authorization can be revoked.
 *
 * Flag meanings:
 * - AUTH_REQUIRED: Trustlines to this issuer's assets must be authorized
 * - AUTH_REVOCABLE: The issuer can revoke authorization of existing trustlines
 * - AUTH_IMMUTABLE: The issuer cannot change authorization flags in the future
 * - AUTH_CLAWBACK_ENABLED: The issuer can clawback assets from holders
 *
 * This response is included in AccountResponse for asset issuing accounts.
 *
 * @package Soneso\StellarSDK\Responses\Account
 * @see AccountResponse For the parent account details
 * @see https://developers.stellar.org/docs/encyclopedia/authorization-flags Authorization Flags Documentation
 * @since 1.0.0
 */
class AccountFlagsResponse
{

    private bool $authRequired;
    private bool $authRevocable;
    private bool $authImmutable;
    private bool $authClawbackEnabled;

    /**
     * Checks if the account requires authorization for trustlines
     *
     * When true, trustlines to this issuer's assets require explicit authorization.
     *
     * @return bool True if authorization is required
     */
    public function isAuthRequired() : bool {
        return $this->authRequired;
    }

    /**
     * Checks if the account can revoke authorization
     *
     * When true, the issuer can revoke authorization from existing trustlines.
     *
     * @return bool True if authorization is revocable
     */
    public function isAuthRevocable() : bool {
        return $this->authRevocable;
    }

    /**
     * Checks if authorization flags are immutable
     *
     * When true, the issuer cannot change authorization flags in the future.
     *
     * @return bool True if flags are immutable
     */
    public function isAuthImmutable() : bool {
        return $this->authImmutable;
    }

    /**
     * Checks if clawback is enabled for this account's issued assets
     *
     * When true, the issuer can clawback assets from holder accounts.
     *
     * @return bool True if clawback is enabled
     */
    public function isAuthClawbackEnabled() : bool {
        return $this->authClawbackEnabled;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['auth_required'])) $this->authRequired = $json['auth_required'];
        if (isset($json['auth_revocable'])) $this->authRevocable = $json['auth_revocable'];
        if (isset($json['auth_immutable'])) $this->authImmutable = $json['auth_immutable'];
        if (isset($json['auth_clawback_enabled'])) $this->authClawbackEnabled = $json['auth_clawback_enabled'];
    }

    /**
     * Creates an AccountFlagsResponse instance from JSON data
     *
     * @param array $json The JSON array containing flag data from Horizon
     * @return AccountFlagsResponse The parsed flags response
     */
    public static function fromJson(array $json) : AccountFlagsResponse {
        $result = new AccountFlagsResponse();
        $result->loadFromJson($json);
        return $result;
    }
}

