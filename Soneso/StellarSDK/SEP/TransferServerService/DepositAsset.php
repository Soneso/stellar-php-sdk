<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

use Soneso\StellarSDK\Responses\Response;

class DepositAsset extends Response
{
    private bool $enabled;
    private ?bool $authenticationRequired = null;
    private ?float $feeFixed = null;
    private ?float $feePercent = null;
    private ?float $minAmount = null;
    private ?float $maxAmount = null;
    private ?array $fields = null;

    /**
     * @return bool
     */
    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return bool|null
     */
    public function getAuthenticationRequired(): ?bool
    {
        return $this->authenticationRequired;
    }

    /**
     * @return float|null
     */
    public function getFeeFixed(): ?float
    {
        return $this->feeFixed;
    }

    /**
     * @return float|null
     */
    public function getFeePercent(): ?float
    {
        return $this->feePercent;
    }

    /**
     * @return float|null
     */
    public function getMinAmount(): ?float
    {
        return $this->minAmount;
    }

    /**
     * @return float|null
     */
    public function getMaxAmount(): ?float
    {
        return $this->maxAmount;
    }

    /**
     * @return array|null
     */
    public function getFields(): ?array
    {
        return $this->fields;
    } //[string => AnchorField]

    protected function loadFromJson(array $json) : void {
        if (isset($json['enabled'])) $this->enabled = $json['enabled'];
        if (isset($json['authentication_required'])) $this->authenticationRequired = $json['authentication_required'];
        if (isset($json['fee_fixed'])) $this->feeFixed = $json['fee_fixed'];
        if (isset($json['fee_percent'])) $this->feePercent = $json['fee_percent'];
        if (isset($json['min_amount'])) $this->minAmount = $json['min_amount'];
        if (isset($json['max_amount'])) $this->maxAmount = $json['max_amount'];
        if (isset($json['fields'])) {
            $this->fields = array();
            $jsonFields = $json['fields'];
            foreach(array_keys($jsonFields) as $key) {
                $value = AnchorField::fromJson($jsonFields[$key]);
                $this->fields += [$key => $value];
            }
        }
    }

    public static function fromJson(array $json) : DepositAsset
    {
        $result = new DepositAsset();
        $result->loadFromJson($json);
        return $result;
    }
}