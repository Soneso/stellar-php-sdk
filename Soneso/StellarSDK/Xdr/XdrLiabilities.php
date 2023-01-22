<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use phpseclib3\Math\BigInteger;

class XdrLiabilities
{

    public BigInteger $buying;
    public BigInteger $selling;

    /**
     * @param BigInteger $buying
     * @param BigInteger $selling
     */
    public function __construct(BigInteger $buying, BigInteger $selling)
    {
        $this->buying = $buying;
        $this->selling = $selling;
    }


    public function encode(): string {
        $bytes = XdrEncoder::bigInteger64($this->buying);
        $bytes .= XdrEncoder::bigInteger64($this->selling);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrLiabilities {
        return new XdrLiabilities($xdr->readBigInteger64(), $xdr->readBigInteger64());
    }

    /**
     * @return BigInteger
     */
    public function getBuying(): BigInteger
    {
        return $this->buying;
    }

    /**
     * @param BigInteger $buying
     */
    public function setBuying(BigInteger $buying): void
    {
        $this->buying = $buying;
    }

    /**
     * @return BigInteger
     */
    public function getSelling(): BigInteger
    {
        return $this->selling;
    }

    /**
     * @param BigInteger $selling
     */
    public function setSelling(BigInteger $selling): void
    {
        $this->selling = $selling;
    }

}