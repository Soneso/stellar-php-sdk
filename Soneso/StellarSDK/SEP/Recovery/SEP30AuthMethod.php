<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Recovery;

/**
 * Authentication method for SEP-30 account recovery identity verification.
 *
 * Represents a single authentication method that can be used to prove identity ownership.
 * Multiple authentication methods can be associated with a single identity role.
 *
 * STANDARD AUTHENTICATION TYPES:
 *
 * stellar_address:
 * - Stellar account address in G... format (e.g., "GDUAB...")
 * - Proven via SEP-10 Web Authentication
 * - Provides highest security as it requires cryptographic proof
 *
 * phone_number:
 * - Phone number in ITU-T E.164 international format
 * - Must include country code with leading + and no spaces
 * - Example: "+10000000001" (not "+1 000 000 0001")
 * - Vulnerable to SIM swapping attacks - evaluate risk for account value
 *
 * email:
 * - Standard email address format (e.g., "user@example.com")
 * - Security depends on email provider's authentication
 *
 * CUSTOM AUTHENTICATION TYPES:
 * - Custom types may be supported by specific server implementations
 * - Check recovery server documentation for supported custom types
 *
 * @package Soneso\StellarSDK\SEP\Recovery
 * @see https://github.com/stellar/stellar-protocol/blob/v0.8.1/ecosystem/sep-0030.md#common-authentication-methods
 * @see https://www.itu.int/rec/T-REC-E.164 ITU-T E.164 Phone Number Format
 * @see SEP30RequestIdentity
 * @see RecoveryService
 */
class SEP30AuthMethod
{
    /**
     * The authentication method type.
     *
     * Standard types: "stellar_address", "phone_number", "email"
     * Custom types may be supported by specific server implementations.
     *
     * @var string
     */
    public string $type;

    /**
     * The authentication value/identifier.
     *
     * Format depends on type:
     * - stellar_address: "GDUAB..." (Stellar G-address)
     * - phone_number: "+10000000001" (E.164 format with +, no spaces)
     * - email: "user@example.com"
     *
     * @var string
     */
    public string $value;

    /**
     * Constructs a new authentication method.
     *
     * @param string $type The authentication method type (e.g., "stellar_address", "phone_number", "email").
     * @param string $value The authentication value/identifier in the format required by the type.
     */
    public function __construct(string $type, string $value)
    {
        $this->type = $type;
        $this->value = $value;
    }

    public function toJson() : array {
        return array(
            'type' => $this->type,
            'value' => $this->value
        );
    }

    /**
     * Gets the authentication method type.
     *
     * @return string The type (e.g., "stellar_address", "phone_number", "email").
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Sets the authentication method type.
     *
     * @param string $type The type (e.g., "stellar_address", "phone_number", "email").
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * Gets the authentication value/identifier.
     *
     * @return string The value in format specific to the type (e.g., "GDUAB...", "+10000000001", "user@example.com").
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Sets the authentication value/identifier.
     *
     * @param string $value The value in format specific to the type.
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }


}