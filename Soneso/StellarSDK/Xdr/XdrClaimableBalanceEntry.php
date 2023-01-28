<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use phpseclib3\Math\BigInteger;

class XdrClaimableBalanceEntry
{
    public XdrClaimableBalanceID $accountID;
    public array $claimants;  // [XdrClaimant]
    public XdrAsset $asset;
    private BigInteger $amount;
    public XdrClaimableBalanceEntryExt $ext;

    /**
     * @param XdrClaimableBalanceID $accountID
     * @param array $claimants
     * @param XdrAsset $asset
     * @param BigInteger $amount
     * @param XdrClaimableBalanceEntryExt $ext
     */
    public function __construct(XdrClaimableBalanceID $accountID, array $claimants, XdrAsset $asset, BigInteger $amount, XdrClaimableBalanceEntryExt $ext)
    {
        $this->accountID = $accountID;
        $this->claimants = $claimants;
        $this->asset = $asset;
        $this->amount = $amount;
        $this->ext = $ext;
    }


    public function encode(): string {
        $bytes = $this->accountID->encode();
        $bytes .= XdrEncoder::integer32(count($this->claimants));
        foreach($this->claimants as $val) {
            $bytes .= $val->encode();
        }
        $bytes .= $this->asset->encode();
        $bytes .= XdrEncoder::bigInteger64($this->amount);
        $bytes .= $this->ext->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrClaimableBalanceEntry {
        $cID = XdrClaimableBalanceID::decode($xdr);
        $valCount = $xdr->readInteger32();
        $claimantsArr = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($claimantsArr, XdrClaimant::decode($xdr));
        }

        $asset = XdrAsset::decode($xdr);
        $amount = $xdr->readBigInteger64();
        $ext = XdrClaimableBalanceEntryExt::decode($xdr);

        return new XdrClaimableBalanceEntry($cID, $claimantsArr, $asset, $amount, $ext);
    }

}