<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrLedgerFootprint
{

    public array $readOnly; // [XdrLedgerKey]
    public array $readWrite; // [XdrLedgerKey]

    /**
     * @param array $readOnly [XdrLedgerKey]
     * @param array $readWrite [XdrLedgerKey]
     */
    public function __construct(array $readOnly, array $readWrite)
    {
        $this->readOnly = $readOnly;
        $this->readWrite = $readWrite;
    }


    public function encode(): string {
        $bytes = XdrEncoder::integer32(count($this->readOnly));
        foreach($this->readOnly as $val) {
            $bytes .= $val->encode();
        }
        $bytes .= XdrEncoder::integer32(count($this->readWrite));
        foreach($this->readWrite as $val) {
            $bytes .= $val->encode();
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrLedgerFootprint {
        $valCount = $xdr->readInteger32();
        $readOnlyArr = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($readOnlyArr, XdrLedgerKey::decode($xdr));
        }
        $valCount = $xdr->readInteger32();
        $readWriteArr = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($readWriteArr, XdrLedgerKey::decode($xdr));
        }

        return new XdrLedgerFootprint($readOnlyArr, $readWriteArr);
    }

    public static function fromBase64Xdr(String $base64Xdr) : XdrLedgerFootprint {
        $xdr = base64_decode($base64Xdr);
        $xdrBuffer = new XdrBuffer($xdr);
        return XdrLedgerFootprint::decode($xdrBuffer);
    }

    public function toBase64Xdr() : String {
        return base64_encode($this->encode());
    }

    /**
     * @return array [XdrLedgerKey]
     */
    public function getReadOnly(): array
    {
        return $this->readOnly;
    }

    /**
     * @param array $readOnly [XdrLedgerKey]
     */
    public function setReadOnly(array $readOnly): void
    {
        $this->readOnly = $readOnly;
    }

    /**
     * @return array [XdrLedgerKey]
     */
    public function getReadWrite(): array
    {
        return $this->readWrite;
    }

    /**
     * @param array $readWrite [XdrLedgerKey]
     */
    public function setReadWrite(array $readWrite): void
    {
        $this->readWrite = $readWrite;
    }
}