<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

use Soneso\StellarSDK\Responses\Response;

class DepositResponse extends Response
{
    /// Terse but complete instructions for how to deposit the asset. In the case of most cryptocurrencies it is just an address to which the deposit should be sent.
    private string $how;

    /// (optional) The anchor's ID for this deposit. The wallet will use this ID to query the /transaction endpoint to check status of the request.
    private ?string $id = null;

    /// (optional) Estimate of how long the deposit will take to credit in seconds.
    private ?int $eta;

    /// (optional) Minimum amount of an asset that a user can deposit
    private ?float $minAmount = null;

    /// (optional) Maximum amount of asset that a user can deposit.
    private ?float $maxAmount = null;

    /// (optional) Fixed fee (if any). In units of the deposited asset.
    private ?float $feeFixed = null;

    /// (optional) Percentage fee (if any). In units of percentage points.
    private ?float $feePercent = null;

    /// (optional) JSON object with additional information about the deposit process.
    private ?array $extraInfo = null;

    /**
     * Terse but complete instructions for how to deposit the asset. In the case of most cryptocurrencies it is just an address to which the deposit should be sent.
     * @return string
     */
    public function getHow(): string
    {
        return $this->how;
    }

    /**
     * (optional) The anchor's ID for this deposit. The wallet will use this ID to query the /transaction endpoint to check status of the request.
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * (optional) Estimate of how long the deposit will take to credit in seconds.
     * @return int|null
     */
    public function getEta(): ?int
    {
        return $this->eta;
    }

    /**
     * (optional) Minimum amount of an asset that a user can deposit
     * @return float|null
     */
    public function getMinAmount(): ?float
    {
        return $this->minAmount;
    }

    /**
     * (optional) Maximum amount of asset that a user can deposit.
     * @return float|null
     */
    public function getMaxAmount(): ?float
    {
        return $this->maxAmount;
    }

    /**
     * Fixed fee (if any). In units of the deposited asset.
     * @return float|null
     */
    public function getFeeFixed(): ?float
    {
        return $this->feeFixed;
    }

    /**
     * (optional) Percentage fee (if any). In units of percentage points.
     * @return float|null
     */
    public function getFeePercent(): ?float
    {
        return $this->feePercent;
    }

    /**
     * (optional) object with additional information about the deposit process.
     * @return array|null
     */
    public function getExtraInfo(): ?array
    {
        return $this->extraInfo;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['how'])) $this->how = $json['how'];
        if (isset($json['id'])) $this->id = $json['id'];
        if (isset($json['eta'])) $this->eta = $json['eta'];
        if (isset($json['fee_fixed'])) $this->feeFixed = $json['fee_fixed'];
        if (isset($json['fee_percent'])) $this->feePercent = $json['fee_percent'];
        if (isset($json['min_amount'])) $this->minAmount = $json['min_amount'];
        if (isset($json['max_amount'])) $this->maxAmount = $json['max_amount'];
        if (isset($json['extra_info'])) $this->extraInfo = $json['extra_info'];
    }

    public static function fromJson(array $json) : DepositResponse
    {
        $result = new DepositResponse();
        $result->loadFromJson($json);
        return $result;
    }
}