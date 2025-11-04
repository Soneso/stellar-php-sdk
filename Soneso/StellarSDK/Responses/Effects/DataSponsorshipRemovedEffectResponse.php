<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

/**
 * Represents a data entry sponsorship removed effect from the Stellar network
 *
 * This effect occurs when sponsorship for a data entry's base reserve is removed.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse Base effect class
 * @see https://developers.stellar.org/api/resources/effects Horizon Effects API
 * @since 1.0.0
 */
class DataSponsorshipRemovedEffectResponse extends EffectResponse
{
    private string $formerSponsor;
    private string $dataName;

    /**
     * Gets the account ID of the former sponsor
     *
     * @return string The former sponsor's account ID
     */
    public function getFormerSponsor(): string
    {
        return $this->formerSponsor;
    }

    /**
     * Gets the name of the data entry
     *
     * @return string The data entry name
     */
    public function getDataName(): string
    {
        return $this->dataName;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['former_sponsor'])) $this->formerSponsor = $json['former_sponsor'];
        if (isset($json['data_name'])) $this->dataName = $json['data_name'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : DataSponsorshipRemovedEffectResponse {
        $result = new DataSponsorshipRemovedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}