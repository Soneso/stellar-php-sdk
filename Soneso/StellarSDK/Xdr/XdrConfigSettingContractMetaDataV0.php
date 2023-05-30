<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrConfigSettingContractMetaDataV0
{
    public int $txMaxExtendedMetaDataSizeBytes;
    public int $feeExtendedMetaData1KB;

    /**
     * @param int $txMaxExtendedMetaDataSizeBytes Maximum size of extended meta data produced by a transaction
     * @param int $feeExtendedMetaData1KB Fee for generating 1KB of extended meta data
     */
    public function __construct(int $txMaxExtendedMetaDataSizeBytes, int $feeExtendedMetaData1KB)
    {
        $this->txMaxExtendedMetaDataSizeBytes = $txMaxExtendedMetaDataSizeBytes;
        $this->feeExtendedMetaData1KB = $feeExtendedMetaData1KB;
    }


    public function encode(): string {
        $bytes = XdrEncoder::unsignedInteger32($this->txMaxExtendedMetaDataSizeBytes);
        $bytes .= XdrEncoder::integer64($this->feeExtendedMetaData1KB);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrConfigSettingContractMetaDataV0 {
        $txMaxExtendedMetaDataSizeBytes = $xdr->readUnsignedInteger32();
        $feeExtendedMetaData1KB = $xdr->readInteger64();

        return new XdrConfigSettingContractMetaDataV0($txMaxExtendedMetaDataSizeBytes, $feeExtendedMetaData1KB);
    }

    /**
     * @return int
     */
    public function getTxMaxExtendedMetaDataSizeBytes(): int
    {
        return $this->txMaxExtendedMetaDataSizeBytes;
    }

    /**
     * @param int $txMaxExtendedMetaDataSizeBytes
     */
    public function setTxMaxExtendedMetaDataSizeBytes(int $txMaxExtendedMetaDataSizeBytes): void
    {
        $this->txMaxExtendedMetaDataSizeBytes = $txMaxExtendedMetaDataSizeBytes;
    }

    /**
     * @return int
     */
    public function getFeeExtendedMetaData1KB(): int
    {
        return $this->feeExtendedMetaData1KB;
    }

    /**
     * @param int $feeExtendedMetaData1KB
     */
    public function setFeeExtendedMetaData1KB(int $feeExtendedMetaData1KB): void
    {
        $this->feeExtendedMetaData1KB = $feeExtendedMetaData1KB;
    }
}