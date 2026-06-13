<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban;

use Soneso\StellarSDK\Xdr\XdrSorobanAddressCredentialsWithDelegates;

/**
 * Address credentials with a delegate tree for Soroban WITH_DELEGATES authorization (Protocol 27, CAP-71).
 *
 * This is the hand-written wrapper for the ADDRESS_WITH_DELEGATES credential arm. It pairs the
 * top-level SorobanAddressCredentials (address, nonce, signatureExpirationLedger, signature) with
 * a sorted array of SorobanDelegateSignature nodes that can authorize the same entry.
 *
 * Invariants:
 * - $delegates must be sorted ascending by the complete XDR-encoded bytes of each delegate's
 *   XdrSCAddress before submission; no duplicates within this array.
 * - The top-level $addressCredentials carries the nonce and signatureExpirationLedger.
 *   Delegate nodes carry neither.
 * - An empty $delegates array is structurally valid and behaves like ADDRESS_V2.
 *
 * @package Soneso\StellarSDK\Soroban
 * @see SorobanDelegateSignature
 * @see SorobanCredentials
 * @since Protocol 27 (CAP-71)
 */
class SorobanAddressCredentialsWithDelegates
{
    /**
     * @var SorobanAddressCredentials top-level credentials carrying address, nonce, expiration, and top-level signature
     */
    public SorobanAddressCredentials $addressCredentials;

    /**
     * @var array<SorobanDelegateSignature> top-level delegate nodes, sorted by XDR-encoded address bytes
     */
    public array $delegates;

    /**
     * @param SorobanAddressCredentials $addressCredentials the top-level address credentials
     * @param array<SorobanDelegateSignature> $delegates delegate signature nodes (must be sorted)
     */
    public function __construct(
        SorobanAddressCredentials $addressCredentials,
        array                     $delegates = [],
    ) {
        $this->addressCredentials = $addressCredentials;
        $this->delegates          = $delegates;
    }

    /**
     * Creates a SorobanAddressCredentialsWithDelegates from its XDR representation.
     *
     * @param XdrSorobanAddressCredentialsWithDelegates $xdr the XDR object to decode
     * @return SorobanAddressCredentialsWithDelegates the decoded object
     */
    public static function fromXdr(XdrSorobanAddressCredentialsWithDelegates $xdr): SorobanAddressCredentialsWithDelegates
    {
        $delegates = [];
        foreach ($xdr->delegates as $xdrDelegate) {
            $delegates[] = SorobanDelegateSignature::fromXdr($xdrDelegate);
        }
        return new SorobanAddressCredentialsWithDelegates(
            SorobanAddressCredentials::fromXdr($xdr->addressCredentials),
            $delegates,
        );
    }

    /**
     * Converts this object to its XDR representation.
     *
     * @return XdrSorobanAddressCredentialsWithDelegates the XDR representation
     */
    public function toXdr(): XdrSorobanAddressCredentialsWithDelegates
    {
        $delegatesXdr = [];
        foreach ($this->delegates as $delegate) {
            $delegatesXdr[] = $delegate->toXdr();
        }
        return new XdrSorobanAddressCredentialsWithDelegates(
            $this->addressCredentials->toXdr(),
            $delegatesXdr,
        );
    }

    /**
     * Returns the top-level address credentials.
     *
     * @return SorobanAddressCredentials the top-level credentials
     */
    public function getAddressCredentials(): SorobanAddressCredentials
    {
        return $this->addressCredentials;
    }

    /**
     * Sets the top-level address credentials.
     *
     * @param SorobanAddressCredentials $addressCredentials the top-level credentials
     */
    public function setAddressCredentials(SorobanAddressCredentials $addressCredentials): void
    {
        $this->addressCredentials = $addressCredentials;
    }

    /**
     * Returns the delegate nodes array.
     *
     * @return array<SorobanDelegateSignature> the delegate nodes
     */
    public function getDelegates(): array
    {
        return $this->delegates;
    }

    /**
     * Sets the delegate nodes array.
     *
     * @param array<SorobanDelegateSignature> $delegates the delegate nodes
     */
    public function setDelegates(array $delegates): void
    {
        $this->delegates = $delegates;
    }
}
