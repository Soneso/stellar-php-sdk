<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

/**
 * Represents a data entry removed effect from the Stellar network
 *
 * This effect occurs when a data entry is removed from an account.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse Base effect class
 * @see https://developers.stellar.org/api/resources/effects Horizon Effects API
 * @since 1.0.0
 */
class DataRemovedEffectResponse extends EffectResponse
{
    private string $name;

    /**
     * Gets the name of the removed data entry
     *
     * @return string The data entry name
     */
    public function getValue(): string
    {
        return $this->value;
    }

    protected function loadFromJson(array $json): void
    {
        if (isset($json['name'])) $this->name = $json['name'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData): DataRemovedEffectResponse
    {
        $result = new DataRemovedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}