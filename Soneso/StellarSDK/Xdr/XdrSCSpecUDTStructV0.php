<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCSpecUDTStructV0
{

    public string $doc;
    public array $lib; // [String]
    public array $name; // [String]
    public array $fields; // [XdrSCSpecUDTStructFieldV0]

    /**
     * @param string $doc
     * @param array $lib
     * @param array $name
     * @param array $fields
     */
    public function __construct(string $doc, array $lib, array $name, array $fields)
    {
        $this->doc = $doc;
        $this->lib = $lib;
        $this->name = $name;
        $this->fields = $fields;
    }


    public function encode(): string {
        $bytes = XdrEncoder::string($this->doc);
        $bytes .= XdrEncoder::integer32(count($this->lib));
        foreach($this->lib as $val) {
            $bytes .= XdrEncoder::string($val);
        }
        $bytes .= XdrEncoder::integer32(count($this->name));
        foreach($this->name as $val) {
            $bytes .= XdrEncoder::string($val);
        }
        $bytes .= XdrEncoder::integer32(count($this->fields));
        foreach($this->fields as $val) {
            $bytes .= $val->encode();
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCSpecUDTStructV0 {
        $doc = $xdr->readString();
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
        $fieldsArr = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($fieldsArr, XdrSCSpecUDTStructFieldV0::decode($xdr));
        }

        return new XdrSCSpecUDTStructV0($doc, $libArr, $nameArr, $fieldsArr);
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
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @param array $fields
     */
    public function setFields(array $fields): void
    {
        $this->fields = $fields;
    }

}