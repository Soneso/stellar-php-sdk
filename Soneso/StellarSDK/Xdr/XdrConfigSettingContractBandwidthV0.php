<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrConfigSettingContractBandwidthV0
{
    public int $ledgerMaxTxsSizeBytes;
    public int $txMaxSizeBytes;
    public int $feeTxSize1KB;

    /**
     * @param int $ledgerMaxTxsSizeBytes
     * @param int $txMaxSizeBytes
     * @param int $feeTxSize1KB
     */
    public function __construct(int $ledgerMaxTxsSizeBytes, int $txMaxSizeBytes, int $feeTxSize1KB)
    {
        $this->ledgerMaxTxsSizeBytes = $ledgerMaxTxsSizeBytes;
        $this->txMaxSizeBytes = $txMaxSizeBytes;
        $this->feeTxSize1KB = $feeTxSize1KB;
    }


    public function encode(): string {
        $bytes = XdrEncoder::unsignedInteger32($this->ledgerMaxTxsSizeBytes);
        $bytes .= XdrEncoder::unsignedInteger32($this->txMaxSizeBytes);
        $bytes .= XdrEncoder::integer64($this->feeTxSize1KB);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrConfigSettingContractBandwidthV0 {
        $ledgerMaxTxsSizeBytes = $xdr->readUnsignedInteger32();
        $txMaxSizeBytes = $xdr->readUnsignedInteger32();
        $feeTxSize1KB = $xdr->readInteger64();

        return new XdrConfigSettingContractBandwidthV0($ledgerMaxTxsSizeBytes, $txMaxSizeBytes, $feeTxSize1KB);
    }

    /**
     * @return int
     */
    public function getLedgerMaxTxsSizeBytes(): int
    {
        return $this->ledgerMaxTxsSizeBytes;
    }

    /**
     * @param int $ledgerMaxTxsSizeBytes
     */
    public function setLedgerMaxTxsSizeBytes(int $ledgerMaxTxsSizeBytes): void
    {
        $this->ledgerMaxTxsSizeBytes = $ledgerMaxTxsSizeBytes;
    }

    /**
     * @return int
     */
    public function getTxMaxSizeBytes(): int
    {
        return $this->txMaxSizeBytes;
    }

    /**
     * @param int $txMaxSizeBytes
     */
    public function setTxMaxSizeBytes(int $txMaxSizeBytes): void
    {
        $this->txMaxSizeBytes = $txMaxSizeBytes;
    }

    /**
     * @return int
     */
    public function getFeeTxSize1KB(): int
    {
        return $this->feeTxSize1KB;
    }

    /**
     * @param int $feeTxSize1KB
     */
    public function setFeeTxSize1KB(int $feeTxSize1KB): void
    {
        $this->feeTxSize1KB = $feeTxSize1KB;
    }

}