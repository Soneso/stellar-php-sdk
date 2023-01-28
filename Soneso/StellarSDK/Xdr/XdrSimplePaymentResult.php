<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use phpseclib3\Math\BigInteger;

class XdrSimplePaymentResult
{
    private XdrMuxedAccount $destination;
    private XdrAsset $asset;
    private BigInteger $amount;

    /**
     * @return XdrMuxedAccount
     */
    public function getDestination(): XdrMuxedAccount
    {
        return $this->destination;
    }

    /**
     * @return XdrAsset
     */
    public function getAsset(): XdrAsset
    {
        return $this->asset;
    }

    /**
     * @return BigInteger
     */
    public function getAmount(): BigInteger
    {
        return $this->amount;
    }

    public function encode(): string {
        $bytes = $this->destination->encode();
        $bytes .= $this->asset->encode();
        $bytes .= XdrEncoder::bigInteger64($this->amount);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrSimplePaymentResult {

        $result = new XdrSimplePaymentResult();
        $result->destination = XdrMuxedAccount::decode($xdr);
        $result->asset = XdrAsset::decode($xdr);
        $result->amount = $xdr->readBigInteger64();
        return $result;
    }
}