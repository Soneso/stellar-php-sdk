<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban;

use Soneso\StellarSDK\Xdr\XdrSorobanAddressCredentials;


class SorobanAddressCredentials
{
    public Address $address;
    public int $nonce;
    public int $signatureExpirationLedger;
    public array $signatureArgs; // [XdrSCVal]

    /**
     * @param Address $address
     * @param int $nonce
     * @param int $signatureExpirationLedger
     * @param array $signatureArgs
     */
    public function __construct(Address $address, int $nonce, int $signatureExpirationLedger, array $signatureArgs = array())
    {
        $this->address = $address;
        $this->nonce = $nonce;
        $this->signatureExpirationLedger = $signatureExpirationLedger;
        $this->signatureArgs = $signatureArgs;
    }

    /**
     * @param XdrSorobanAddressCredentials $xdr
     * @return SorobanAddressCredentials
     */
    public static function fromXdr(XdrSorobanAddressCredentials $xdr) : SorobanAddressCredentials {
        return new SorobanAddressCredentials(Address::fromXdr($xdr->address), $xdr->nonce, $xdr->signatureExpirationLedger, $xdr->signatureArgs);
    }

    /**
     * @return XdrSorobanAddressCredentials
     */
    public function toXdr(): XdrSorobanAddressCredentials {
        return new XdrSorobanAddressCredentials($this->address->toXdr(),$this->nonce, $this->signatureExpirationLedger, $this->signatureArgs);
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
     * @return array
     */
    public function getSignatureArgs(): array
    {
        return $this->signatureArgs;
    }

    /**
     * @param array $signatureArgs
     */
    public function setSignatureArgs(array $signatureArgs): void
    {
        $this->signatureArgs = $signatureArgs;
    }
}