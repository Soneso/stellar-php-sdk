<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCSpecUDTUnionCaseVoidV0
{
    public string $doc;
    public string $name;

    /**
     * @param string $doc
     * @param string $name
     */
    public function __construct(string $doc, string $name)
    {
        $this->doc = $doc;
        $this->name = $name;
    }


    public function encode(): string {
        $bytes = XdrEncoder::string($this->doc);
        $bytes .= XdrEncoder::string($this->name);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCSpecUDTUnionCaseVoidV0 {
        $doc = $xdr->readString();
        $name = $xdr->readString();
        return new XdrSCSpecUDTUnionCaseVoidV0($doc, $name);
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

}