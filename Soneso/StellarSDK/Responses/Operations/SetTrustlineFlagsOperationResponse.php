<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;

use Soneso\StellarSDK\Asset;

class SetTrustlineFlagsOperationResponse extends OperationResponse
{
    private string $trustor;
    private string $assetType;
    private ?string $assetCode = null;
    private ?string $assetIssuer = null;
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
     * @return string
     */
    public function getAssetType(): string
    {
        return $this->assetType;
    }

    /**
     * @return string|null
     */
    public function getAssetCode(): ?string
    {
        return $this->assetCode;
    }

    /**
     * @return string|null
     */
    public function getAssetIssuer(): ?string
    {
        return $this->assetIssuer;
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
    }



    protected function loadFromJson(array $json) : void {

        if (isset($json['trustor'])) $this->trustor = $json['trustor'];

        if (isset($json['asset_type'])) $this->assetType = $json['asset_type'];
        if (isset($json['asset_code'])) $this->assetCode = $json['asset_code'];
        if (isset($json['asset_issuer'])) $this->assetIssuer = $json['asset_issuer'];

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

    public static function fromJson(array $jsonData) : SetTrustlineFlagsOperationResponse {
        $result = new SetTrustlineFlagsOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }

}