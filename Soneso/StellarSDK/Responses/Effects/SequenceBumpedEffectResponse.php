<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

class SequenceBumpedEffectResponse extends EffectResponse
{
    private string $newSequence;

    /**
     * @return string
     */
    public function getNewSequence(): string
    {
        return $this->newSequence;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['new_seq'])) $this->newSequence = $json['new_seq'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : SequenceBumpedEffectResponse {
        $result = new SequenceBumpedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}