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
     * @var array<string> $supportedSeps List of SEP numbers that this contract claims to support.
     * Extracted from meta entries with key "sep" as defined in SEP-47.
     * SEP-47: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0047.md
     */
    public array $supportedSeps;

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

        // Parse supported SEPs from meta entries (SEP-47)
        // Meta entries with key "sep" contain comma-separated SEP numbers
        $this->supportedSeps = $this->parseSupportedSeps($metaEntries);
    }

    /**
     * Parse supported SEP numbers from meta entries.
     * According to SEP-47, contracts can indicate which SEPs they support
     * through meta entries with key "sep" containing comma-separated SEP numbers.
     * Multiple "sep" entries are concatenated.
     *
     * @param array<string,string> $metaEntries Contract Meta Entries
     * @return array<string> List of SEP numbers (e.g., ["41", "40"])
     */
    private function parseSupportedSeps(array $metaEntries): array
    {
        $sepNumbers = [];

        // Look for all meta entries with key "sep"
        foreach ($metaEntries as $key => $value) {
            if ($key === 'sep') {
                // Parse comma-separated SEP numbers
                $seps = array_map('trim', explode(',', $value));
                foreach ($seps as $sep) {
                    if (!empty($sep) && !in_array($sep, $sepNumbers)) {
                        $sepNumbers[] = $sep;
                    }
                }
            }
        }

        return $sepNumbers;
    }


}