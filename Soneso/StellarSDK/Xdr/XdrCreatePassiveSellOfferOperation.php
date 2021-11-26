<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

use phpseclib3\Math\BigInteger;

class XdrCreatePassiveSellOfferOperation
{
    private XdrAsset $selling;
    private XdrAsset $buying;
    private BigInteger $amount;
    private XdrPrice $price;

    /**
     * @return XdrAsset
     */
    public function getSelling(): XdrAsset
    {
        return $this->selling;
    }

    /**
     * @return XdrAsset
     */
    public function getBuying(): XdrAsset
    {
        return $this->buying;
    }

    /**
     * @return BigInteger
     */
    public function getAmount(): BigInteger
    {
        return $this->amount;
    }

    /**
     * @return XdrPrice
     */
    public function getPrice(): XdrPrice
    {
        return $this->price;
    }

    public function __construct(XdrAsset $selling, XdrAsset $buying, BigInteger $amount, XdrPrice $price) {
        $this->selling = $selling;
        $this->buying = $buying;
        $this->amount = $amount;
        $this->price = $price;
    }

    public function encode(): string {
        $bytes = $this->selling->encode();
        $bytes .= $this->buying->encode();
        $bytes .= XdrEncoder::bigInteger64($this->amount);
        $bytes .= $this->price->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr): XdrCreatePassiveSellOfferOperation {
        $selling = XdrAsset::decode($xdr);
        $buying = XdrAsset::decode($xdr);
        $amount = $xdr->readBigInteger64($xdr);
        $price = XdrPrice::decode($xdr);
        return new XdrCreatePassiveSellOfferOperation($selling, $buying, $amount, $price);
    }
}