<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban;

use Soneso\StellarSDK\Xdr\XdrSCVal;

/**
 * Descriptor used when constructing a delegate tree for an ADDRESS_WITH_DELEGATES entry.
 *
 * Pass an array of SorobanDelegateDescriptor objects to
 * SorobanAuthorizationEntry::withDelegates(). Each descriptor identifies a delegate node by
 * its strkey address and, optionally, an initial signature and nested sub-delegates.
 *
 * The $signature defaults to void; the SDK will sign the node later via sign(forAddress:).
 * The $nestedDelegates array will be sorted by XDR-encoded address bytes during tree construction.
 *
 * @package Soneso\StellarSDK\Soroban
 * @see SorobanAuthorizationEntry::withDelegates()
 * @since Protocol 27 (CAP-71)
 */
class SorobanDelegateDescriptor
{
    /**
     * @var string strkey of the delegate address (G- or C-prefixed)
     */
    public string $address;

    /**
     * @var XdrSCVal|null initial signature; null defaults to void
     */
    public ?XdrSCVal $signature;

    /**
     * @var array<SorobanDelegateDescriptor> nested delegate descriptors
     */
    public array $nestedDelegates;

    /**
     * @param string $address strkey of the delegate address (G- or C-prefixed)
     * @param XdrSCVal|null $signature initial signature value; null for void
     * @param array<SorobanDelegateDescriptor> $nestedDelegates nested delegate descriptors
     */
    public function __construct(
        string    $address,
        ?XdrSCVal $signature = null,
        array     $nestedDelegates = [],
    ) {
        $this->address         = $address;
        $this->signature       = $signature;
        $this->nestedDelegates = $nestedDelegates;
    }
}
