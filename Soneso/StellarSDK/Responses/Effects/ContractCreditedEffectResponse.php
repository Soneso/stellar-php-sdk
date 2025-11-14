<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

/**
 * Represents an effect when a Soroban smart contract receives funds
 *
 * This effect occurs when assets are credited to a smart contract address in the Soroban
 * environment. Contracts can hold and manage Stellar assets including native XLM and
 * issued assets. Triggered by Soroban contract invocations that transfer assets.
 *
 * @package Soneso\StellarSDK\Responses\Effects
 * @see EffectResponse
 * @see https://developers.stellar.org Stellar developer docs
 */
class ContractCreditedEffectResponse extends EffectResponse
{
    public string $contract;
    public string $amount;
    public string $assetType;
    public ?string $assetCode = null;
    public ?string $assetIssuer = null;

    /**
     * Loads object data from JSON array
     *
     * @param array $json JSON data array
     * @return void
     */
    protected function loadFromJson(array $json): void
    {
        $this->contract = $json['contract'];
        $this->amount = $json['amount'];
        $this->assetType = $json['asset_type'];
        if (isset($json['asset_code'])) $this->assetCode = $json['asset_code'];
        if (isset($json['asset_issuer'])) $this->assetIssuer = $json['asset_issuer'];
        parent::loadFromJson($json);
    }

    /**
     * Creates an instance from JSON data
     *
     * @param array $jsonData JSON data array
     * @return ContractCreditedEffectResponse
     */
    public static function fromJson(array $jsonData): ContractCreditedEffectResponse
    {
        $result = new ContractCreditedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }

    /**
     * Gets the contract address that was credited
     *
     * @return string The contract address
     */
    public function getContract(): string
    {
        return $this->contract;
    }

    /**
     * Sets the contract address
     *
     * @param string $contract The contract address
     * @return void
     */
    public function setContract(string $contract): void
    {
        $this->contract = $contract;
    }

    /**
     * Gets the amount credited
     *
     * @return string The amount
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Sets the amount credited
     *
     * @param string $amount The amount
     * @return void
     */
    public function setAmount(string $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * Gets the asset type
     *
     * @return string The asset type
     */
    public function getAssetType(): string
    {
        return $this->assetType;
    }

    /**
     * Sets the asset type
     *
     * @param string $assetType The asset type
     * @return void
     */
    public function setAssetType(string $assetType): void
    {
        $this->assetType = $assetType;
    }

    /**
     * Gets the asset code
     *
     * @return string|null The asset code, or null for native assets
     */
    public function getAssetCode(): ?string
    {
        return $this->assetCode;
    }

    /**
     * Sets the asset code
     *
     * @param string|null $assetCode The asset code
     * @return void
     */
    public function setAssetCode(?string $assetCode): void
    {
        $this->assetCode = $assetCode;
    }

    /**
     * Gets the asset issuer account ID
     *
     * @return string|null The issuer's account ID, or null for native assets
     */
    public function getAssetIssuer(): ?string
    {
        return $this->assetIssuer;
    }

    /**
     * Sets the asset issuer account ID
     *
     * @param string|null $assetIssuer The issuer's account ID
     * @return void
     */
    public function setAssetIssuer(?string $assetIssuer): void
    {
        $this->assetIssuer = $assetIssuer;
    }

}
