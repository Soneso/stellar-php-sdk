<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;


class SetOptionsOperationResponse extends OperationResponse
{
    private ?int $lowThreshold = null;
    private ?int $medThreshold = null;
    private ?int $highThreshold = null;
    private ?string $inflationDestination = null;
    private ?string $homeDomain = null;
    private ?string $signerKey = null;
    private ?int $signerWeight = null;
    private ?int $masterKeyWeight = null;
    private ?array $setFlags = null; // [int]
    private ?array $setFlagsS = null; // [string]
    private ?array $clearFlags = null; // [int]
    private ?array $clearFlagsS = null;

    /**
     * @return int|null
     */
    public function getLowThreshold(): ?int
    {
        return $this->lowThreshold;
    }

    /**
     * @return int|null
     */
    public function getMedThreshold(): ?int
    {
        return $this->medThreshold;
    }

    /**
     * @return int|null
     */
    public function getHighThreshold(): ?int
    {
        return $this->highThreshold;
    }

    /**
     * @return string|null
     */
    public function getInflationDestination(): ?string
    {
        return $this->inflationDestination;
    }

    /**
     * @return string|null
     */
    public function getHomeDomain(): ?string
    {
        return $this->homeDomain;
    }

    /**
     * @return string|null
     */
    public function getSignerKey(): ?string
    {
        return $this->signerKey;
    }

    /**
     * @return int|null
     */
    public function getSignerWeight(): ?int
    {
        return $this->signerWeight;
    }

    /**
     * @return int|null
     */
    public function getMasterKeyWeight(): ?int
    {
        return $this->masterKeyWeight;
    }

    /**
     * @return array|null ?[int]
     */
    public function getSetFlags(): ?array
    {
        return $this->setFlags;
    }

    /**
     * @return array|null ?[string]
     */
    public function getSetFlagsS(): ?array
    {
        return $this->setFlagsS;
    }

    /**
     * @return array|null ?[int]
     */
    public function getClearFlags(): ?array
    {
        return $this->clearFlags;
    }

    /**
     * @return array|null ?[string]
     */
    public function getClearFlagsS(): ?array
    {
        return $this->clearFlagsS;
    } // [string]

    protected function loadFromJson(array $json) : void {

        if (isset($json['low_threshold'])) $this->lowThreshold = $json['low_threshold'];
        if (isset($json['med_threshold'])) $this->medThreshold = $json['med_threshold'];
        if (isset($json['high_threshold'])) $this->highThreshold = $json['high_threshold'];

        if (isset($json['inflation_dest'])) $this->inflationDestination = $json['inflation_dest'];
        if (isset($json['home_domain'])) $this->homeDomain = $json['home_domain'];
        if (isset($json['signer_key'])) $this->signerKey = $json['signer_key'];
        if (isset($json['signer_weight'])) $this->signerWeight = $json['signer_weight'];
        if (isset($json['master_key_weight'])) $this->masterKeyWeight = $json['master_key_weight'];


        if (isset($json['set_flags'])) {
            $this->setFlags = array();
            foreach ($json['set_flags'] as $value) {
                array_push($this->setFlags, $value);
            }
        }

        if (isset($json['set_flags_s'])) {
            $this->setFlagsS = array();
            foreach ($json['set_flags_s'] as $value) {
                array_push($this->setFlagsS, $value);
            }
        }

        if (isset($json['clear_flags'])) {
            $this->clearFlags = array();
            foreach ($json['clear_flags'] as $value) {
                array_push($this->clearFlags, $value);
            }
        }

        if (isset($json['clear_flags_s'])) {
            $this->clearFlagsS = array();
            foreach ($json['clear_flags_s'] as $value) {
                array_push($this->clearFlagsS, $value);
            }
        }

        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : SetOptionsOperationResponse {
        $result = new SetOptionsOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }
}