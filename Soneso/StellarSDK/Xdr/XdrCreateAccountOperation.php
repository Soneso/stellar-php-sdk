<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use phpseclib3\Math\BigInteger;

class XdrCreateAccountOperation
{
    private XdrAccountID $destination;
    private BigInteger $startingBalance; //in stroops

    public function __construct(XdrAccountID $destination, BigInteger $startingBalance) {
        $this->destination = $destination;
        $this->startingBalance = $startingBalance;
    }

    /**
     * @return XdrAccountID
     */
    public function getDestination(): XdrAccountID
    {
        return $this->destination;
    }

    /**
     * @return BigInteger
     */
    public function getStartingBalance(): BigInteger
    {
        return $this->startingBalance;
    }

    public function encode() : string {
        $bytes = $this->destination->encode();
        $bytes .= XdrEncoder::bigInteger64($this->startingBalance);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) :  XdrCreateAccountOperation {
        $destination = XdrAccountID::decode($xdr);
        $startingBalance = $xdr->readBigInteger64(); //new BigInteger($xdr->readInteger64());
        return new XdrCreateAccountOperation($destination, $startingBalance);
    }
}