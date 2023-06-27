<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCSpecFunctionInputV0
{

    public string $doc;
    public string $name;
    public XdrSCSpecTypeDef $type;

    /**
     * @param string $doc
     * @param string $name
     * @param XdrSCSpecTypeDef $type
     */
    public function __construct(string $doc, string $name, XdrSCSpecTypeDef $type)
    {
        $this->doc = $doc;
        $this->name = $name;
        $this->type = $type;
    }


    public function encode(): string {
        $bytes = XdrEncoder::string($this->doc);
        $bytes .= XdrEncoder::string($this->name);
        $bytes .= $this->type->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCSpecFunctionInputV0 {
        $doc = $xdr->readString();
        $name = $xdr->readString();
        $type = XdrSCSpecTypeDef::decode($xdr);

        return new XdrSCSpecFunctionInputV0($doc, $name, $type);
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