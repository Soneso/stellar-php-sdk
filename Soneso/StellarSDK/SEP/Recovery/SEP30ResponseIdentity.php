<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Recovery;

/**
 * Identity information in SEP-0030 account responses.
 *
 * This class represents identity owner information including role and
 * authentication status for account recovery operations.
 *
 * @package Soneso\StellarSDK\SEP\Recovery
 * @see https://github.com/stellar/stellar-protocol/blob/v0.8.1/ecosystem/sep-0030.md
 * @see SEP30AccountResponse
 */
class SEP30ResponseIdentity
{
    /**
     * @param string|null $role The identity role (e.g., "owner", "sender", "receiver"). Client-defined value stored by server and returned in responses.
     * @param bool|null $authenticated Whether the identity is authenticated.
     */
    public function __construct(
        public ?string $role,
        public ?bool $authenticated = null,
    ) {
    }

    /**
     * Constructs a SEP30ResponseIdentity from JSON data.
     *
     * @param array<array-key, mixed> $json The JSON data to parse.
     * @return SEP30ResponseIdentity The constructed identity.
     */
    public static function fromJson(array $json) : SEP30ResponseIdentity
    {
        $auth = null;
        if (isset($json['authenticated'])) {
            $auth = $json['authenticated'];
        }
        $role = isset($json['role']) ? $json['role'] : null;
        return new SEP30ResponseIdentity($role, $auth);
    }

    /**
     * Gets the identity role.
     *
     * @return string|null The role (e.g., "owner", "sender", "receiver").
     */
    public function getRole(): ?string
    {
        return $this->role;
    }

    /**
     * Sets the identity role.
     *
     * @param string|null $role The role (e.g., "owner", "sender", "receiver").
     */
    public function setRole(?string $role): void
    {
        $this->role = $role;
    }

    /**
     * Gets the authentication status.
     *
     * @return bool|null Whether the identity is authenticated.
     */
    public function getAuthenticated(): ?bool
    {
        return $this->authenticated;
    }

    /**
     * Sets the authentication status.
     *
     * @param bool|null $authenticated Whether the identity is authenticated.
     */
    public function setAuthenticated(?bool $authenticated): void
    {
        $this->authenticated = $authenticated;
    }
}
