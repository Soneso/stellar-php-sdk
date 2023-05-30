<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrConfigSettingContractLedgerCostV0
{

    public int $ledgerMaxReadLedgerEntries;
    public int $ledgerMaxReadBytes;
    public int $ledgerMaxWriteLedgerEntries;
    public int $ledgerMaxWriteBytes;
    public int $txMaxReadLedgerEntries;
    public int $txMaxReadBytes;
    public int $txMaxWriteLedgerEntries;
    public int $txMaxWriteBytes;
    public int $feeReadLedgerEntry;
    public int $feeWriteLedgerEntry;
    public int $feeRead1KB;
    public int $feeWrite1KB;
    public int $bucketListSizeBytes;
    public int $bucketListFeeRateLow;
    public int $bucketListFeeRateHigh;
    public int $bucketListGrowthFactor;

    /**
     * @param int $ledgerMaxReadLedgerEntries Maximum number of ledger entry read operations per ledger
     * @param int $ledgerMaxReadBytes Maximum number of bytes that can be read per ledger
     * @param int $ledgerMaxWriteLedgerEntries Maximum number of ledger entry write operations per ledger
     * @param int $ledgerMaxWriteBytes Maximum number of bytes that can be written per ledger
     * @param int $txMaxReadLedgerEntries Maximum number of ledger entry read operations per transaction
     * @param int $txMaxReadBytes Maximum number of bytes that can be read per transaction
     * @param int $txMaxWriteLedgerEntries Maximum number of ledger entry write operations per transaction
     * @param int $txMaxWriteBytes Maximum number of bytes that can be written per transaction
     * @param int $feeReadLedgerEntry Fee per ledger entry read
     * @param int $feeWriteLedgerEntry Fee per ledger entry write
     * @param int $feeRead1KB Fee for reading 1KB
     * @param int $feeWrite1KB Fee for writing 1KB
     * @param int $bucketListSizeBytes Bucket list fees grow slowly up to that size
     * @param int $bucketListFeeRateLow Fee rate in stroops when the bucket list is empty
     * @param int $bucketListFeeRateHigh  Fee rate in stroops when the bucket list reached bucketListSizeBytes
     * @param int $bucketListGrowthFactor Rate multiplier for any additional data past the first bucketListSizeBytes
     */
    public function __construct(int $ledgerMaxReadLedgerEntries, int $ledgerMaxReadBytes,
                                int $ledgerMaxWriteLedgerEntries, int $ledgerMaxWriteBytes,
                                int $txMaxReadLedgerEntries, int $txMaxReadBytes,
                                int $txMaxWriteLedgerEntries, int $txMaxWriteBytes,
                                int $feeReadLedgerEntry, int $feeWriteLedgerEntry,
                                int $feeRead1KB, int $feeWrite1KB, int $bucketListSizeBytes,
                                int $bucketListFeeRateLow, int $bucketListFeeRateHigh, int $bucketListGrowthFactor)
    {
        $this->ledgerMaxReadLedgerEntries = $ledgerMaxReadLedgerEntries;
        $this->ledgerMaxReadBytes = $ledgerMaxReadBytes;
        $this->ledgerMaxWriteLedgerEntries = $ledgerMaxWriteLedgerEntries;
        $this->ledgerMaxWriteBytes = $ledgerMaxWriteBytes;
        $this->txMaxReadLedgerEntries = $txMaxReadLedgerEntries;
        $this->txMaxReadBytes = $txMaxReadBytes;
        $this->txMaxWriteLedgerEntries = $txMaxWriteLedgerEntries;
        $this->txMaxWriteBytes = $txMaxWriteBytes;
        $this->feeReadLedgerEntry = $feeReadLedgerEntry;
        $this->feeWriteLedgerEntry = $feeWriteLedgerEntry;
        $this->feeRead1KB = $feeRead1KB;
        $this->feeWrite1KB = $feeWrite1KB;
        $this->bucketListSizeBytes = $bucketListSizeBytes;
        $this->bucketListFeeRateLow = $bucketListFeeRateLow;
        $this->bucketListFeeRateHigh = $bucketListFeeRateHigh;
        $this->bucketListGrowthFactor = $bucketListGrowthFactor;
    }


    public function encode(): string {
        $bytes = XdrEncoder::unsignedInteger32($this->ledgerMaxReadLedgerEntries);
        $bytes .= XdrEncoder::unsignedInteger32($this->ledgerMaxReadBytes);
        $bytes .= XdrEncoder::unsignedInteger32($this->ledgerMaxWriteLedgerEntries);
        $bytes .= XdrEncoder::unsignedInteger32($this->ledgerMaxWriteBytes);
        $bytes .= XdrEncoder::unsignedInteger32($this->txMaxReadLedgerEntries);
        $bytes .= XdrEncoder::unsignedInteger32($this->txMaxReadBytes);
        $bytes .= XdrEncoder::unsignedInteger32($this->txMaxWriteLedgerEntries);
        $bytes .= XdrEncoder::unsignedInteger32($this->txMaxWriteBytes);
        $bytes .= XdrEncoder::integer64($this->feeReadLedgerEntry);
        $bytes .= XdrEncoder::integer64($this->feeWriteLedgerEntry);
        $bytes .= XdrEncoder::integer64($this->feeRead1KB);
        $bytes .= XdrEncoder::integer64($this->feeWrite1KB);
        $bytes .= XdrEncoder::integer64($this->bucketListSizeBytes);
        $bytes .= XdrEncoder::integer64($this->bucketListFeeRateLow);
        $bytes .= XdrEncoder::integer64($this->bucketListFeeRateHigh);
        $bytes .= XdrEncoder::unsignedInteger32($this->bucketListGrowthFactor);

        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrConfigSettingContractLedgerCostV0 {
        $ledgerMaxReadLedgerEntries = $xdr->readUnsignedInteger32();
        $ledgerMaxReadBytes = $xdr->readUnsignedInteger32();
        $ledgerMaxWriteLedgerEntries = $xdr->readUnsignedInteger32();
        $ledgerMaxWriteBytes = $xdr->readUnsignedInteger32();
        $txMaxReadLedgerEntries = $xdr->readUnsignedInteger32();
        $txMaxReadBytes = $xdr->readUnsignedInteger32();
        $txMaxWriteLedgerEntries = $xdr->readUnsignedInteger32();
        $txMaxWriteBytes = $xdr->readUnsignedInteger32();

        $feeReadLedgerEntry = $xdr->readInteger64();
        $feeWriteLedgerEntry = $xdr->readInteger64();
        $feeRead1KB = $xdr->readInteger64();
        $feeWrite1KB = $xdr->readInteger64();
        $bucketListSizeBytes = $xdr->readInteger64();
        $bucketListFeeRateLow = $xdr->readInteger64();
        $bucketListFeeRateHigh = $xdr->readInteger64();

        $bucketListGrowthFactor = $xdr->readUnsignedInteger32();

        return new XdrConfigSettingContractLedgerCostV0($ledgerMaxReadLedgerEntries, $ledgerMaxReadBytes,
                                $ledgerMaxWriteLedgerEntries, $ledgerMaxWriteBytes,
                                $txMaxReadLedgerEntries, $txMaxReadBytes,
                                $txMaxWriteLedgerEntries, $txMaxWriteBytes,
                                $feeReadLedgerEntry, $feeWriteLedgerEntry,
                                $feeRead1KB, $feeWrite1KB, $bucketListSizeBytes,
                                $bucketListFeeRateLow, $bucketListFeeRateHigh, $bucketListGrowthFactor);
    }

    /**
     * @return int
     */
    public function getLedgerMaxReadLedgerEntries(): int
    {
        return $this->ledgerMaxReadLedgerEntries;
    }

    /**
     * @param int $ledgerMaxReadLedgerEntries
     */
    public function setLedgerMaxReadLedgerEntries(int $ledgerMaxReadLedgerEntries): void
    {
        $this->ledgerMaxReadLedgerEntries = $ledgerMaxReadLedgerEntries;
    }

    /**
     * @return int
     */
    public function getLedgerMaxReadBytes(): int
    {
        return $this->ledgerMaxReadBytes;
    }

    /**
     * @param int $ledgerMaxReadBytes
     */
    public function setLedgerMaxReadBytes(int $ledgerMaxReadBytes): void
    {
        $this->ledgerMaxReadBytes = $ledgerMaxReadBytes;
    }

    /**
     * @return int
     */
    public function getLedgerMaxWriteLedgerEntries(): int
    {
        return $this->ledgerMaxWriteLedgerEntries;
    }

    /**
     * @param int $ledgerMaxWriteLedgerEntries
     */
    public function setLedgerMaxWriteLedgerEntries(int $ledgerMaxWriteLedgerEntries): void
    {
        $this->ledgerMaxWriteLedgerEntries = $ledgerMaxWriteLedgerEntries;
    }

    /**
     * @return int
     */
    public function getLedgerMaxWriteBytes(): int
    {
        return $this->ledgerMaxWriteBytes;
    }

    /**
     * @param int $ledgerMaxWriteBytes
     */
    public function setLedgerMaxWriteBytes(int $ledgerMaxWriteBytes): void
    {
        $this->ledgerMaxWriteBytes = $ledgerMaxWriteBytes;
    }

    /**
     * @return int
     */
    public function getTxMaxReadLedgerEntries(): int
    {
        return $this->txMaxReadLedgerEntries;
    }

    /**
     * @param int $txMaxReadLedgerEntries
     */
    public function setTxMaxReadLedgerEntries(int $txMaxReadLedgerEntries): void
    {
        $this->txMaxReadLedgerEntries = $txMaxReadLedgerEntries;
    }

    /**
     * @return int
     */
    public function getTxMaxReadBytes(): int
    {
        return $this->txMaxReadBytes;
    }

    /**
     * @param int $txMaxReadBytes
     */
    public function setTxMaxReadBytes(int $txMaxReadBytes): void
    {
        $this->txMaxReadBytes = $txMaxReadBytes;
    }

    /**
     * @return int
     */
    public function getTxMaxWriteLedgerEntries(): int
    {
        return $this->txMaxWriteLedgerEntries;
    }

    /**
     * @param int $txMaxWriteLedgerEntries
     */
    public function setTxMaxWriteLedgerEntries(int $txMaxWriteLedgerEntries): void
    {
        $this->txMaxWriteLedgerEntries = $txMaxWriteLedgerEntries;
    }

    /**
     * @return int
     */
    public function getTxMaxWriteBytes(): int
    {
        return $this->txMaxWriteBytes;
    }

    /**
     * @param int $txMaxWriteBytes
     */
    public function setTxMaxWriteBytes(int $txMaxWriteBytes): void
    {
        $this->txMaxWriteBytes = $txMaxWriteBytes;
    }

    /**
     * @return int
     */
    public function getFeeReadLedgerEntry(): int
    {
        return $this->feeReadLedgerEntry;
    }

    /**
     * @param int $feeReadLedgerEntry
     */
    public function setFeeReadLedgerEntry(int $feeReadLedgerEntry): void
    {
        $this->feeReadLedgerEntry = $feeReadLedgerEntry;
    }

    /**
     * @return int
     */
    public function getFeeWriteLedgerEntry(): int
    {
        return $this->feeWriteLedgerEntry;
    }

    /**
     * @param int $feeWriteLedgerEntry
     */
    public function setFeeWriteLedgerEntry(int $feeWriteLedgerEntry): void
    {
        $this->feeWriteLedgerEntry = $feeWriteLedgerEntry;
    }

    /**
     * @return int
     */
    public function getFeeRead1KB(): int
    {
        return $this->feeRead1KB;
    }

    /**
     * @param int $feeRead1KB
     */
    public function setFeeRead1KB(int $feeRead1KB): void
    {
        $this->feeRead1KB = $feeRead1KB;
    }

    /**
     * @return int
     */
    public function getFeeWrite1KB(): int
    {
        return $this->feeWrite1KB;
    }

    /**
     * @param int $feeWrite1KB
     */
    public function setFeeWrite1KB(int $feeWrite1KB): void
    {
        $this->feeWrite1KB = $feeWrite1KB;
    }

    /**
     * @return int
     */
    public function getBucketListSizeBytes(): int
    {
        return $this->bucketListSizeBytes;
    }

    /**
     * @param int $bucketListSizeBytes
     */
    public function setBucketListSizeBytes(int $bucketListSizeBytes): void
    {
        $this->bucketListSizeBytes = $bucketListSizeBytes;
    }

    /**
     * @return int
     */
    public function getBucketListFeeRateLow(): int
    {
        return $this->bucketListFeeRateLow;
    }

    /**
     * @param int $bucketListFeeRateLow
     */
    public function setBucketListFeeRateLow(int $bucketListFeeRateLow): void
    {
        $this->bucketListFeeRateLow = $bucketListFeeRateLow;
    }

    /**
     * @return int
     */
    public function getBucketListFeeRateHigh(): int
    {
        return $this->bucketListFeeRateHigh;
    }

    /**
     * @param int $bucketListFeeRateHigh
     */
    public function setBucketListFeeRateHigh(int $bucketListFeeRateHigh): void
    {
        $this->bucketListFeeRateHigh = $bucketListFeeRateHigh;
    }

    /**
     * @return int
     */
    public function getBucketListGrowthFactor(): int
    {
        return $this->bucketListGrowthFactor;
    }

    /**
     * @param int $bucketListGrowthFactor
     */
    public function setBucketListGrowthFactor(int $bucketListGrowthFactor): void
    {
        $this->bucketListGrowthFactor = $bucketListGrowthFactor;
    }
}