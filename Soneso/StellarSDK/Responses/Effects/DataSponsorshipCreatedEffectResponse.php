<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

class DataSponsorshipCreatedEffectResponse extends EffectResponse
{
    private string $dataName;
    private string $sponsor;

    /**
     * @return string
     */
    public function getDataName(): string
    {
        return $this->dataName;
    }

    /**
     * @return string
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