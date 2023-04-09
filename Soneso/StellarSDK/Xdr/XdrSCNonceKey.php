<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCNonceKey
{

    public XdrSCAddress $nonceAddress;

    /**
     * @param XdrSCAddress $nonceAddress
     */
    public function __construct(XdrSCAddress $nonceAddress)
    {
        $this->nonceAddress = $nonceAddress;
    }

    public function encode(): string {
        return $this->nonceAddress->encode();
    }

    public static function decode(XdrBuffer $xdr):  XdrSCNonceKey {
        return new XdrSCNonceKey(XdrSCAddress::decode($xdr));
    }

    /**
     * @return XdrSCAddress
     */
    public function getNonceAddress(): XdrSCAddress
    {
        return $this->nonceAddress;
    }

    /**
     * @param XdrSCAddress $nonceAddress
     */
    public function setNonceAddress(XdrSCAddress $nonceAddress): void
    {
        $this->nonceAddress = $nonceAddress;
    }

}