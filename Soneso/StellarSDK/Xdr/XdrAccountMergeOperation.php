<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrAccountMergeOperation
{
    private XdrMuxedAccount $destination;

    public function __construct(XdrMuxedAccount $destination) {
        $this->destination = $destination;
    }

    /**
     * @return XdrMuxedAccount
     */
    public function getDestination(): XdrMuxedAccount
    {
        return $this->destination;
    }

    public function encode() : string {
        return $this->destination->encode();
    }

    public static function decode(XdrBuffer $xdr) : XdrAccountMergeOperation {
        $destination = XdrMuxedAccount::decode($xdr);
        return new XdrAccountMergeOperation($destination);
    }
}