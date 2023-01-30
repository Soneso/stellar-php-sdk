<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

use phpseclib3\Math\BigInteger;

class XdrOfferEntry
{
    private XdrAccountID $sellerID;
    private int $offerId;
    private XdrAsset $selling;
    private XdrAsset $buying;
    private BigInteger $amount;
    private XdrPrice $price;
    private int $flags;
    private XdrOfferEntryExt $ext;

    /**
     * @return XdrAccountID
     */
    public function getSellerID(): XdrAccountID
    {
        return $this->sellerID;
    }

    /**
     * @param XdrAccountID $sellerID
     */
    public function setSellerID(XdrAccountID $sellerID): void
    {
        $this->sellerID = $sellerID;
    }

    /**
     * @return int
     */
    public function getOfferId(): int
    {
        return $this->offerId;
    }

    /**
     * @param int $offerId
     */
    public function setOfferId(int $offerId): void
    {
        $this->offerId = $offerId;
    }

    /**
     * @return XdrAsset
     */
    public function getSelling(): XdrAsset
    {
        return $this->selling;
    }

    /**
     * @param XdrAsset $selling
     */
    public function setSelling(XdrAsset $selling): void
    {
        $this->selling = $selling;
    }

    /**
     * @return XdrAsset
     */
    public function getBuying(): XdrAsset
    {
        return $this->buying;
    }

    /**
     * @param XdrAsset $buying
     */
    public function setBuying(XdrAsset $buying): void
    {
        $this->buying = $buying;
    }

    /**
     * @return BigInteger
     */
    public function getAmount(): BigInteger
    {
        return $this->amount;
    }

    /**
     * @param BigInteger $amount
     */
    public function setAmount(BigInteger $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return XdrPrice
     */
    public function getPrice(): XdrPrice
    {
        return $this->price;
    }

    /**
     * @param XdrPrice $price
     */
    public function setPrice(XdrPrice $price): void
    {
        $this->price = $price;
    }

    /**
     * @return int
     */
    public function getFlags(): int
    {
        return $this->flags;
    }

    /**
     * @param int $flags
     */
    public function setFlags(int $flags): void
    {
        $this->flags = $flags;
    }

    /**
     * @return XdrOfferEntryExt
     */
    public function getExt(): XdrOfferEntryExt
    {
        return $this->ext;
    }

    /**
     * @param XdrOfferEntryExt $ext
     */
    public function setExt(XdrOfferEntryExt $ext): void
    {
        $this->ext = $ext;
    }

    public function encode() : string {
        $bytes = $this->sellerID->encode();
        $bytes .= XdrEncoder::unsignedInteger64($this->offerId);
        $bytes .= $this->selling->encode();
        $bytes .= $this->buying->encode();
        $bytes .= XdrEncoder::bigInteger64($this->amount);
        $bytes .= $this->price->encode();
        $bytes .= XdrEncoder::unsignedInteger32($this->flags);
        $bytes .= $this->ext->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrOfferEntry {
        $decoded = new XdrOfferEntry();
        $decoded->sellerID = XdrAccountID::decode($xdr);
        $decoded->offerId = $xdr->readUnsignedInteger64();
        $decoded->selling = XdrAsset::decode($xdr);
        $decoded->buying = XdrAsset::decode($xdr);
        $decoded->amount = $xdr->readBigInteger64();
        $decoded->price = XdrPrice::decode($xdr);
        $decoded->flags = $xdr->readUnsignedInteger32();
        $decoded->ext = XdrOfferEntryExt::decode($xdr);
        return $decoded;
    }
}