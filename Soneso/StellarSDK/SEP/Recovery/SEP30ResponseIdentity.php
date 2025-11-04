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
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0030.md
 * @see SEP30AccountResponse
 */
class SEP30ResponseIdentity
{
    public string $role;
    public ?bool $authenticated = null;

    /**
     * Constructor.
     *
     * @param string $role The identity role ("owner" or "other").
     * @param bool|null $authenticated Whether the identity is authenticated.
     */
    public function __construct(string $role, ?bool $authenticated = null)
    {
        $this->role = $role;
        $this->authenticated = $authenticated;
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
        return new SEP30ResponseIdentity($json['role'], $auth);
    }

    /**
     * Gets the identity role.
     *
     * @return string The role ("owner" or "other").
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * Sets the identity role.
     *
     * @param string $role The role.
     */
    public function setRole(string $role): void
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