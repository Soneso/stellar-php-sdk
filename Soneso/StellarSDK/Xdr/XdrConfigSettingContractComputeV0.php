<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrConfigSettingContractComputeV0
{
    // Maximum instructions per ledger
    public int $ledgerMaxInstructions;
    // Maximum instructions per transaction
    public int $txMaxInstructions;
    // Cost of 10000 instructions
    public int $feeRatePerInstructionsIncrement;
    // Memory limit per transaction. Unlike instructions, there is no fee
    // for memory, just the limit.
    public int $txMemoryLimit;

    /**
     * @param int $ledgerMaxInstructions Maximum instructions per ledger
     * @param int $txMaxInstructions Maximum instructions per transaction
     * @param int $feeRatePerInstructionsIncrement Cost of 10000 instructions
     * @param int $txMemoryLimit Memory limit per transaction.
     */
    public function __construct(int $ledgerMaxInstructions, int $txMaxInstructions, int $feeRatePerInstructionsIncrement, int $txMemoryLimit)
    {
        $this->ledgerMaxInstructions = $ledgerMaxInstructions;
        $this->txMaxInstructions = $txMaxInstructions;
        $this->feeRatePerInstructionsIncrement = $feeRatePerInstructionsIncrement;
        $this->txMemoryLimit = $txMemoryLimit;
    }


    public function encode(): string {
        $bytes = XdrEncoder::integer64($this->ledgerMaxInstructions);
        $bytes .= XdrEncoder::integer64($this->txMaxInstructions);
        $bytes .= XdrEncoder::integer64($this->feeRatePerInstructionsIncrement);
        $bytes .= XdrEncoder::unsignedInteger32($this->txMemoryLimit);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrConfigSettingContractComputeV0 {
        $ledgerMaxInstructions = $xdr->readInteger64();
        $txMaxInstructions = $xdr->readInteger64();
        $feeRatePerInstructionsIncrement = $xdr->readInteger64();
        $txMemoryLimit = $xdr->readUnsignedInteger32();

        return new XdrConfigSettingContractComputeV0($ledgerMaxInstructions, $txMaxInstructions, $feeRatePerInstructionsIncrement, $txMemoryLimit);
    }

    /**
     * @return int
     */
    public function getLedgerMaxInstructions(): int
    {
        return $this->ledgerMaxInstructions;
    }

    /**
     * @param int $ledgerMaxInstructions
     */
    public function setLedgerMaxInstructions(int $ledgerMaxInstructions): void
    {
        $this->ledgerMaxInstructions = $ledgerMaxInstructions;
    }

    /**
     * @return int
     */
    public function getTxMaxInstructions(): int
    {
        return $this->txMaxInstructions;
    }

    /**
     * @param int $txMaxInstructions
     */
    public function setTxMaxInstructions(int $txMaxInstructions): void
    {
        $this->txMaxInstructions = $txMaxInstructions;
    }

    /**
     * @return int
     */
    public function getFeeRatePerInstructionsIncrement(): int
    {
        return $this->feeRatePerInstructionsIncrement;
    }

    /**
     * @param int $feeRatePerInstructionsIncrement
     */
    public function setFeeRatePerInstructionsIncrement(int $feeRatePerInstructionsIncrement): void
    {
        $this->feeRatePerInstructionsIncrement = $feeRatePerInstructionsIncrement;
    }

    /**
     * @return int
     */
    public function getTxMemoryLimit(): int
    {
        return $this->txMemoryLimit;
    }

    /**
     * @param int $txMemoryLimit
     */
    public function setTxMemoryLimit(int $txMemoryLimit): void
    {
        $this->txMemoryLimit = $txMemoryLimit;
    }
}