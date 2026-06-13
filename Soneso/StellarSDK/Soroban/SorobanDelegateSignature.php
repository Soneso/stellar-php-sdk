<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban;

use Soneso\StellarSDK\Xdr\XdrSCAddress;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSorobanDelegateSignature;

/**
 * Delegate signature node for a Soroban WITH_DELEGATES authorization entry (Protocol 27, CAP-71).
 *
 * Each node carries the delegate address, a signature value (void XdrSCVal when unsigned),
 * and an optional list of further nested delegates. The address is stored as XdrSCAddress so
 * it can be XDR-encoded for sorting and comparison without a round-trip through strkey.
 *
 * Invariants:
 * - $nestedDelegates must be sorted ascending by the complete XDR-encoded bytes of each
 *   node's $address before encoding; no duplicates within one array.
 * - $signature is an XdrSCVal::forVoid() when unsigned; callers append signatures via
 *   SorobanAuthorizationEntry::sign().
 * - This type is not idempotent: signing the same node twice with the same key appends a
 *   duplicate that the host will reject. Callers are responsible for call order.
 *
 * @package Soneso\StellarSDK\Soroban
 * @see SorobanAddressCredentialsWithDelegates
 * @see SorobanAuthorizationEntry
 * @since Protocol 27 (CAP-71)
 */
class SorobanDelegateSignature
{
    /**
     * @var XdrSCAddress the address of this delegate node
     */
    public XdrSCAddress $address;

    /**
     * @var XdrSCVal signature value; void (XdrSCVal::forVoid()) when unsigned
     */
    public XdrSCVal $signature;

    /**
     * @var array<SorobanDelegateSignature> sorted nested delegate nodes, empty if none
     */
    public array $nestedDelegates;

    /**
     * @param XdrSCAddress $address the delegate address
     * @param XdrSCVal|null $signature signature or null for void
     * @param array<SorobanDelegateSignature> $nestedDelegates nested delegates (already sorted)
     */
    public function __construct(
        XdrSCAddress $address,
        ?XdrSCVal    $signature = null,
        array        $nestedDelegates = [],
    ) {
        $this->address         = $address;
        $this->signature       = $signature ?? XdrSCVal::forVoid();
        $this->nestedDelegates = $nestedDelegates;
    }

    /**
     * Creates a SorobanDelegateSignature from its XDR representation.
     *
     * @param XdrSorobanDelegateSignature $xdr the XDR delegate signature to decode
     * @return SorobanDelegateSignature the decoded delegate signature
     */
    public static function fromXdr(XdrSorobanDelegateSignature $xdr): SorobanDelegateSignature
    {
        $nested = [];
        foreach ($xdr->nestedDelegates as $xdrNested) {
            $nested[] = self::fromXdr($xdrNested);
        }
        return new SorobanDelegateSignature($xdr->address, $xdr->signature, $nested);
    }

    /**
     * Converts this delegate signature to its XDR representation.
     *
     * @return XdrSorobanDelegateSignature the XDR representation
     */
    public function toXdr(): XdrSorobanDelegateSignature
    {
        $nestedXdr = [];
        foreach ($this->nestedDelegates as $nested) {
            $nestedXdr[] = $nested->toXdr();
        }
        return new XdrSorobanDelegateSignature($this->address, $this->signature, $nestedXdr);
    }

    /**
     * Returns the address of this delegate node.
     *
     * @return XdrSCAddress the delegate address
     */
    public function getAddress(): XdrSCAddress
    {
        return $this->address;
    }

    /**
     * Sets the address of this delegate node.
     *
     * @param XdrSCAddress $address the delegate address
     */
    public function setAddress(XdrSCAddress $address): void
    {
        $this->address = $address;
    }

    /**
     * Returns the signature value.
     *
     * @return XdrSCVal the signature (void when unsigned)
     */
    public function getSignature(): XdrSCVal
    {
        return $this->signature;
    }

    /**
     * Sets the signature value.
     *
     * @param XdrSCVal $signature the signature data
     */
    public function setSignature(XdrSCVal $signature): void
    {
        $this->signature = $signature;
    }

    /**
     * Returns the nested delegates array.
     *
     * @return array<SorobanDelegateSignature> the nested delegate nodes
     */
    public function getNestedDelegates(): array
    {
        return $this->nestedDelegates;
    }

    /**
     * Sets the nested delegates array.
     *
     * @param array<SorobanDelegateSignature> $nestedDelegates the nested delegate nodes
     */
    public function setNestedDelegates(array $nestedDelegates): void
    {
        $this->nestedDelegates = $nestedDelegates;
    }
}
