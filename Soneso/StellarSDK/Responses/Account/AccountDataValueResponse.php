<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Account;

use Soneso\StellarSDK\Responses\Response;

/**
 * Represents a single data value for a specific key in an account.
 * This is the response from the /accounts/{account_id}/data/{key} endpoint.
 */
class AccountDataValueResponse extends Response
{
    private string $value;

    /**
     * @param string $value The base64-encoded data value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * Returns the base64-encoded value.
     *
     * @return string The base64-encoded value
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Returns the decoded value.
     *
     * @return string|false The decoded value, or false if decoding fails
     */
    public function getDecodedValue(): string|false
    {
        return base64_decode($this->value, true);
    }

    /**
     * Creates an AccountDataValueResponse from JSON data.
     *
     * @param array $json The JSON data from Horizon API
     * @return AccountDataValueResponse
     */
    public static function fromJson(array $json): AccountDataValueResponse
    {
        $value = $json['value'] ?? '';
        return new AccountDataValueResponse($value);
    }
}
