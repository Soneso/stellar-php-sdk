<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Recovery;

/**
 * Identity information for SEP-30 account recovery registration requests.
 *
 * Represents a person or entity that can authenticate to gain control of an account.
 * Each identity has a role and one or more authentication methods.
 *
 * IDENTITY ROLES:
 * Roles are client-defined labels that describe the relationship between the identity
 * and the account. The server stores but does not validate roles - they are returned
 * in responses to help users understand their relationship to the account.
 *
 * Common role examples:
 * - "owner": The primary account owner (single-user recovery scenario)
 * - "sender": Person sharing the account (account sharing scenario)
 * - "receiver": Person receiving shared account access (account sharing scenario)
 * - "device": Specific device for multi-device access scenarios
 *
 * USE CASES:
 *
 * Single-Party Recovery:
 * - One identity with role "owner"
 * - Multiple authentication methods for the owner (phone, email, stellar address)
 * - Enables account recovery if the owner loses their private key
 *
 * Account Sharing:
 * - Multiple identities with different roles (e.g., "sender", "receiver")
 * - Each identity has their own authentication methods
 * - Both parties can recover the shared account
 * - Useful for shared accounts between family members or business partners
 *
 * Multi-Device Access:
 * - Multiple identities with role "device" or device-specific roles
 * - Each device has separate authentication methods
 * - Enables recovery from any registered device
 *
 * @package Soneso\StellarSDK\SEP\Recovery
 * @see https://github.com/stellar/stellar-protocol/blob/v0.8.1/ecosystem/sep-0030.md#common-request-fields
 * @see SEP30AuthMethod
 * @see SEP30Request
 * @see RecoveryService
 */
class SEP30RequestIdentity
{
    /**
     * The identity role describing the relationship to the account.
     *
     * This is a client-defined value (e.g., "owner", "sender", "receiver").
     * The server stores and returns this value but does not validate it.
     *
     * @var string
     */
    public string $role;

    /**
     * Array of authentication methods for this identity.
     *
     * Each identity must have at least one authentication method.
     * Multiple methods provide flexibility and redundancy for recovery.
     *
     * @var array<SEP30AuthMethod> $authMethods
     */
    public array $authMethods;

    /**
     * Constructs a new identity for account recovery.
     *
     * @param string $role The identity role (e.g., "owner", "sender", "receiver").
     *                     Client-defined value describing relationship to account.
     * @param array<SEP30AuthMethod> $authMethods Array of authentication methods for this identity.
     *                                             Must contain at least one method.
     */
    public function __construct(string $role, array $authMethods)
    {
        $this->role = $role;
        $this->authMethods = $authMethods;
    }

    public function toJson() : array {

        $authMethodsJson = array();

        foreach ($this->authMethods as $authMethod) {
            if ($authMethod instanceof SEP30AuthMethod) {
                $authMethodsJson[] = $authMethod->toJson();
            }
        }
        return array(
            'role' => $this->role,
            'auth_methods' => $authMethodsJson
        );
    }

    /**
     * Gets the identity role.
     *
     * @return string The role (e.g., "owner", "sender", "receiver").
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * Sets the identity role.
     *
     * @param string $role The role (e.g., "owner", "sender", "receiver").
     *                     Client-defined value describing relationship to account.
     */
    public function setRole(string $role): void
    {
        $this->role = $role;
    }

    /**
     * Gets the authentication methods for this identity.
     *
     * @return array<SEP30AuthMethod> Array of authentication methods.
     */
    public function getAuthMethods(): array
    {
        return $this->authMethods;
    }

    /**
     * Sets the authentication methods for this identity.
     *
     * @param array<SEP30AuthMethod> $authMethods Array of authentication methods.
     *                                             Must contain at least one method.
     */
    public function setAuthMethods(array $authMethods): void
    {
        $this->authMethods = $authMethods;
    }
}