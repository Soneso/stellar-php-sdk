<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

/**
 * Represents a data entry updated effect from the Stellar network
 *
 * This effect occurs when a data entry's value is modified.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse Base effect class
 * @see https://developers.stellar.org Stellar developer docs Horizon Effects API
 * @since 1.0.0
 */
class DataUpdatedEffectResponse extends EffectResponse
{
    private string $name;
    private string $value;

    /**
     * Gets the name of the data entry
     *
     * @return string The data entry name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the new value of the data entry
     *
     * @return string The data entry value (base64 encoded)
     */
    public function getValue(): string
    {
        return $this->value;
    }

    protected function loadFromJson(array $json): void {
        if (isset($json['name'])) $this->name = $json['name'];
        if (isset($json['value'])) $this->value = $json['value'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData): DataUpdatedEffectResponse {
        $result = new DataUpdatedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}