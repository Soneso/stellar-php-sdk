<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrAddressWithNonce
{
    public XdrSCAddress $address;
    public int $nonce; // uint64

    /**
     * @param XdrSCAddress $address
     * @param int $nonce
     */
    public function __construct(XdrSCAddress $address, int $nonce)
    {
        $this->address = $address;
        $this->nonce = $nonce;
    }

    public function encode(): string {
        $bytes = $this->address->encode();
        $bytes .= XdrEncoder::unsignedInteger64($this->nonce);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrAddressWithNonce {
        $address = XdrSCAddress::decode($xdr);
        $nonce = $xdr->readUnsignedInteger64();
        return new XdrAddressWithNonce($address, $nonce);
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

}