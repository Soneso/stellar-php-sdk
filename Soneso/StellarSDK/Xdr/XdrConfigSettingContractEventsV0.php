<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

/**
 * Contract event-related settings.
 */
class XdrConfigSettingContractEventsV0
{
    /**
     * @var int $txMaxContractEventsSizeBytes (uint32) Maximum size of events that a contract call can emit.
     */
    public int $txMaxContractEventsSizeBytes;

    /**
     * @var int $feeContractEvents1KB (int64) Fee for generating 1KB of contract events.
     */
    public int $feeContractEvents1KB;

    /**
     * @param int $txMaxContractEventsSizeBytes (uint32) Maximum size of events that a contract call can emit.
     * @param int $feeContractEvents1KB (int64) Fee for generating 1KB of contract events.
     */
    public function __construct(int $txMaxContractEventsSizeBytes, int $feeContractEvents1KB)
    {
        $this->txMaxContractEventsSizeBytes = $txMaxContractEventsSizeBytes;
        $this->feeContractEvents1KB = $feeContractEvents1KB;
    }


    public function encode(): string {
        $bytes = XdrEncoder::unsignedInteger32($this->txMaxContractEventsSizeBytes);
        $bytes .= XdrEncoder::integer64($this->feeContractEvents1KB);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrConfigSettingContractEventsV0 {
        $txMaxContractEventsSizeBytes = $xdr->readUnsignedInteger32();
        $feeContractEvents1KB = $xdr->readInteger64();

        return new XdrConfigSettingContractEventsV0($txMaxContractEventsSizeBytes, $feeContractEvents1KB);
    }

    /**
     * @return int
     */
    public function getTxMaxContractEventsSizeBytes(): int
    {
        return $this->txMaxContractEventsSizeBytes;
    }

    /**
     * @param int $txMaxContractEventsSizeBytes
     */
    public function setTxMaxContractEventsSizeBytes(int $txMaxContractEventsSizeBytes): void
    {
        $this->txMaxContractEventsSizeBytes = $txMaxContractEventsSizeBytes;
    }

    /**
     * @return int
     */
    public function getFeeContractEvents1KB(): int
    {
        return $this->feeContractEvents1KB;
    }

    /**
     * @param int $feeContractEvents1KB
     */
    public function setFeeContractEvents1KB(int $feeContractEvents1KB): void
    {
        $this->feeContractEvents1KB = $feeContractEvents1KB;
    }

}