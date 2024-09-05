<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban;

use Soneso\StellarSDK\Xdr\XdrSCSpecEntry;

/**
 * Stores information parsed from a soroban contract byte code such as
 * Environment Meta, Contract Spec Entries and Contract Meta Entries.
 * See also: https://developers.stellar.org/docs/tools/sdks/build-your-own
 */
class SorobanContractInfo
{
    /**
     * @var int Environment interface number from Environment Meta.
     */
    public int $envInterfaceVersion;

    /**
     * @var array<XdrSCSpecEntry> $specEntries Contract Spec Entries.
     * There is a SCSpecEntry for every function, struct, and union exported by the contract.
     */
    public array $specEntries;

    /**
     * @var array<string,string> $metaEntries Contract Meta Entries. Key => Value pairs.
     * Contracts may store any metadata in the entries that can be used by applications
     * and tooling off-network.
     */
    public array $metaEntries;

    /**
     * @param int $envInterfaceVersion Environment interface number from Environment Meta.
     * @param array<XdrSCSpecEntry> $specEntries Contract Spec Entries.
     * @param array<string,string> $metaEntries Contract Meta Entries. Key => Value pairs.
     */
    public function __construct(
        int $envInterfaceVersion,
        array $specEntries,
        array $metaEntries,
    )
    {
        $this->envInterfaceVersion = $envInterfaceVersion;
        $this->specEntries = $specEntries;
        $this->metaEntries = $metaEntries;
    }


}