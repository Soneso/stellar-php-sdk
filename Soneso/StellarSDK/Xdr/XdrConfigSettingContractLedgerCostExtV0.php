<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

/**
 * Ledger access settings for contracts.
 */
class XdrConfigSettingContractLedgerCostExtV0
{
    /**
     * @var int $txMaxFootprintEntries (uint32) Maximum number of RO+RW entries in the transaction footprint.
     */
    public int $txMaxFootprintEntries; // uint32

    /**
     * @var int $feeWrite1KB (int64) Fee per 1 KB of data written to the ledger.
     * Unlike the rent fee, this is a flat fee that is charged for any ledger
     * write, independent of the type of the entry being written.
     */
    public int $feeWrite1KB; // int64

    /**
     * @param int $txMaxFootprintEntries (uint32) Maximum number of RO+RW entries in the transaction footprint.
     * @param int $feeWrite1KB (int64) Fee per 1 KB of data written to the ledger.
     *  Unlike the rent fee, this is a flat fee that is charged for any ledger
     *  write, independent of the type of the entry being written.
     */
    public function __construct(int $txMaxFootprintEntries, int $feeWrite1KB)
    {
        $this->txMaxFootprintEntries = $txMaxFootprintEntries;
        $this->feeWrite1KB = $feeWrite1KB;
    }


    public function encode(): string {
        $bytes = XdrEncoder::unsignedInteger32($this->txMaxFootprintEntries);
        $bytes .= XdrEncoder::integer64($this->feeWrite1KB);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrConfigSettingContractLedgerCostExtV0 {
        $txMaxFootprintEntries = $xdr->readUnsignedInteger32();
        $feeWrite1KB = $xdr->readInteger64();
        return new XdrConfigSettingContractLedgerCostExtV0($txMaxFootprintEntries, $feeWrite1KB);
    }

    /**
     * @return int
     */
    public function getTxMaxFootprintEntries(): int
    {
        return $this->txMaxFootprintEntries;
    }

    /**
     * @param int $txMaxFootprintEntries
     */
    public function setTxMaxFootprintEntries(int $txMaxFootprintEntries): void
    {
        $this->txMaxFootprintEntries = $txMaxFootprintEntries;
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

}