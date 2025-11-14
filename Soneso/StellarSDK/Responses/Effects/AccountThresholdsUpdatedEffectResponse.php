<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

/**
 * Represents an account thresholds updated effect from the Stellar network
 *
 * This effect occurs when an account's signature thresholds are modified.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse Base effect class
 * @see https://developers.stellar.org Stellar developer docs Horizon Effects API
 * @since 1.0.0
 */
class AccountThresholdsUpdatedEffectResponse extends EffectResponse
{
    private int $lowThreshold;
    private int $medThreshold;
    private int $highThreshold;

    /**
     * Gets the low threshold value
     *
     * @return int The threshold for low security operations
     */
    public function getLowThreshold(): int
    {
        return $this->lowThreshold;
    }

    /**
     * Gets the medium threshold value
     *
     * @return int The threshold for medium security operations
     */
    public function getMedThreshold(): int
    {
        return $this->medThreshold;
    }

    /**
     * Gets the high threshold value
     *
     * @return int The threshold for high security operations
     */
    public function getHighThreshold(): int
    {
        return $this->highThreshold;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['low_threshold'])) $this->lowThreshold = $json['low_threshold'];
        if (isset($json['med_threshold'])) $this->medThreshold = $json['med_threshold'];
        if (isset($json['high_threshold'])) $this->highThreshold = $json['high_threshold'];

        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : AccountThresholdsUpdatedEffectResponse {
        $result = new AccountThresholdsUpdatedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}