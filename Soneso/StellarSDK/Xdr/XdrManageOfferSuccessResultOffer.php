<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrManageOfferSuccessResultOffer
{
    private XdrManageOfferEffect $effect;
    private XdrOfferEntry $offer;

    public function __construct(XdrManageOfferEffect $effect, XdrOfferEntry $offer) {
        $this->offer = $offer;
        $this->effect = $effect;
    }

    /**
     * @return XdrManageOfferEffect
     */
    public function getEffect(): XdrManageOfferEffect
    {
        return $this->effect;
    }

    /**
     * @return XdrOfferEntry
     */
    public function getOffer(): XdrOfferEntry
    {
        return $this->offer;
    }

    public function encode(): string {
        $bytes = $this->effect->encode();
        $bytes .= $this->offer->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrManageOfferSuccessResultOffer {
        $effect = XdrManageOfferEffect::decode($xdr);
        $offer = XdrOfferEntry::decode($xdr);
        return new XdrManageOfferSuccessResultOffer($effect, $offer);
    }
}