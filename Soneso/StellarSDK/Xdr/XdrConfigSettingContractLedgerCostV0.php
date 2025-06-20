<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrConfigSettingContractLedgerCostV0
{

    /**
     * @var int $ledgerMaxDiskReadLedgerEntries (uint32) Maximum number of disk entry read operations per ledger
     */
    public int $ledgerMaxDiskReadLedgerEntries;

    /**
     * @var int $ledgerMaxDiskReadBytes (uint32) Maximum number of bytes of disk reads that can be performed per ledger
     */
    public int $ledgerMaxDiskReadBytes;

    /**
     * @var int $ledgerMaxWriteLedgerEntries (uint32) Maximum number of ledger entry write operations per ledger
     */
    public int $ledgerMaxWriteLedgerEntries;

    /**
     * @var int $ledgerMaxWriteBytes (uint32) Maximum number of bytes that can be written per ledger
     */
    public int $ledgerMaxWriteBytes;


    /**
     * @var int $txMaxDiskReadEntries (uint32) Maximum number of disk entry read operations per transaction
     */
    public int $txMaxDiskReadEntries;


    /**
     * @var int $txMaxDiskReadBytes (uint32) Maximum number of bytes of disk reads that can be performed per transaction
     */
    public int $txMaxDiskReadBytes;

    /**
     * @var int $txMaxWriteLedgerEntries (uint32) Maximum number of ledger entry write operations per transaction
     */
    public int $txMaxWriteLedgerEntries;

    /**
     * @var int $txMaxWriteBytes (uint32) Maximum number of bytes that can be written per transaction
     */
    public int $txMaxWriteBytes;

    /**
     * @var int $feeDiskReadLedgerEntry (int64) Fee per disk ledger entry read
     */
    public int $feeDiskReadLedgerEntry;

    /**
     * @var int $feeWriteLedgerEntry (int64) Fee per ledger entry write
     */
    public int $feeWriteLedgerEntry;

    /**
     * @var int $feeDiskRead1KB (int64) Fee for reading 1KB disk
     */
    public int $feeDiskRead1KB;

    /**
     * @var int $sorobanStateTargetSizeBytes (int64) Rent fee grows linearly until soroban state reaches this size
     */
    public int $sorobanStateTargetSizeBytes;

    /**
     * @var int $rentFee1KBSorobanStateSizeLow (int64) Fee per 1KB rent when the soroban state is empty
     */
    public int $rentFee1KBSorobanStateSizeLow;

    /**
     * @var int $rentFee1KBSorobanStateSizeHigh (int64) Fee per 1KB rent when the soroban state has reached `sorobanStateTargetSizeBytes`
     */
    public int $rentFee1KBSorobanStateSizeHigh;

    /**
     * @var int $sorobanStateRentFeeGrowthFactor (uint32) Rent fee multiplier for any additional data past the first `sorobanStateTargetSizeBytes`
     */
    public int $sorobanStateRentFeeGrowthFactor;

    /**
     * @param int $ledgerMaxDiskReadLedgerEntries (uint32) Maximum number of disk entry read operations per ledger
     * @param int $ledgerMaxDiskReadBytes (uint32) Maximum number of bytes of disk reads that can be performed per ledger
     * @param int $ledgerMaxWriteLedgerEntries (uint32) Maximum number of ledger entry write operations per ledger
     * @param int $ledgerMaxWriteBytes (uint32) Maximum number of bytes that can be written per ledger
     * @param int $txMaxDiskReadEntries (uint32) Maximum number of disk entry read operations per transaction
     * @param int $txMaxDiskReadBytes (uint32) Maximum number of bytes of disk reads that can be performed per transaction
     * @param int $txMaxWriteLedgerEntries (uint32) Maximum number of ledger entry write operations per transaction
     * @param int $txMaxWriteBytes (uint32) Maximum number of bytes that can be written per transaction
     * @param int $feeDiskReadLedgerEntry (int64) Fee per disk ledger entry read
     * @param int $feeWriteLedgerEntry (int64) Fee per ledger entry write
     * @param int $feeDiskRead1KB (int64) Fee for reading 1KB disk
     * @param int $sorobanStateTargetSizeBytes (int64) Rent fee grows linearly until soroban state reaches this size
     * @param int $rentFee1KBSorobanStateSizeLow (int64) Fee per 1KB rent when the soroban state is empty
     * @param int $rentFee1KBSorobanStateSizeHigh (int64) Fee per 1KB rent when the soroban state has reached `sorobanStateTargetSizeBytes`
     * @param int $sorobanStateRentFeeGrowthFactor (uint32) Rent fee multiplier for any additional data past the first `sorobanStateTargetSizeBytes`
     */
    public function __construct(int $ledgerMaxDiskReadLedgerEntries, int $ledgerMaxDiskReadBytes,
                                int $ledgerMaxWriteLedgerEntries, int $ledgerMaxWriteBytes,
                                int $txMaxDiskReadEntries, int $txMaxDiskReadBytes,
                                int $txMaxWriteLedgerEntries, int $txMaxWriteBytes,
                                int $feeDiskReadLedgerEntry, int $feeWriteLedgerEntry,
                                int $feeDiskRead1KB, int $sorobanStateTargetSizeBytes, int $rentFee1KBSorobanStateSizeLow,
                                int $rentFee1KBSorobanStateSizeHigh, int $sorobanStateRentFeeGrowthFactor)
    {
        $this->ledgerMaxDiskReadLedgerEntries = $ledgerMaxDiskReadLedgerEntries;
        $this->ledgerMaxDiskReadBytes = $ledgerMaxDiskReadBytes;
        $this->ledgerMaxWriteLedgerEntries = $ledgerMaxWriteLedgerEntries;
        $this->ledgerMaxWriteBytes = $ledgerMaxWriteBytes;
        $this->txMaxDiskReadEntries = $txMaxDiskReadEntries;
        $this->txMaxDiskReadBytes = $txMaxDiskReadBytes;
        $this->txMaxWriteLedgerEntries = $txMaxWriteLedgerEntries;
        $this->txMaxWriteBytes = $txMaxWriteBytes;
        $this->feeDiskReadLedgerEntry = $feeDiskReadLedgerEntry;
        $this->feeWriteLedgerEntry = $feeWriteLedgerEntry;
        $this->feeDiskRead1KB = $feeDiskRead1KB;
        $this->sorobanStateTargetSizeBytes = $sorobanStateTargetSizeBytes;
        $this->rentFee1KBSorobanStateSizeLow = $rentFee1KBSorobanStateSizeLow;
        $this->rentFee1KBSorobanStateSizeHigh = $rentFee1KBSorobanStateSizeHigh;
        $this->sorobanStateRentFeeGrowthFactor = $sorobanStateRentFeeGrowthFactor;
    }


    public function encode(): string {
        $bytes = XdrEncoder::unsignedInteger32($this->ledgerMaxDiskReadLedgerEntries);
        $bytes .= XdrEncoder::unsignedInteger32($this->ledgerMaxDiskReadBytes);
        $bytes .= XdrEncoder::unsignedInteger32($this->ledgerMaxWriteLedgerEntries);
        $bytes .= XdrEncoder::unsignedInteger32($this->ledgerMaxWriteBytes);
        $bytes .= XdrEncoder::unsignedInteger32($this->txMaxDiskReadEntries);
        $bytes .= XdrEncoder::unsignedInteger32($this->txMaxDiskReadBytes);
        $bytes .= XdrEncoder::unsignedInteger32($this->txMaxWriteLedgerEntries);
        $bytes .= XdrEncoder::unsignedInteger32($this->txMaxWriteBytes);
        $bytes .= XdrEncoder::integer64($this->feeDiskReadLedgerEntry);
        $bytes .= XdrEncoder::integer64($this->feeWriteLedgerEntry);
        $bytes .= XdrEncoder::integer64($this->feeDiskRead1KB);
        $bytes .= XdrEncoder::integer64($this->sorobanStateTargetSizeBytes);
        $bytes .= XdrEncoder::integer64($this->rentFee1KBSorobanStateSizeLow);
        $bytes .= XdrEncoder::integer64($this->rentFee1KBSorobanStateSizeHigh);
        $bytes .= XdrEncoder::unsignedInteger32($this->sorobanStateRentFeeGrowthFactor);

        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrConfigSettingContractLedgerCostV0 {
        $ledgerMaxDiskReadEntries = $xdr->readUnsignedInteger32();
        $ledgerMaxDiskReadBytes = $xdr->readUnsignedInteger32();
        $ledgerMaxWriteLedgerEntries = $xdr->readUnsignedInteger32();
        $ledgerMaxWriteBytes = $xdr->readUnsignedInteger32();
        $txMaxDiskReadEntries = $xdr->readUnsignedInteger32();
        $txMaxDiskReadBytes = $xdr->readUnsignedInteger32();
        $txMaxWriteLedgerEntries = $xdr->readUnsignedInteger32();
        $txMaxWriteBytes = $xdr->readUnsignedInteger32();

        $feeDiskReadLedgerEntry = $xdr->readInteger64();
        $feeWriteLedgerEntry = $xdr->readInteger64();
        $feeDiskRead1KB = $xdr->readInteger64();
        $sorobanStateTargetSizeBytes = $xdr->readInteger64();
        $rentFee1KBSorobanStateSizeLow = $xdr->readInteger64();
        $rentFee1KBSorobanStateSizeHigh = $xdr->readInteger64();

        $sorobanStateRentFeeGrowthFactor = $xdr->readUnsignedInteger32();

        return new XdrConfigSettingContractLedgerCostV0($ledgerMaxDiskReadEntries, $ledgerMaxDiskReadBytes,
                                $ledgerMaxWriteLedgerEntries, $ledgerMaxWriteBytes,
                                $txMaxDiskReadEntries, $txMaxDiskReadBytes,
                                $txMaxWriteLedgerEntries, $txMaxWriteBytes,
                                $feeDiskReadLedgerEntry, $feeWriteLedgerEntry,
                                $feeDiskRead1KB, $sorobanStateTargetSizeBytes,
                                $rentFee1KBSorobanStateSizeLow, $rentFee1KBSorobanStateSizeHigh,
                                $sorobanStateRentFeeGrowthFactor);
    }

    /**
     * @return int
     */
    public function getLedgerMaxDiskReadLedgerEntries(): int
    {
        return $this->ledgerMaxDiskReadLedgerEntries;
    }

    /**
     * @param int $ledgerMaxDiskReadLedgerEntries
     */
    public function setLedgerMaxDiskReadLedgerEntries(int $ledgerMaxDiskReadLedgerEntries): void
    {
        $this->ledgerMaxDiskReadLedgerEntries = $ledgerMaxDiskReadLedgerEntries;
    }

    /**
     * @return int
     */
    public function getLedgerMaxDiskReadBytes(): int
    {
        return $this->ledgerMaxDiskReadBytes;
    }

    /**
     * @param int $ledgerMaxDiskReadBytes
     */
    public function setLedgerMaxDiskReadBytes(int $ledgerMaxDiskReadBytes): void
    {
        $this->ledgerMaxDiskReadBytes = $ledgerMaxDiskReadBytes;
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
    public function getTxMaxDiskReadEntries(): int
    {
        return $this->txMaxDiskReadEntries;
    }

    /**
     * @param int $txMaxDiskReadEntries
     */
    public function setTxMaxDiskReadEntries(int $txMaxDiskReadEntries): void
    {
        $this->txMaxDiskReadEntries = $txMaxDiskReadEntries;
    }

    /**
     * @return int
     */
    public function getTxMaxDiskReadBytes(): int
    {
        return $this->txMaxDiskReadBytes;
    }

    /**
     * @param int $txMaxDiskReadBytes
     */
    public function setTxMaxDiskReadBytes(int $txMaxDiskReadBytes): void
    {
        $this->txMaxDiskReadBytes = $txMaxDiskReadBytes;
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
    public function getFeeDiskReadLedgerEntry(): int
    {
        return $this->feeDiskReadLedgerEntry;
    }

    /**
     * @param int $feeDiskReadLedgerEntry
     */
    public function setFeeDiskReadLedgerEntry(int $feeDiskReadLedgerEntry): void
    {
        $this->feeDiskReadLedgerEntry = $feeDiskReadLedgerEntry;
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
    public function getFeeDiskRead1KB(): int
    {
        return $this->feeDiskRead1KB;
    }

    /**
     * @param int $feeDiskRead1KB
     */
    public function setFeeDiskRead1KB(int $feeDiskRead1KB): void
    {
        $this->feeDiskRead1KB = $feeDiskRead1KB;
    }

    /**
     * @return int
     */
    public function getSorobanStateTargetSizeBytes(): int
    {
        return $this->sorobanStateTargetSizeBytes;
    }

    /**
     * @param int $sorobanStateTargetSizeBytes
     */
    public function setSorobanStateTargetSizeBytes(int $sorobanStateTargetSizeBytes): void
    {
        $this->sorobanStateTargetSizeBytes = $sorobanStateTargetSizeBytes;
    }

    /**
     * @return int
     */
    public function getRentFee1KBSorobanStateSizeLow(): int
    {
        return $this->rentFee1KBSorobanStateSizeLow;
    }

    /**
     * @param int $rentFee1KBSorobanStateSizeLow
     */
    public function setRentFee1KBSorobanStateSizeLow(int $rentFee1KBSorobanStateSizeLow): void
    {
        $this->rentFee1KBSorobanStateSizeLow = $rentFee1KBSorobanStateSizeLow;
    }

    /**
     * @return int
     */
    public function getRentFee1KBSorobanStateSizeHigh(): int
    {
        return $this->rentFee1KBSorobanStateSizeHigh;
    }

    /**
     * @param int $rentFee1KBSorobanStateSizeHigh
     */
    public function setRentFee1KBSorobanStateSizeHigh(int $rentFee1KBSorobanStateSizeHigh): void
    {
        $this->rentFee1KBSorobanStateSizeHigh = $rentFee1KBSorobanStateSizeHigh;
    }

    /**
     * @return int
     */
    public function getSorobanStateRentFeeGrowthFactor(): int
    {
        return $this->sorobanStateRentFeeGrowthFactor;
    }

    /**
     * @param int $sorobanStateRentFeeGrowthFactor
     */
    public function setSorobanStateRentFeeGrowthFactor(int $sorobanStateRentFeeGrowthFactor): void
    {
        $this->sorobanStateRentFeeGrowthFactor = $sorobanStateRentFeeGrowthFactor;
    }
}