<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Account;

/**
 * Represents data entries stored on an account
 *
 * Accounts can store arbitrary key-value data on the ledger using the ManageData operation.
 * Each data entry is stored as a base64-encoded string. This response provides methods to
 * access data entries both in their raw base64 form and decoded as strings.
 *
 * Data entries are limited to:
 * - Key: Maximum 64 bytes
 * - Value: Maximum 64 bytes
 *
 * This response is included in AccountResponse as part of the account details.
 *
 * @package Soneso\StellarSDK\Responses\Account
 * @see AccountResponse For the parent account details
 * @see https://developers.stellar.org Stellar developer docs Data Entries Documentation
 * @since 1.0.0
 */
class AccountDataResponse
{
    private array $data;

    /**
     * Creates a new account data response
     *
     * @param array $data Associative array of data entries (key => base64 value)
     */
    public function __construct(array $data = array()) {
        $this->data = $data;
    }

    /**
     * Gets a decoded data entry value by key
     *
     * Returns the data value decoded from base64 to its original string form.
     *
     * @param string $key The data entry key
     * @return string|null The decoded value, or null if key does not exist
     */
    public function get(string $key) : ?string {
        if (array_key_exists($key, $this->getData())) {
            return base64_decode($this->data[$key], true);
        }
        return null;
    }

    /**
     * Gets a base64-encoded data entry value by key
     *
     * Returns the data value in its raw base64-encoded form as stored on the ledger.
     *
     * @param string $key The data entry key
     * @return string|null The base64-encoded value, or null if key does not exist
     */
    public function getBase64Encoded(string $key) : ?string {
        if (array_key_exists($key, $this->getData())) {
            return $this->data[$key];
        }
        return null;
    }

    /**
     * Gets all data entry keys
     *
     * @return array Array of data entry keys
     */
    public function getKeys() : array {
        return array_keys($this->getData());
    }

    /**
     * Gets all data entries as an associative array
     *
     * @return array Associative array of all data entries (key => base64 value)
     */
    public function getData() : array {
        return $this->data;
    }

    /**
     * Creates an AccountDataResponse instance from JSON data
     *
     * @param array $json The JSON array containing data entries from Horizon
     * @return AccountDataResponse The parsed data response
     */
    public static function fromJson(array $json) : AccountDataResponse {
        $accountData = array();
        if (isset($json['data'])) {
            foreach ($json['data'] as $key => $value) {
                $accountData[$key] = $value;
            }
        }
        return new AccountDataResponse($accountData);
    }
}

