<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

use Soneso\StellarSDK\Responses\Response;

class SEP24WithdrawAsset extends Response
{
    /**
     * @var bool true if withdrawal for this asset is supported
     */
    public bool $enabled;

    /**
     * @var float|null Optional minimum amount. No limit if not specified.
     */
    public ?float $minAmount = null;

    /**
     * @var float|null Optional maximum amount. No limit if not specified.
     */
    public ?float $maxAmount = null;

    /**
     * @var float|null Optional fixed (base) fee for withdraw. In units of the withdrawn asset.
     * This is in addition to any fee_percent.
     */
    public ?float $feeFixed = null;

    /**
     * @var float|null Optional percentage fee for withdraw in percentage points.
     * This is in addition to any fee_fixed.
     */
    public ?float $feePercent = null;

    /**
     * @var float|null Optional minimum fee in units of the withdrawn asset.
     */
    public ?float $feeMinimum = null;

    /**
     * Loads the needed data from a json array.
     * @param array<array-key, mixed> $json the data array to read from.
     * @return void
     */
    protected function loadFromJson(array $json) : void {
        if (isset($json['enabled'])) $this->enabled = $json['enabled'];
        if (isset($json['min_amount'])) $this->minAmount = $json['min_amount'];
        if (isset($json['max_amount'])) $this->maxAmount = $json['max_amount'];
        if (isset($json['fee_fixed'])) $this->feeFixed = $json['fee_fixed'];
        if (isset($json['fee_percent'])) $this->feePercent = $json['fee_percent'];
        if (isset($json['fee_minimum'])) $this->feeMinimum  = $json['fee_minimum'];
    }

    /**
     * Constructs a new instance of SEP24WithdrawAsset by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return SEP24WithdrawAsset the object containing the parsed data.
     */
    public static function fromJson(array $json) : SEP24WithdrawAsset
    {
        $result = new SEP24WithdrawAsset();
        $result->loadFromJson($json);

        return $result;
    }

    /**
     * @return bool true if withdrawal for this asset is supported
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled true if withdrawal for this asset is supported
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * @return float|null Optional minimum amount. No limit if not specified.
     */
    public function getMinAmount(): ?float
    {
        return $this->minAmount;
    }

    /**
     * @param float|null $minAmount Optional minimum amount. No limit if not specified.
     */
    public function setMinAmount(?float $minAmount): void
    {
        $this->minAmount = $minAmount;
    }

    /**
     * @return float|null Optional maximum amount. No limit if not specified.
     */
    public function getMaxAmount(): ?float
    {
        return $this->maxAmount;
    }

    /**
     * @param float|null $maxAmount Optional maximum amount. No limit if not specified.
     */
    public function setMaxAmount(?float $maxAmount): void
    {
        $this->maxAmount = $maxAmount;
    }

    /**
     * @return float|null Optional fixed (base) fee for withdraw. In units of the withdrawn asset.
     *  This is in addition to any fee_percent.
     */
    public function getFeeFixed(): ?float
    {
        return $this->feeFixed;
    }

    /**
     * @param float|null $feeFixed Optional fixed (base) fee for withdraw. In units of the withdrawn asset.
     *  This is in addition to any fee_percent.
     */
    public function setFeeFixed(?float $feeFixed): void
    {
        $this->feeFixed = $feeFixed;
    }

    /**
     * @return float|null Optional percentage fee for withdraw in percentage points.
     *  This is in addition to any fee_fixed.
     */
    public function getFeePercent(): ?float
    {
        return $this->feePercent;
    }

    /**
     * @param float|null $feePercent Optional percentage fee for withdraw in percentage points.
     *  This is in addition to any fee_fixed.
     */
    public function setFeePercent(?float $feePercent): void
    {
        $this->feePercent = $feePercent;
    }

    /**
     * @return float|null Optional minimum fee in units of the withdrawn asset.
     */
    public function getFeeMinimum(): ?float
    {
        return $this->feeMinimum;
    }

    /**
     * @param float|null $feeMinimum Optional minimum fee in units of the withdrawn asset.
     */
    public function setFeeMinimum(?float $feeMinimum): void
    {
        $this->feeMinimum = $feeMinimum;
    }
}