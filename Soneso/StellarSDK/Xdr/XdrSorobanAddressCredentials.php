<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSorobanAddressCredentials
{
    public XdrSCAddress $address;
    public int $nonce; // int64
    public int $signatureExpirationLedger; // uint32
    public XdrSCVal $signature;

    /**
     * @param XdrSCAddress $address
     * @param int $nonce
     * @param int $signatureExpirationLedger
     * @param XdrSCVal $signature
     */
    public function __construct(XdrSCAddress $address, int $nonce, int $signatureExpirationLedger, XdrSCVal $signature)
    {
        $this->address = $address;
        $this->nonce = $nonce;
        $this->signatureExpirationLedger = $signatureExpirationLedger;
        $this->signature = $signature;
    }


    public function encode(): string {
        $bytes = $this->address->encode();
        $bytes .= XdrEncoder::integer64($this->nonce);
        $bytes .= XdrEncoder::unsignedInteger32($this->signatureExpirationLedger);
        $bytes .= $this->signature->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrSorobanAddressCredentials {
        $address = XdrSCAddress::decode($xdr);
        $nonce = $xdr->readInteger64();
        $signatureExpirationLedger = $xdr->readUnsignedInteger32();
        $signature = XdrSCVal::decode($xdr);
        return new XdrSorobanAddressCredentials($address, $nonce, $signatureExpirationLedger, $signature);
    }

    /**
     * @return XdrSCAddress
     */
    public function getAddress(): XdrSCAddress
    {
        return $this->address;
    }

    /**
     * @param XdrSCAddress $address
     */
    public function setAddress(XdrSCAddress $address): void
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