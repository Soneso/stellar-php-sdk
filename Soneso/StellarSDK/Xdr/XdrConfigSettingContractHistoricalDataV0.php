<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrConfigSettingContractHistoricalDataV0
{
    // Fee for storing 1KB in archives
    public int $feeHistorical1KB;

    /**
     * @param int $feeHistorical1KB Fee for storing 1KB in archives
     */
    public function __construct(int $feeHistorical1KB)
    {
        $this->feeHistorical1KB = $feeHistorical1KB;
    }


    public function encode(): string
    {
        return XdrEncoder::integer64($this->feeHistorical1KB);
    }

    public static function decode(XdrBuffer $xdr): XdrConfigSettingContractHistoricalDataV0
    {
        return new XdrConfigSettingContractHistoricalDataV0($xdr->readInteger64());
    }

    /**
     * @return int
     */
    public function getFeeHistorical1KB(): int
    {
        return $this->feeHistorical1KB;
    }

    /**
     * @param int $feeHistorical1KB
     */
    public function setFeeHistorical1KB(int $feeHistorical1KB): void
    {
        $this->feeHistorical1KB = $feeHistorical1KB;
    }
}