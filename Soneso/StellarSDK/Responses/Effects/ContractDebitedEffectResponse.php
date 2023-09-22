<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

class ContractDebitedEffectResponse extends EffectResponse
{
    public string $contract;
    public string $amount;
    public string $assetType;
    public ?string $assetCode = null;
    public ?string $assetIssuer = null;

    protected function loadFromJson(array $json): void
    {
        $this->contract = $json['contract'];
        $this->amount = $json['amount'];
        $this->assetType = $json['asset_type'];
        if (isset($json['asset_code'])) $this->assetCode = $json['asset_code'];
        if (isset($json['asset_issuer'])) $this->assetIssuer = $json['asset_issuer'];
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData): ContractDebitedEffectResponse
    {
        $result = new ContractDebitedEffectResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }

    /**
     * @return string
     */
    public function getContract(): string
    {
        return $this->contract;
    }

    /**
     * @param string $contract
     */
    public function setContract(string $contract): void
    {
        $this->contract = $contract;
    }

    /**
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * @param string $amount
     */
    public function setAmount(string $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return string
     */
    public function getAssetType(): string
    {
        return $this->assetType;
    }

    /**
     * @param string $assetType
     */
    public function setAssetType(string $assetType): void
    {
        $this->assetType = $assetType;
    }

    /**
     * @return string|null
     */
    public function getAssetCode(): ?string
    {
        return $this->assetCode;
    }

    /**
     * @param string|null $assetCode
     */
    public function setAssetCode(?string $assetCode): void
    {
        $this->assetCode = $assetCode;
    }

    /**
     * @return string|null
     */
    public function getAssetIssuer(): ?string
    {
        return $this->assetIssuer;
    }

    /**
     * @param string|null $assetIssuer
     */
    public function setAssetIssuer(?string $assetIssuer): void
    {
        $this->assetIssuer = $assetIssuer;
    }

}