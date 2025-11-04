<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

/**
 * Represents a data entry sponsorship created effect from the Stellar network
 *
 * This effect occurs when sponsorship for a data entry's base reserve is established.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse Base effect class
 * @see https://developers.stellar.org/api/resources/effects Horizon Effects API
 * @since 1.0.0
 */
class DataSponsorshipCreatedEffectResponse extends EffectResponse
{
    private string $dataName;
    private string $sponsor;

    /**
     * Gets the name of the data entry
     *
     * @return string The data entry name
     */
    public function getDataName(): string
    {
        return $this->dataName;
    }

    /**
     * Gets the account ID of the sponsor
     *
     * @return string The sponsor's account ID
     */
    public function getSponsor(): string
    {
        return $this->sponsor;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['sponsor'])) $this->sponsor = $json['sponsor'];
        if (isset($json['data_name'])) $this->dataName = $json['data_name'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : DataSponsorshipCreatedEffectResponse {
        $result = new DataSponsorshipCreatedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}