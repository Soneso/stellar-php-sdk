<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrClaimantV0
{
    private XdrAccountID $destination;
    private XdrClaimPredicate $predicate;

    public function __construct(XdrAccountID $destination, XdrClaimPredicate $predicate) {
        $this->destination = $destination;
        $this->predicate = $predicate;
    }

    /**
     * @return XdrAccountID
     */
    public function getDestination(): XdrAccountID
    {
        return $this->destination;
    }

    /**
     * @return XdrClaimPredicate
     */
    public function getPredicate(): XdrClaimPredicate
    {
        return $this->predicate;
    }

    public function encode() : string {
        $bytes = $this->destination->encode();
        $bytes .= $this->predicate->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrClaimantV0 {
        $destination = XdrAccountID::decode($xdr);
        $predicate = XdrClaimPredicate::decode($xdr);
        return new XdrClaimantV0($destination, $predicate);
    }
}