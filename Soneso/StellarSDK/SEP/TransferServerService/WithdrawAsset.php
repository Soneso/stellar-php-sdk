<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

use Soneso\StellarSDK\Responses\Response;

class WithdrawAsset extends Response
{
    private bool $enabled;
    private ?bool $authenticationRequired = null;
    private ?float $feeFixed = null;
    private ?float $feePercent = null;
    private ?float $minAmount = null;
    private ?float $maxAmount = null;
    private ?array $types = null; // [string => [string => AnchorField]]

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
    public function getTypes(): ?array
    {
        return $this->types;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['enabled'])) $this->enabled = $json['enabled'];
        if (isset($json['authentication_required'])) $this->authenticationRequired = $json['authentication_required'];
        if (isset($json['fee_fixed'])) $this->feeFixed = $json['fee_fixed'];
        if (isset($json['fee_percent'])) $this->feePercent = $json['fee_percent'];
        if (isset($json['min_amount'])) $this->minAmount = $json['min_amount'];
        if (isset($json['max_amount'])) $this->maxAmount = $json['max_amount'];
        if (isset($json['types'])) {
            $this->types = array();
            $typesFields = $json['types'];
            foreach(array_keys($typesFields) as $typeKey) {
                $fields = array();
                foreach(array_keys($typesFields[$typeKey]['fields']) as $fieldKey) {
                    $value = AnchorField::fromJson($typesFields[$typeKey]['fields'][$fieldKey]);
                    $fields += [$fieldKey => $value];
                }
                $this->types += [$typeKey => $fields];
            }
        }
    }

    public static function fromJson(array $json) : WithdrawAsset
    {
        $result = new WithdrawAsset();
        $result->loadFromJson($json);
        return $result;
    }
}