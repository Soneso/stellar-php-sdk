<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

class DataRemovedEffectResponse extends EffectResponse
{
    private string $name;

    /**
     * @return string
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