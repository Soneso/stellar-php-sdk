<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;

/**
 * Represents a manage data operation response from Horizon API
 *
 * This operation sets, modifies, or deletes a data entry attached to an account.
 * Contains the data entry name and value (base64-encoded).
 *
 * @package Soneso\StellarSDK\Responses\Operations
 * @see OperationResponse Base operation response
 * @see https://developers.stellar.org Stellar developer docs Horizon Manage Data
 * @since 1.0.0
 */
class ManageDataOperationResponse extends OperationResponse
{
    private string $name;
    private string $value;

    /**
     * Gets the data entry name
     *
     * @return string The data entry key
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the data entry value
     *
     * @return string The data value (base64-encoded), or empty string if deleted
     */
    public function getValue(): string
    {
        return $this->value;
    }

    protected function loadFromJson(array $json): void
    {

        if (isset($json['name'])) $this->name = $json['name'];
        if (isset($json['value'])) $this->value = $json['value'];

        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData): ManageDataOperationResponse
    {
        $result = new ManageDataOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}
