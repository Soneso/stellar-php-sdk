<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrTrustLineEntryV1
{

    public XdrLiabilities $liabilities;
    public XdrTrustLineEntryV1Ext $ext;

    /**
     * @param XdrLiabilities $liabilities
     * @param XdrTrustLineEntryV1Ext $ext
     */
    public function __construct(XdrLiabilities $liabilities, XdrTrustLineEntryV1Ext $ext)
    {
        $this->liabilities = $liabilities;
        $this->ext = $ext;
    }


    public function encode(): string {
        $bytes = $this->liabilities->encode();
        $bytes .= $this->ext->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrTrustLineEntryV1 {
        $liabilities = XdrLiabilities::decode($xdr);
        $ext = XdrTrustLineEntryV1Ext::decode($xdr);
        return new XdrTrustLineEntryV1($liabilities, $ext);
    }

    /**
     * @return XdrLiabilities
     */
    public function getLiabilities(): XdrLiabilities
    {
        return $this->liabilities;
    }

    /**
     * @param XdrLiabilities $liabilities
     */
    public function setLiabilities(XdrLiabilities $liabilities): void
    {
        $this->liabilities = $liabilities;
    }

    /**
     * @return XdrTrustLineEntryV1Ext
     */
    public function getExt(): XdrTrustLineEntryV1Ext
    {
        return $this->ext;
    }

    /**
     * @param XdrTrustLineEntryV1Ext $ext
     */
    public function setExt(XdrTrustLineEntryV1Ext $ext): void
    {
        $this->ext = $ext;
    }

}