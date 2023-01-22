<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCSpecUDTErrorEnumV0
{

    public array $lib; // [String]
    public array $name; // [String]
    public array $cases; // [XdrSCSpecUDTErrorEnumCaseV0]

    /**
     * @param array $lib [string]
     * @param array $name [string]
     * @param array $cases [XdrSCSpecUDTErrorEnumCaseV0]
     */
    public function __construct(array $lib, array $name, array $cases)
    {
        $this->lib = $lib;
        $this->name = $name;
        $this->cases = $cases;
    }


    public function encode(): string {
        $bytes = XdrEncoder::integer32(count($this->lib));
        foreach($this->lib as $val) {
            $bytes .= XdrEncoder::string($val);
        }
        $bytes .= XdrEncoder::integer32(count($this->name));
        foreach($this->name as $val) {
            $bytes .= XdrEncoder::string($val);
        }
        $bytes .= XdrEncoder::integer32(count($this->cases));
        foreach($this->cases as $val) {
            $bytes .= $val->encode();
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCSpecUDTErrorEnumV0 {
        $valCount = $xdr->readInteger32();
        $libArr = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($libArr, $xdr->readString());
        }
        $valCount = $xdr->readInteger32();
        $nameArr = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($nameArr, $xdr->readString());
        }
        $valCount = $xdr->readInteger32();
        $casesArr = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($casesArr, XdrSCSpecUDTErrorEnumCaseV0::decode($xdr));
        }

        return new XdrSCSpecUDTErrorEnumV0($libArr, $nameArr, $casesArr);
    }

    /**
     * @return array
     */
    public function getLib(): array
    {
        return $this->lib;
    }

    /**
     * @param array $lib
     */
    public function setLib(array $lib): void
    {
        $this->lib = $lib;
    }

    /**
     * @return array
     */
    public function getName(): array
    {
        return $this->name;
    }

    /**
     * @param array $name
     */
    public function setName(array $name): void
    {
        $this->name = $name;
    }

    /**
     * @return array
     */
    public function getCases(): array
    {
        return $this->cases;
    }

    /**
     * @param array $cases
     */
    public function setCases(array $cases): void
    {
        $this->cases = $cases;
    }


}