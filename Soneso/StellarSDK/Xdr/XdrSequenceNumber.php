<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use phpseclib3\Math\BigInteger;

class XdrSequenceNumber
{
    private BigInteger $sequenceNumber;

    public function __construct(BigInteger $sequenceNumber) {
        $this->sequenceNumber = $sequenceNumber;
    }

    /**
     * @return BigInteger
     */
    public function getValue(): BigInteger
    {
        return $this->sequenceNumber;
    }

    public function encode() : string {
        return XdrEncoder::bigInteger64($this->sequenceNumber);
    }

    public static function decode(XdrBuffer $xdr) : XdrSequenceNumber {
        return new XdrSequenceNumber($xdr->readBigInteger64());
    }
}