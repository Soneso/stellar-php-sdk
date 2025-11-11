<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban;

use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSorobanAddressCredentials;

/**
 * Address-based credentials for Soroban authorization
 *
 * This class represents address-based credentials used in Soroban authorization entries.
 * It contains the address being authorized, a nonce for replay protection, a signature
 * expiration ledger for time-based security, and the signature data itself.
 *
 * @package Soneso\StellarSDK\Soroban
 * @see SorobanCredentials
 * @see SorobanAuthorizationEntry
 * @see https://developers.stellar.org/docs/learn/smart-contract-internals/authorization Soroban Authorization
 * @since 1.0.0
 */
class SorobanAddressCredentials
{
    /**
     * @var Address the address being authorized (account or contract address)
     */
    public Address $address;

    /**
     * @var int nonce for replay protection, must be unique per authorization
     */
    public int $nonce;

    /**
     * @var int ledger sequence number after which this signature expires
     */
    public int $signatureExpirationLedger;

    /**
     * @var XdrSCVal signature data containing one or more signatures authorizing the invocation
     */
    public XdrSCVal $signature;

    /**
     * Creates new address-based credentials for Soroban authorization.
     *
     * @param Address $address the address being authorized (account or contract)
     * @param int $nonce unique nonce for replay protection
     * @param int $signatureExpirationLedger ledger number after which the signature expires
     * @param XdrSCVal $signature signature data (typically a vector of AccountEd25519Signature)
     */
    public function __construct(Address $address, int $nonce, int $signatureExpirationLedger, XdrSCVal $signature)
    {
        $this->address = $address;
        $this->nonce = $nonce;
        $this->signatureExpirationLedger = $signatureExpirationLedger;
        $this->signature = $signature;
    }


    /**
     * Creates SorobanAddressCredentials from its XDR representation.
     *
     * @param XdrSorobanAddressCredentials $xdr the XDR object to decode
     * @return SorobanAddressCredentials the decoded credentials object
     */
    public static function fromXdr(XdrSorobanAddressCredentials $xdr) : SorobanAddressCredentials {
        return new SorobanAddressCredentials(Address::fromXdr($xdr->address), $xdr->nonce, $xdr->signatureExpirationLedger, $xdr->signature);
    }

    /**
     * Converts this object to its XDR representation.
     *
     * @return XdrSorobanAddressCredentials the XDR encoded credentials
     */
    public function toXdr(): XdrSorobanAddressCredentials {
        return new XdrSorobanAddressCredentials($this->address->toXdr(),$this->nonce, $this->signatureExpirationLedger, $this->signature);
    }

    /**
     * Returns the address being authorized.
     *
     * @return Address the authorized address
     */
    public function getAddress(): Address
    {
        return $this->address;
    }

    /**
     * Sets the address being authorized.
     *
     * @param Address $address the address to authorize
     */
    public function setAddress(Address $address): void
    {
        $this->address = $address;
    }

    /**
     * Returns the nonce for replay protection.
     *
     * @return int the nonce value
     */
    public function getNonce(): int
    {
        return $this->nonce;
    }

    /**
     * Sets the nonce for replay protection.
     *
     * @param int $nonce the nonce value
     */
    public function setNonce(int $nonce): void
    {
        $this->nonce = $nonce;
    }

    /**
     * Returns the ledger number after which the signature expires.
     *
     * @return int the expiration ledger sequence number
     */
    public function getSignatureExpirationLedger(): int
    {
        return $this->signatureExpirationLedger;
    }

    /**
     * Sets the ledger number after which the signature expires.
     *
     * @param int $signatureExpirationLedger the expiration ledger sequence number
     */
    public function setSignatureExpirationLedger(int $signatureExpirationLedger): void
    {
        $this->signatureExpirationLedger = $signatureExpirationLedger;
    }

    /**
     * Returns the signature data.
     *
     * @return XdrSCVal the signature (typically a vector of signatures)
     */
    public function getSignature(): XdrSCVal
    {
        return $this->signature;
    }

    /**
     * Sets the signature data.
     *
     * @param XdrSCVal $signature the signature data (typically a vector of signatures)
     */
    public function setSignature(XdrSCVal $signature): void
    {
        $this->signature = $signature;
    }

}