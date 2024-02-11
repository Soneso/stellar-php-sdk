<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

use Soneso\StellarSDK\Responses\Response;

class SEP24InfoResponse extends Response
{
    /**
     * @var array<array-key, SEP24DepositAsset>|null deposit assets of the info response.
     */
    public ?array $depositAssets = null;

    /**
     * @var array<array-key, SEP24WithdrawAsset>|null withdrawal assets of the info response.
     */
    public ?array $withdrawAssets = null;

    /**
     * @var FeeEndpointInfo|null Info about the fee endpoint (e.g. if enabled and if it requires sep-10 authentication)
     */
    public ?FeeEndpointInfo $feeEndpointInfo = null;

    /**
     * @var FeatureFlags|null Info about the additional features the anchor offers.
     * E.g. whether the anchor supports creating accounts for users requesting deposits.
     */
    public ?FeatureFlags $featureFlags = null;


    /**
     * Loads the needed data from a json array.
     * @param array<array-key, mixed> $json the data array to read from.
     * @return void
     */
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

    /**
     * Constructs a new instance of SEP24InfoResponse by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return SEP24InfoResponse the object containing the parsed data.
     */
    public static function fromJson(array $json) : SEP24InfoResponse
    {
        $result = new SEP24InfoResponse();
        $result->loadFromJson($json);
        return $result;
    }

    /**
     * @return array<array-key, SEP24DepositAsset>|null deposit assets of the info response.
     */
    public function getDepositAssets(): ?array
    {
        return $this->depositAssets;
    }

    /**
     * @param array<array-key, SEP24DepositAsset>|null $depositAssets deposit assets of the info response.
     */
    public function setDepositAssets(?array $depositAssets): void
    {
        $this->depositAssets = $depositAssets;
    }

    /**
     * @return array<array-key, SEP24WithdrawAsset>|null withdrawal assets of the info response.
     */
    public function getWithdrawAssets(): ?array
    {
        return $this->withdrawAssets;
    }

    /**
     * @param array<array-key, SEP24WithdrawAsset>|null $withdrawAssets withdrawal assets of the info response.
     */
    public function setWithdrawAssets(?array $withdrawAssets): void
    {
        $this->withdrawAssets = $withdrawAssets;
    }

    /**
     * @return FeeEndpointInfo|null Info about the fee endpoint (e.g. if enabled and if it requires sep-10 authentication)
     */
    public function getFeeEndpointInfo(): ?FeeEndpointInfo
    {
        return $this->feeEndpointInfo;
    }

    /**
     * @param FeeEndpointInfo|null $feeEndpointInfo Info about the fee endpoint (e.g. if enabled and if it requires sep-10 authentication)
     */
    public function setFeeEndpointInfo(?FeeEndpointInfo $feeEndpointInfo): void
    {
        $this->feeEndpointInfo = $feeEndpointInfo;
    }

    /**
     * @return FeatureFlags|null Info about the additional features the anchor offers.
     *  E.g. whether the anchor supports creating accounts for users requesting deposits.
     */
    public function getFeatureFlags(): ?FeatureFlags
    {
        return $this->featureFlags;
    }

    /**
     * @param FeatureFlags|null $featureFlags Info about the additional features the anchor offers.
     * E.g. whether the anchor supports creating accounts for users requesting deposits.
     */
    public function setFeatureFlags(?FeatureFlags $featureFlags): void
    {
        $this->featureFlags = $featureFlags;
    }
}