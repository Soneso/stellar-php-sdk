<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

/**
 * Settings for running the contract transactions in parallel.
 */
class XdrConfigSettingContractParallelComputeV0
{
    /**
     * @var int $ledgerMaxDependentTxClusters (uint32) Maximum number of clusters with dependent transactions allowed in a
     * stage of parallel tx set component. This effectively sets the lower bound on the number of physical threads
     * necessary to effectively apply transaction sets in parallel.
     */
    public int $ledgerMaxDependentTxClusters; // uint32

    /**
     * @param int $ledgerMaxDependentTxClusters (uint32) Maximum number of clusters with dependent transactions allowed in a
     *  stage of parallel tx set component. This effectively sets the lower bound on the number of physical threads
     *  necessary to effectively apply transaction sets in parallel.
     */
    public function __construct(int $ledgerMaxDependentTxClusters)
    {
        $this->ledgerMaxDependentTxClusters = $ledgerMaxDependentTxClusters;
    }

    public function encode(): string {
        return XdrEncoder::unsignedInteger32($this->ledgerMaxDependentTxClusters);
    }

    public static function decode(XdrBuffer $xdr) : XdrConfigSettingContractParallelComputeV0 {
        $ledgerMaxDependentTxClusters = $xdr->readUnsignedInteger32();
        return new XdrConfigSettingContractParallelComputeV0($ledgerMaxDependentTxClusters);
    }

    /**
     * @return int
     */
    public function getLedgerMaxDependentTxClusters(): int
    {
        return $this->ledgerMaxDependentTxClusters;
    }

    /**
     * @param int $ledgerMaxDependentTxClusters
     */
    public function setLedgerMaxDependentTxClusters(int $ledgerMaxDependentTxClusters): void
    {
        $this->ledgerMaxDependentTxClusters = $ledgerMaxDependentTxClusters;
    }
    
}