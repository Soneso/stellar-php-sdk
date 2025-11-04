<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

/**
 * Represents a data entry created effect from the Stellar network
 *
 * This effect occurs when a new data entry is added to an account.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse Base effect class
 * @see https://developers.stellar.org/api/resources/effects Horizon Effects API
 * @since 1.0.0
 */
class DataCreatedEffectResponse extends EffectResponse
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
     * Gets the value of the data entry
     *
     * @return string The data entry value (base64 encoded)
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

    public static function fromJson(array $jsonData): DataCreatedEffectResponse
    {
        $result = new DataCreatedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}