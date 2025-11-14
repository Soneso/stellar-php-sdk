<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;

use Soneso\StellarSDK\Asset;

/**
 * Represents a set trustline flags operation response from Horizon API
 *
 * This operation sets or clears flags on a trustline, controlling authorization and clawback behavior.
 * The asset issuer can authorize accounts to hold the asset, enable clawback capabilities, or restrict
 * the trustline to maintain liabilities only. This provides granular control over asset distribution
 * and compliance requirements. This operation supersedes the deprecated AllowTrust operation.
 *
 * @package Soneso\StellarSDK\Responses\Operations
 * @see OperationResponse Base operation response
 * @see https://developers.stellar.org Stellar developer docs Horizon Set Trustline Flags Operation
 */
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
     * Gets the account holding the trustline
     *
     * @return string The trustor account ID
     */
    public function getTrustor(): string
    {
        return $this->trustor;
    }

    /**
     * Gets the asset type
     *
     * @return string The asset type (native, credit_alphanum4, or credit_alphanum12)
     */
    public function getAssetType(): string
    {
        return $this->assetType;
    }

    /**
     * Gets the asset code
     *
     * @return string|null The asset code or null for native assets
     */
    public function getAssetCode(): ?string
    {
        return $this->assetCode;
    }

    /**
     * Gets the asset issuer
     *
     * @return string|null The asset issuer account ID or null for native assets
     */
    public function getAssetIssuer(): ?string
    {
        return $this->assetIssuer;
    }

    /**
     * Gets the flags being set as integer values
     *
     * @return array|null Array of flag integers or null
     */
    public function getSetFlags(): ?array
    {
        return $this->setFlags;
    }

    /**
     * Gets the flags being set as string names
     *
     * @return array|null Array of flag names or null
     */
    public function getSetFlagsS(): ?array
    {
        return $this->setFlagsS;
    }

    /**
     * Gets the flags being cleared as integer values
     *
     * @return array|null Array of flag integers or null
     */
    public function getClearFlags(): ?array
    {
        return $this->clearFlags;
    }

    /**
     * Gets the flags being cleared as string names
     *
     * @return array|null Array of flag names or null
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