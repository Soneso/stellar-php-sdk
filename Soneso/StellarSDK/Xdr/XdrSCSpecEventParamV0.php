<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCSpecEventParamV0
{

    public string $doc;
    public string $name;
    public XdrSCSpecTypeDef $type;
    public XdrSCSpecEventParamLocationV0 $location;

    /**
     * @param string $doc
     * @param string $name
     * @param XdrSCSpecTypeDef $type
     * @param XdrSCSpecEventParamLocationV0 $location
     */
    public function __construct(string $doc, string $name, XdrSCSpecTypeDef $type, XdrSCSpecEventParamLocationV0 $location)
    {
        $this->doc = $doc;
        $this->name = $name;
        $this->type = $type;
        $this->location = $location;
    }


    public function encode(): string {
        $bytes = XdrEncoder::string($this->doc);
        $bytes .= XdrEncoder::string($this->name);
        $bytes .= $this->type->encode();
        $bytes .= $this->location->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCSpecEventParamV0 {
        $doc = $xdr->readString();
        $name = $xdr->readString();
        $type = XdrSCSpecTypeDef::decode($xdr);
        $location = XdrSCSpecEventParamLocationV0::decode($xdr);

        return new XdrSCSpecEventParamV0($doc, $name, $type, $location);
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

    /**
     * @return XdrSCSpecEventParamLocationV0
     */
    public function getLocation(): XdrSCSpecEventParamLocationV0
    {
        return $this->location;
    }

    /**
     * @param XdrSCSpecEventParamLocationV0 $location
     */
    public function setLocation(XdrSCSpecEventParamLocationV0 $location): void
    {
        $this->location = $location;
    }

}