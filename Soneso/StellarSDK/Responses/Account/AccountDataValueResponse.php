<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Account;

use Soneso\StellarSDK\Responses\Response;

/**
 * Represents a single data entry value for a specific key in an account
 *
 * This response contains the value of a single data entry stored on an account ledger.
 * The value is stored as a base64-encoded string and can be decoded to retrieve the
 * original data. This response is returned by the account data value endpoint.
 *
 * Returned by endpoint:
 * - GET /accounts/{account_id}/data/{key} - Single data entry value
 *
 * @package Soneso\StellarSDK\Responses\Account
 * @see AccountDataResponse For all data entries on an account
 * @see https://developers.stellar.org Stellar developer docs Horizon Account Data API
 * @since 1.0.0
 */
class AccountDataValueResponse extends Response
{
    private string $value;

    /**
     * Creates a new account data value response
     *
     * @param string $value The base64-encoded data value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * Gets the base64-encoded value
     *
     * Returns the data value in its raw base64-encoded form as stored on the ledger.
     *
     * @return string The base64-encoded value
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Gets the decoded value
     *
     * Returns the data value decoded from base64 to its original form.
     *
     * @return string|false The decoded value, or false if decoding fails
     */
    public function getDecodedValue(): string|false
    {
        return base64_decode($this->value, true);
    }

    /**
     * Creates an AccountDataValueResponse instance from JSON data
     *
     * @param array $json The JSON array containing data value from Horizon
     * @return AccountDataValueResponse The parsed data value response
     */
    public static function fromJson(array $json): AccountDataValueResponse
    {
        $value = $json['value'] ?? '';
        return new AccountDataValueResponse($value);
    }
}
