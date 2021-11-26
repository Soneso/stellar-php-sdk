<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use phpseclib3\Math\BigInteger;

class XdrInflationPayout
{
    private XdrAccountID $destination;
    private BigInteger $amount;

    public function __construct(XdrAccountID $destination, BigInteger $amount)
    {
        $this->destination = $destination;
        $this->amount = $amount;
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
    public function getAmount(): BigInteger
    {
        return $this->amount;
    }

    public function encode() : string {
        $bytes = $this->destination->encode();
        $bytes .= XdrEncoder::bigInteger64($this->amount);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrInflationPayout {
        $destination = XdrAccountID::decode($xdr);
        $amount = $xdr->readBigInteger64();
        return new XdrInflationPayout($destination, $amount);
    }
}