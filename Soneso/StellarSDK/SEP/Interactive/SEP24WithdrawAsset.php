<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

use Soneso\StellarSDK\Responses\Response;

class SEP24WithdrawAsset extends Response
{
    /// true if withdrawal for this asset is supported
    public bool $enabled;
    /// Optional minimum amount. No limit if not specified.
    public ?float $minAmount = null;
    /// Optional maximum amount. No limit if not specified.
    public ?float $maxAmount = null;
    /// Optional fixed (base) fee for withdraw. In units of the withdrawn asset. This is in addition to any fee_percent.
    public ?float $feeFixed = null;
    /// Optional percentage fee for withdraw in percentage points. This is in addition to any fee_fixed.
    public ?float $feePercent = null;
    /// Optional minimum fee in units of the withdrawn asset.
    public ?float $feeMinimum = null;


    protected function loadFromJson(array $json) : void {
        if (isset($json['enabled'])) $this->enabled = $json['enabled'];
        if (isset($json['min_amount'])) $this->minAmount = $json['min_amount'];
        if (isset($json['max_amount'])) $this->maxAmount = $json['max_amount'];
        if (isset($json['fee_fixed'])) $this->feeFixed = $json['fee_fixed'];
        if (isset($json['fee_percent'])) $this->feePercent = $json['fee_percent'];
        if (isset($json['fee_minimum'])) $this->feeMinimum  = $json['fee_minimum'];
    }

    public static function fromJson(array $json) : SEP24WithdrawAsset
    {
        $result = new SEP24WithdrawAsset();
        $result->loadFromJson($json);
        return $result;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * @return float|null
     */
    public function getMinAmount(): ?float
    {
        return $this->minAmount;
    }

    /**
     * @param float|null $minAmount
     */
    public function setMinAmount(?float $minAmount): void
    {
        $this->minAmount = $minAmount;
    }

    /**
     * @return float|null
     */
    public function getMaxAmount(): ?float
    {
        return $this->maxAmount;
    }

    /**
     * @param float|null $maxAmount
     */
    public function setMaxAmount(?float $maxAmount): void
    {
        $this->maxAmount = $maxAmount;
    }

    /**
     * @return float|null
     */
    public function getFeeFixed(): ?float
    {
        return $this->feeFixed;
    }

    /**
     * @param float|null $feeFixed
     */
    public function setFeeFixed(?float $feeFixed): void
    {
        $this->feeFixed = $feeFixed;
    }

    /**
     * @return float|null
     */
    public function getFeePercent(): ?float
    {
        return $this->feePercent;
    }

    /**
     * @param float|null $feePercent
     */
    public function setFeePercent(?float $feePercent): void
    {
        $this->feePercent = $feePercent;
    }

    /**
     * @return float|null
     */
    public function getFeeMinimum(): ?float
    {
        return $this->feeMinimum;
    }

    /**
     * @param float|null $feeMinimum
     */
    public function setFeeMinimum(?float $feeMinimum): void
    {
        $this->feeMinimum = $feeMinimum;
    }
}