<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;

use Soneso\StellarSDK\Asset;

class SetTrustlineFlagsOperationResponse extends OperationResponse
{
    private string $trustor;
    private Asset $asset;
    private ?array $setFlags = null; // [int]
    private ?array $setFlagsS = null; // [string]
    private ?array $clearFlags = null; // [int]
    private ?array $clearFlagsS = null;

    /**
     * @return string
     */
    public function getTrustor(): string
    {
        return $this->trustor;
    }

    /**
     * @return Asset
     */
    public function getAsset(): Asset
    {
        return $this->asset;
    }

    /**
     * @return array|null
     */
    public function getSetFlags(): ?array
    {
        return $this->setFlags;
    }

    /**
     * @return array|null
     */
    public function getSetFlagsS(): ?array
    {
        return $this->setFlagsS;
    }

    /**
     * @return array|null
     */
    public function getClearFlags(): ?array
    {
        return $this->clearFlags;
    }

    /**
     * @return array|null
     */
    public function getClearFlagsS(): ?array
    {
        return $this->clearFlagsS;
    } // [string]

    protected function loadFromJson(array $json) : void {

        if (isset($json['trustor'])) $this->trustor = $json['trustor'];

        if (isset($json['asset_type'])) {
            $assetCode = $json['asset_code'] ?? null;
            $assetIssuer = $json['asset_issuer'] ?? null;
            $this->asset = Asset::create($json['asset_type'], $assetCode, $assetIssuer);
        }

        if (isset($json['set_flags'])) {
            $this->setFlags = array();
            foreach ($json['set_flags'] as $value) {
                $this->setFlags->add($value);
            }
        }

        if (isset($json['set_flags_s'])) {
            $this->setFlagsS = array();
            foreach ($json['set_flags_s'] as $value) {
                $this->setFlagsS->add($value);
            }
        }

        if (isset($json['clear_flags'])) {
            $this->clearFlags = array();
            foreach ($json['clear_flags'] as $value) {
                $this->clearFlags->add($value);
            }
        }

        if (isset($json['clear_flags_s'])) {
            $this->clearFlagsS = array();
            foreach ($json['clear_flags_s'] as $value) {
                $this->clearFlagsS->add($value);
            }
        }

        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : SetTrustlineFlagsOperationResponse {
        $result = new SetTrustlineFlagsOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }

}