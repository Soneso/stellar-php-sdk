<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCSpecUDTUnionCaseTupleV0
{

    public string $doc;
    public array $name;
    public array $type; // [XdrSCSpecTypeDef]

    /**
     * @param string $doc
     * @param array $name
     * @param array $type
     */
    public function __construct(string $doc, array $name, array $type)
    {
        $this->doc = $doc;
        $this->name = $name;
        $this->type = $type;
    }


    public function encode(): string {
        $bytes = XdrEncoder::string($this->doc);
        $bytes .= XdrEncoder::integer32(count($this->name));
        foreach($this->name as $val) {
            $bytes .= XdrEncoder::string($val);
        }
        $bytes .= XdrEncoder::integer32(count($this->type));
        foreach($this->type as $val) {
            if ($val instanceof XdrSCSpecTypeDef) {
                $bytes .= $val->encode();
            }

        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCSpecUDTUnionCaseTupleV0 {
        $doc = $xdr->readString();
        $valCount = $xdr->readInteger32();
        $nameArr = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($nameArr, $xdr->readString());
        }
        $valCount = $xdr->readInteger32();
        $typeArr = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($typeArr, XdrSCSpecTypeDef::decode($xdr));
        }

        return new XdrSCSpecUDTUnionCaseTupleV0($doc, $nameArr, $typeArr);
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
    public function getType(): array
    {
        return $this->type;
    }

    /**
     * @param array $type
     */
    public function setType(array $type): void
    {
        $this->type = $type;
    }
}