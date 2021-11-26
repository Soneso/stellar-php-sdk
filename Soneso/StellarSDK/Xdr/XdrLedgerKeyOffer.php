<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrLedgerKeyOffer
{
    private XdrAccountID $sellerID;
    private int $offerID; // uint64

    public function __construct(XdrAccountID $sellerID, int $offerID) {
        $this->sellerID = $sellerID;
        $this->offerID = $offerID;
    }

    /**
     * @return XdrAccountID
     */
    public function getSellerID(): XdrAccountID
    {
        return $this->sellerID;
    }

    /**
     * @return int
     */
    public function getOfferID(): int
    {
        return $this->offerID;
    }

    public function encode(): string {
        $bytes = $this->sellerID->encode();
        $bytes .= XdrEncoder::unsignedInteger64($this->offerID);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrLedgerKeyOffer {
        $acc = XdrAccountID::decode($xdr);
        $offerID = $xdr->readUnsignedInteger64();
        return new XdrLedgerKeyOffer($acc, $offerID);
    }
}