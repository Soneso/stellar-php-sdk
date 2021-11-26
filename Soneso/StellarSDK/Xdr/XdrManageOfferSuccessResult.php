<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrManageOfferSuccessResult
{
    private array $offersClaimed; // [XdrClaimAtom]
    private XdrManageOfferSuccessResultOffer $offer;

    public function __construct(array $offersClaimed, XdrManageOfferSuccessResultOffer $offer) {
        $this->offer = $offer;
        $this->offersClaimed = $offersClaimed;
    }

    public function encode() : string {
        $numOffersClaimed = count($this->offersClaimed);
        $bytes = XdrEncoder::integer32($numOffersClaimed);
        foreach ($this->offersClaimed as $offerClaimed) {
            if ($offerClaimed instanceof XdrClaimAtom) {
                $bytes .= $offerClaimed->encode();
            }
        }
        $bytes .=  $this->offer->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrManageOfferSuccessResult {
        $count = $xdr->readInteger32();
        $offersClaimed = array();
        for ($i = 0; $i < $count; $i++) {
            array_push($offersClaimed, XdrClaimAtom::decode($xdr));
        }
        $offer = XdrManageOfferSuccessResultOffer::decode($xdr);
        return new XdrManageOfferSuccessResult($offersClaimed, $offer);
    }

}