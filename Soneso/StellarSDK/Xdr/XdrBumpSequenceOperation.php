<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrBumpSequenceOperation
{
    private XdrSequenceNumber $bumpTo;

    /**
     * @return XdrSequenceNumber
     */
    public function getBumpTo(): XdrSequenceNumber
    {
        return $this->bumpTo;
    }

    public function __construct(XdrSequenceNumber $bumpTo) {
        $this->bumpTo = $bumpTo;
    }

    public function encode() : string {
        return $this->bumpTo->encode();
    }
    public static function decode(XdrBuffer $xdr): XdrBumpSequenceOperation {
        $seqNr = XdrSequenceNumber::decode($xdr);
        return new XdrBumpSequenceOperation($seqNr);
    }
}