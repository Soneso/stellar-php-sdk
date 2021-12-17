<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use phpseclib3\Math\BigInteger;

class XdrPathPaymentStrictReceiveOperation
{
    private XdrAsset $sendAsset;
    private BigInteger $sendAmount;
    private XdrMuxedAccount $destination;
    private XdrAsset $destAsset;
    private BigInteger $destAmount;
    private array $path; // [XdrAsset]

    public function __construct(XdrAsset $sendAsset, BigInteger $sendAmount, XdrMuxedAccount $destination, XdrAsset $destAsset, BigInteger $destAmount, array $path) {
        $this->sendAsset = $sendAsset;
        $this->sendAmount = $sendAmount;
        $this->destination = $destination;
        $this->destAsset = $destAsset;
        $this->destAmount = $destAmount;
        $this->path = $path;
    }

    /**
     * @return XdrAsset
     */
    public function getSendAsset(): XdrAsset
    {
        return $this->sendAsset;
    }

    /**
     * @return BigInteger
     */
    public function getSendAmount(): BigInteger
    {
        return $this->sendAmount;
    }

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
    public function getDestAsset(): XdrAsset
    {
        return $this->destAsset;
    }

    /**
     * @return BigInteger
     */
    public function getDestAmount(): BigInteger
    {
        return $this->destAmount;
    }

    /**
     * @return array
     */
    public function getPath(): array
    {
        return $this->path;
    }

    public function encode() : string {
        $bytes = $this->sendAsset->encode();
        $bytes .= XdrEncoder::bigInteger64($this->sendAmount);
        $bytes .= $this->destination->encode();
        $bytes .= $this->destAsset->encode();
        $bytes .= XdrEncoder::bigInteger64($this->destAmount);
        $bytes .= XdrEncoder::integer32(count($this->path));
        foreach ($this->path as $asset) {
            if ($asset instanceof XdrAsset) {
                $bytes .= $asset->encode();
            }
        }
        return $bytes;
    }
    public static function decode(XdrBuffer $xdr) : XdrPathPaymentStrictReceiveOperation {

        $sendAsset = XdrAsset::decode($xdr);
        $sendAmount = $xdr->readBigInteger64();
        $destination = XdrMuxedAccount::decode($xdr);
        $destAsset = XdrAsset::decode($xdr);
        $destAmount = $xdr->readBigInteger64();
        $path = array();
        $count = $xdr->readInteger32();
        for ($i = 0; $i < $count; $i++) {
            array_push($path, XdrAsset::decode($xdr));
        }
        return new XdrPathPaymentStrictReceiveOperation($sendAsset, $sendAmount, $destination, $destAsset,$destAmount, $path);
    }
}