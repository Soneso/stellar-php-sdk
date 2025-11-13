<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

/**
 * Represents a sequence bumped effect from the Stellar network
 *
 * This effect occurs when an account's sequence number is bumped forward.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse Base effect class
 * @see https://developers.stellar.org Stellar developer docs Horizon Effects API
 * @since 1.0.0
 */
class SequenceBumpedEffectResponse extends EffectResponse
{
    private string $newSequence;

    /**
     * Gets the new sequence number
     *
     * @return string The new sequence number as a string
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