<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCSpecUDTUnionV0
{

    public string $doc;
    public string $lib;
    public string $name;
    public array $cases; // [XdrSCSpecUDTUnionCaseV0]

    /**
     * @param string $doc
     * @param string $lib
     * @param string $name
     * @param array $cases
     */
    public function __construct(string $doc, string $lib, string $name, array $cases)
    {
        $this->doc = $doc;
        $this->lib = $lib;
        $this->name = $name;
        $this->cases = $cases;
    }


    public function encode(): string {
        $bytes = XdrEncoder::string($this->doc);
        $bytes .= XdrEncoder::string($this->lib);
        $bytes .= XdrEncoder::string($this->name);
        $bytes .= XdrEncoder::integer32(count($this->cases));
        foreach($this->cases as $val) {
            $bytes .= $val->encode();
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCSpecUDTUnionV0 {
        $doc = $xdr->readString();
        $lib = $xdr->readString();
        $name = $xdr->readString();
        $valCount = $xdr->readInteger32();
        $casesArr = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($casesArr, XdrSCSpecUDTUnionCaseV0::decode($xdr));
        }

        return new XdrSCSpecUDTUnionV0($doc, $lib, $name, $casesArr);
    }

    /**
     * @return string
     */
    public function getDoc(): string
    {
        return $this->doc;
    }

    /**
     * @param string $doc
     */
    public function setDoc(string $doc): void
    {
        $this->doc = $doc;
    }

    /**
     * @return string
     */
    public function getLib(): string
    {
        return $this->lib;
    }

    /**
     * @param string $lib
     */
    public function setLib(string $lib): void
    {
        $this->lib = $lib;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
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