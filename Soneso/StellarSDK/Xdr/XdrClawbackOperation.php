<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use phpseclib3\Math\BigInteger;

class XdrClawbackOperation
{
    private XdrAsset $asset;
    private XdrMuxedAccount $from;
    private BigInteger $amount;

    public function __construct(XdrAsset $asset, XdrMuxedAccount $from, BigInteger $amount) {
        $this->asset = $asset;
        $this->from = $from;
        $this->amount = $amount;
    }

    /**
     * @return XdrAsset
     */
    public function getAsset(): XdrAsset
    {
        return $this->asset;
    }

    /**
     * @return XdrMuxedAccount
     */
    public function getFrom(): XdrMuxedAccount
    {
        return $this->from;
    }

    /**
     * @return BigInteger
     */
    public function getAmount(): BigInteger
    {
        return $this->amount;
    }

    public function encode() : string {
        $bytes = $this->asset->encode();
        $bytes .= $this->from->encode();
        $bytes .= XdrEncoder::bigInteger64($this->amount);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) :  XdrClawbackOperation {
        $asset = XdrAsset::decode($xdr);
        $from = XdrMuxedAccount::decode($xdr);
        $amount = $xdr->readBigInteger64(); //new BigInteger($xdr->readInteger64());
        return new XdrClawbackOperation($asset, $from, $amount);
    }
}