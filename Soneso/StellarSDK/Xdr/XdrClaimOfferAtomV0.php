<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

use phpseclib3\Math\BigInteger;

class XdrClaimOfferAtomV0
{
    private string $sellerEd25519; //uint256
    private int $offerId;
    private XdrAsset $assetSold;
    private BigInteger $amountSold;
    private XdrAsset $assetBought;
    private BigInteger $amountBought;

    public function __construct(string $sellerEd25519, int $offerId, XdrAsset $assetSold, BigInteger $amountSold, XdrAsset $assetBought, BigInteger $amountBought) {
        $this->sellerEd25519 = $sellerEd25519;
        $this->offerId = $offerId;
        $this->assetSold = $assetSold;
        $this->amountSold = $amountSold;
        $this->assetBought = $assetBought;
        $this->amountBought = $amountBought;
    }

    /**
     * @return string
     */
    public function getSellerEd25519(): string
    {
        return $this->sellerEd25519;
    }

    /**
     * @return int
     */
    public function getOfferId(): int
    {
        return $this->offerId;
    }

    /**
     * @return XdrAsset
     */
    public function getAssetSold(): XdrAsset
    {
        return $this->assetSold;
    }

    /**
     * @return BigInteger
     */
    public function getAmountSold(): BigInteger
    {
        return $this->amountSold;
    }

    /**
     * @return XdrAsset
     */
    public function getAssetBought(): XdrAsset
    {
        return $this->assetBought;
    }

    /**
     * @return BigInteger
     */
    public function getAmountBought(): BigInteger
    {
        return $this->amountBought;
    }

    public function encode() : string {
       $bytes = XdrEncoder::unsignedInteger256($this->sellerEd25519);
       $bytes .= XdrEncoder::unsignedInteger64($this->offerId);
       $bytes .= $this->assetSold->encode();
       $bytes .= XdrEncoder::bigInteger64($this->amountSold);
       $bytes .= $this->assetBought->encode();
       $bytes .= XdrEncoder::bigInteger64($this->amountBought);
       return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrClaimOfferAtomV0 {
        $seller = $xdr->readUnsignedInteger256();
        $offerId = $xdr->readUnsignedInteger64();
        $assetSold = XdrAsset::decode($xdr);
        $amountSold = $xdr->readBigInteger64();
        $assetBought = XdrAsset::decode($xdr);
        $amountBought = $xdr->readBigInteger64();
        return new XdrClaimOfferAtomV0($seller, $offerId, $assetSold, $amountSold, $assetBought, $amountBought);
    }
}