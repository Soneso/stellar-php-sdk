<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

use Soneso\StellarSDK\Responses\Response;

class SEP24InfoResponse extends Response
{
    public ?array $depositAssets = null; //[string => SEP24DepositAsset]
    public ?array $withdrawAssets = null; //[string => SEP24WithdrawAsset]
    public ?FeeEndpointInfo $feeEndpointInfo = null;
    public ?FeatureFlags $featureFlags = null;


    protected function loadFromJson(array $json) : void {
        if (isset($json['deposit'])) {
            $this->depositAssets = array();
            $jsonFields = $json['deposit'];
            foreach(array_keys($jsonFields) as $key) {
                $value = SEP24DepositAsset::fromJson($jsonFields[$key]);
                $this->depositAssets += [$key => $value];
            }
        }
        if (isset($json['withdraw'])) {
            $this->withdrawAssets = array();
            $jsonFields = $json['withdraw'];
            foreach(array_keys($jsonFields) as $key) {
                $value = SEP24WithdrawAsset::fromJson($jsonFields[$key]);
                $this->withdrawAssets += [$key => $value];
            }
        }
        if (isset($json['fee'])) $this->feeEndpointInfo = FeeEndpointInfo::fromJson($json['fee']);
        if (isset($json['features'])) $this->featureFlags = FeatureFlags::fromJson($json['features']);
    }

    public static function fromJson(array $json) : SEP24InfoResponse
    {
        $result = new SEP24InfoResponse();
        $result->loadFromJson($json);
        return $result;
    }

    /**
     * @return array|null
     */
    public function getDepositAssets(): ?array
    {
        return $this->depositAssets;
    }

    /**
     * @param array|null $depositAssets
     */
    public function setDepositAssets(?array $depositAssets): void
    {
        $this->depositAssets = $depositAssets;
    }

    /**
     * @return array|null
     */
    public function getWithdrawAssets(): ?array
    {
        return $this->withdrawAssets;
    }

    /**
     * @param array|null $withdrawAssets
     */
    public function setWithdrawAssets(?array $withdrawAssets): void
    {
        $this->withdrawAssets = $withdrawAssets;
    }

    /**
     * @return FeeEndpointInfo|null
     */
    public function getFeeEndpointInfo(): ?FeeEndpointInfo
    {
        return $this->feeEndpointInfo;
    }

    /**
     * @param FeeEndpointInfo|null $feeEndpointInfo
     */
    public function setFeeEndpointInfo(?FeeEndpointInfo $feeEndpointInfo): void
    {
        $this->feeEndpointInfo = $feeEndpointInfo;
    }

    /**
     * @return FeatureFlags|null
     */
    public function getFeatureFlags(): ?FeatureFlags
    {
        return $this->featureFlags;
    }

    /**
     * @param FeatureFlags|null $featureFlags
     */
    public function setFeatureFlags(?FeatureFlags $featureFlags): void
    {
        $this->featureFlags = $featureFlags;
    }
}