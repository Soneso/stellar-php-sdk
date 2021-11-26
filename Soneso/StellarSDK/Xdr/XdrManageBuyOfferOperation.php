<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use InvalidArgumentException;
use phpseclib3\Math\BigInteger;

class XdrManageBuyOfferOperation
{
    private XdrAsset $selling;
    private XdrAsset $buying;
    private BigInteger $amount;
    private XdrPrice $price;
    private int $offerId;

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

    /**
     * @return int
     */
    public function getOfferId(): int
    {
        return $this->offerId;
    }

    public function __construct(XdrAsset $selling, XdrAsset $buying, BigInteger $amount, XdrPrice $price, int $offerId) {
        $this->selling = $selling;
        $this->buying = $buying;
        $this->amount = $amount;
        $this->price = $price;
        $this->offerId = $offerId;

        if ($offerId < 0) {
            throw new InvalidArgumentException("Invalid offer id: ".$offerId);
        }
    }

    public function encode(): string {
        $bytes = $this->selling->encode();
        $bytes .= $this->buying->encode();
        $bytes .= XdrEncoder::bigInteger64($this->amount);
        $bytes .= $this->price->encode();
        $bytes .= XdrEncoder::unsignedInteger64($this->offerId);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr): XdrManageBuyOfferOperation {
        $selling = XdrAsset::decode($xdr);
        $buying = XdrAsset::decode($xdr);
        $amount = $xdr->readBigInteger64($xdr);
        $price = XdrPrice::decode($xdr);
        $offerId = $xdr->readUnsignedInteger64();
        return new XdrManageBuyOfferOperation($selling, $buying, $amount, $price, $offerId);
    }
}