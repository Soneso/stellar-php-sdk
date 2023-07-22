<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrConfigSettingContractExecutionLanesV0
{
    public int $ledgerMaxTxCount; // uint32

    /**
     * @param int $ledgerMaxTxCount
     */
    public function __construct(int $ledgerMaxTxCount)
    {
        $this->ledgerMaxTxCount = $ledgerMaxTxCount;
    }

    public function encode(): string {
        return XdrEncoder::unsignedInteger32($this->ledgerMaxTxCount);
    }

    public static function decode(XdrBuffer $xdr) : XdrConfigSettingContractExecutionLanesV0 {
        $ledgerMaxTxCount = $xdr->readUnsignedInteger32();
        return new XdrConfigSettingContractExecutionLanesV0($ledgerMaxTxCount);
    }

    /**
     * @return int
     */
    public function getLedgerMaxTxCount(): int
    {
        return $this->ledgerMaxTxCount;
    }

    /**
     * @param int $ledgerMaxTxCount
     */
    public function setLedgerMaxTxCount(int $ledgerMaxTxCount): void
    {
        $this->ledgerMaxTxCount = $ledgerMaxTxCount;
    }

}