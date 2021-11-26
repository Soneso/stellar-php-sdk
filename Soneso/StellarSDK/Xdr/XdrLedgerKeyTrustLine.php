<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrLedgerKeyTrustLine
{
    private XdrAccountID $accountID;
    private XdrTrustlineAsset $asset;

    public function __construct(XdrAccountID $accountID, XdrTrustlineAsset $asset) {
        $this->accountID = $accountID;
        $this->asset = $asset;
    }

    /**
     * @return XdrAccountID
     */
    public function getAccountID(): XdrAccountID
    {
        return $this->accountID;
    }

    /**
     * @return XdrTrustlineAsset
     */
    public function getAsset(): XdrTrustlineAsset
    {
        return $this->asset;
    }

    public function encode(): string {
        $bytes = $this->accountID->encode();
        $bytes .= $this->asset->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrLedgerKeyTrustLine {
        $acc = XdrAccountID::decode($xdr);
        $asset = XdrTrustlineAsset::decode($xdr);
        return new XdrLedgerKeyTrustLine($acc,$asset);
    }
}