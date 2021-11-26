<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

class DataSponsorshipRemovedEffectResponse extends EffectResponse
{
    private string $formerSponsor;
    private string $dataName;

    /**
     * @return string
     */
    public function getFormerSponsor(): string
    {
        return $this->formerSponsor;
    }

    /**
     * @return string
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