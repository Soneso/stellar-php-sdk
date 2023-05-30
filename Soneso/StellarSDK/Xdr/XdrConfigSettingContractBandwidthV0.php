<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrConfigSettingContractBandwidthV0
{
    public int $ledgerMaxPropagateSizeBytes;
    public int $txMaxSizeBytes;
    public int $feePropagateData1KB;

    /**
     * @param int $ledgerMaxPropagateSizeBytes Maximum size in bytes to propagate per ledger
     * @param int $txMaxSizeBytes Maximum size in bytes for a transaction
     * @param int $feePropagateData1KB Fee for propagating 1KB of data
     */
    public function __construct(int $ledgerMaxPropagateSizeBytes, int $txMaxSizeBytes, int $feePropagateData1KB)
    {
        $this->ledgerMaxPropagateSizeBytes = $ledgerMaxPropagateSizeBytes;
        $this->txMaxSizeBytes = $txMaxSizeBytes;
        $this->feePropagateData1KB = $feePropagateData1KB;
    }


    public function encode(): string {
        $bytes = XdrEncoder::unsignedInteger32($this->ledgerMaxPropagateSizeBytes);
        $bytes .= XdrEncoder::unsignedInteger32($this->txMaxSizeBytes);
        $bytes .= XdrEncoder::integer64($this->feePropagateData1KB);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrConfigSettingContractBandwidthV0 {
        $ledgerMaxPropagateSizeBytes = $xdr->readUnsignedInteger32();
        $txMaxSizeBytes = $xdr->readUnsignedInteger32();
        $feePropagateData1KB = $xdr->readInteger64();

        return new XdrConfigSettingContractBandwidthV0($ledgerMaxPropagateSizeBytes, $txMaxSizeBytes, $feePropagateData1KB);
    }

    /**
     * @return int
     */
    public function getLedgerMaxPropagateSizeBytes(): int
    {
        return $this->ledgerMaxPropagateSizeBytes;
    }

    /**
     * @param int $ledgerMaxPropagateSizeBytes
     */
    public function setLedgerMaxPropagateSizeBytes(int $ledgerMaxPropagateSizeBytes): void
    {
        $this->ledgerMaxPropagateSizeBytes = $ledgerMaxPropagateSizeBytes;
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
    public function getFeePropagateData1KB(): int
    {
        return $this->feePropagateData1KB;
    }

    /**
     * @param int $feePropagateData1KB
     */
    public function setFeePropagateData1KB(int $feePropagateData1KB): void
    {
        $this->feePropagateData1KB = $feePropagateData1KB;
    }
}