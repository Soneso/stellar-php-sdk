<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrLedgerEntryV1
{

    public ?XdrAccountID $sponsoringID = null;
    public XdrLedgerEntryV1Ext $ext;

    /**
     * @param XdrAccountID|null $sponsoringID
     * @param XdrLedgerEntryV1Ext $ext
     */
    public function __construct(?XdrAccountID $sponsoringID, XdrLedgerEntryV1Ext $ext)
    {
        $this->sponsoringID = $sponsoringID;
        $this->ext = $ext;
    }


    public function encode(): string {
        $bytes = "";
        if ($this->sponsoringID != null) {
            $bytes = XdrEncoder::integer32(1);
            $bytes .= $this->sponsoringID->encode();
        } else {
            $bytes = XdrEncoder::integer32(0);
        }
        $bytes .= $this->ext->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrLedgerEntryV1 {
        $sponsoringID = null;
        if ($xdr->readInteger32() == 1) {
            $sponsoringID = XdrAccountID::decode($xdr);
        }
        $ext = XdrLedgerEntryV1Ext::decode($xdr);
        return new XdrLedgerEntryV1($sponsoringID, $ext);
    }

    /**
     * @return XdrAccountID|null
     */
    public function getSponsoringID(): ?XdrAccountID
    {
        return $this->sponsoringID;
    }

    /**
     * @param XdrAccountID|null $sponsoringID
     */
    public function setSponsoringID(?XdrAccountID $sponsoringID): void
    {
        $this->sponsoringID = $sponsoringID;
    }

    /**
     * @return XdrLedgerEntryV1Ext
     */
    public function getExt(): XdrLedgerEntryV1Ext
    {
        return $this->ext;
    }

    /**
     * @param XdrLedgerEntryV1Ext $ext
     */
    public function setExt(XdrLedgerEntryV1Ext $ext): void
    {
        $this->ext = $ext;
    }

}