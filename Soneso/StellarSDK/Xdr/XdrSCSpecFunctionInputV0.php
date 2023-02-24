<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCSpecFunctionInputV0
{

    public string $doc;
    public array $name;
    public XdrSCSpecTypeDef $type;

    /**
     * @param array $name
     * @param XdrSCSpecTypeDef $type
     */
    public function __construct(string $doc, array $name, XdrSCSpecTypeDef $type)
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
        $bytes .= $this->type->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCSpecFunctionInputV0 {
        $doc = $xdr->readString();
        $valCount = $xdr->readInteger32();
        $arr = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($arr, $xdr->readString());
        }
        $type = XdrSCSpecTypeDef::decode($xdr);

        return new XdrSCSpecFunctionInputV0($doc, $arr, $type);
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
     * @return XdrSCSpecTypeDef
     */
    public function getType(): XdrSCSpecTypeDef
    {
        return $this->type;
    }

    /**
     * @param XdrSCSpecTypeDef $type
     */
    public function setType(XdrSCSpecTypeDef $type): void
    {
        $this->type = $type;
    }
}