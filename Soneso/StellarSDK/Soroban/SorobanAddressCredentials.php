<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban;

use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSorobanAddressCredentials;

/**
 * Used for soroban authorization as a part of SorobanCredentials.
 * See: https://developers.stellar.org/docs/learn/smart-contract-internals/authorization
 */
class SorobanAddressCredentials
{
    public Address $address;
    public int $nonce;
    public int $signatureExpirationLedger;
    public XdrSCVal $signature;

    /**
     * @param Address $address
     * @param int $nonce
     * @param int $signatureExpirationLedger
     * @param XdrSCVal $signature
     */
    public function __construct(Address $address, int $nonce, int $signatureExpirationLedger, XdrSCVal $signature)
    {
        $this->address = $address;
        $this->nonce = $nonce;
        $this->signatureExpirationLedger = $signatureExpirationLedger;
        $this->signature = $signature;
    }


    /**
     * @param XdrSorobanAddressCredentials $xdr
     * @return SorobanAddressCredentials
     */
    public static function fromXdr(XdrSorobanAddressCredentials $xdr) : SorobanAddressCredentials {
        return new SorobanAddressCredentials(Address::fromXdr($xdr->address), $xdr->nonce, $xdr->signatureExpirationLedger, $xdr->signature);
    }

    /**
     * @return XdrSorobanAddressCredentials
     */
    public function toXdr(): XdrSorobanAddressCredentials {
        return new XdrSorobanAddressCredentials($this->address->toXdr(),$this->nonce, $this->signatureExpirationLedger, $this->signature);
    }

    /**
     * @return Address
     */
    public function getAddress(): Address
    {
        return $this->address;
    }

    /**
     * @param Address $address
     */
    public function setAddress(Address $address): void
    {
        $this->address = $address;
    }

    /**
     * @return int
     */
    public function getNonce(): int
    {
        return $this->nonce;
    }

    /**
     * @param int $nonce
     */
    public function setNonce(int $nonce): void
    {
        $this->nonce = $nonce;
    }

    /**
     * @return int
     */
    public function getSignatureExpirationLedger(): int
    {
        return $this->signatureExpirationLedger;
    }

    /**
     * @param int $signatureExpirationLedger
     */
    public function setSignatureExpirationLedger(int $signatureExpirationLedger): void
    {
        $this->signatureExpirationLedger = $signatureExpirationLedger;
    }

    /**
     * @return XdrSCVal
     */
    public function getSignature(): XdrSCVal
    {
        return $this->signature;
    }

    /**
     * @param XdrSCVal $signature
     */
    public function setSignature(XdrSCVal $signature): void
    {
        $this->signature = $signature;
    }

}