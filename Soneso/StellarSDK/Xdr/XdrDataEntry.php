<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrDataEntry
{
    public XdrAccountID $accountID;
    public string $dataName;
    public XdrDataValueMandatory $dataValue;
    public XdrDataEntryExt $ext;

    /**
     * @param XdrAccountID $accountID
     * @param string $dataName
     * @param XdrDataValueMandatory $dataValue
     * @param XdrDataEntryExt $ext
     */
    public function __construct(XdrAccountID $accountID, string $dataName, XdrDataValueMandatory $dataValue, XdrDataEntryExt $ext)
    {
        $this->accountID = $accountID;
        $this->dataName = $dataName;
        $this->dataValue = $dataValue;
        $this->ext = $ext;
    }


    public function encode(): string {
        $bytes = $this->accountID->encode();
        $bytes .= XdrEncoder::string($this->dataName, 64);
        $bytes .= $this->dataValue->encode();
        $bytes .= $this->ext->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrDataEntry {
        $accountID = XdrAccountID::decode($xdr);
        $dataName = $xdr->readString(64);
        $dataValue = XdrDataValueMandatory::decode($xdr);
        $ext = XdrDataEntryExt::decode($xdr);

        return new XdrDataEntry($accountID, $dataName, $dataValue, $ext);
    }

    /**
     * @return XdrAccountID
     */
    public function getAccountID(): XdrAccountID
    {
        return $this->accountID;
    }

    /**
     * @param XdrAccountID $accountID
     */
    public function setAccountID(XdrAccountID $accountID): void
    {
        $this->accountID = $accountID;
    }

    /**
     * @return string
     */
    public function getDataName(): string
    {
        return $this->dataName;
    }

    /**
     * @param string $dataName
     */
    public function setDataName(string $dataName): void
    {
        $this->dataName = $dataName;
    }

    /**
     * @return XdrDataValueMandatory
     */
    public function getDataValue(): XdrDataValueMandatory
    {
        return $this->dataValue;
    }

    /**
     * @param XdrDataValueMandatory $dataValue
     */
    public function setDataValue(XdrDataValueMandatory $dataValue): void
    {
        $this->dataValue = $dataValue;
    }

    /**
     * @return XdrDataEntryExt
     */
    public function getExt(): XdrDataEntryExt
    {
        return $this->ext;
    }

    /**
     * @param XdrDataEntryExt $ext
     */
    public function setExt(XdrDataEntryExt $ext): void
    {
        $this->ext = $ext;
    }
}