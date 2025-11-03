<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Account;

/**
 * Represents the signature threshold levels for an account
 *
 * Thresholds determine the combined weight required from signers to authorize operations
 * of different security levels. Each operation type is assigned to low, medium, or high threshold.
 *
 * Threshold levels:
 * - Low: Used for AllowTrust, BumpSequence operations
 * - Medium: Used for all other operations except SetOptions
 * - High: Used for SetOptions operation to change thresholds, signers, or master weight
 *
 * This response is included in AccountResponse as part of the account details.
 *
 * @package Soneso\StellarSDK\Responses\Account
 * @see AccountResponse For the parent account details
 * @see AccountSignerResponse For signer weights
 * @see https://developers.stellar.org/docs/encyclopedia/signatures-multisig Signature and Multisig Documentation
 * @since 1.0.0
 */
class AccountThresholdsResponse
{

    private int $lowThreshold;
    private int $medThreshold;
    private int $highThreshold;

    /**
     * Gets the low security threshold value
     *
     * Operations at this level include AllowTrust and BumpSequence.
     *
     * @return int The low threshold weight required (0-255)
     */
    public function getLowThreshold() : int {
        return $this->lowThreshold;
    }

    /**
     * Gets the medium security threshold value
     *
     * Most operations use this threshold level by default.
     *
     * @return int The medium threshold weight required (0-255)
     */
    public function getMedThreshold() : int {
        return $this->medThreshold;
    }

    /**
     * Gets the high security threshold value
     *
     * SetOptions operations use this threshold to prevent unauthorized account changes.
     *
     * @return int The high threshold weight required (0-255)
     */
    public function getHighThreshold() : int {
        return $this->highThreshold;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['low_threshold'])) $this->lowThreshold = $json['low_threshold'];
        if (isset($json['med_threshold'])) $this->medThreshold = $json['med_threshold'];
        if (isset($json['high_threshold'])) $this->highThreshold = $json['high_threshold'];
    }

    /**
     * Creates an AccountThresholdsResponse instance from JSON data
     *
     * @param array $json The JSON array containing threshold data from Horizon
     * @return AccountThresholdsResponse The parsed thresholds response
     */
    public static function fromJson(array $json) : AccountThresholdsResponse {
        $result = new AccountThresholdsResponse();
        $result->loadFromJson($json);
        return $result;
    }
}

