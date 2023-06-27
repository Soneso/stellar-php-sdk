<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCSpecUDTErrorEnumCaseV0
{

    public string $doc;
    public string $name;
    public int $value;

    /**
     * @param string $doc
     * @param string $name
     * @param int $value
     */
    public function __construct(string $doc, string $name, int $value)
    {
        $this->doc = $doc;
        $this->name = $name;
        $this->value = $value;
    }


    public function encode(): string {
        $bytes = XdrEncoder::string($this->doc);
        $bytes .= XdrEncoder::string($this->name);
        $bytes .= XdrEncoder::unsignedInteger32($this->value);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCSpecUDTErrorEnumCaseV0 {
        $doc = $xdr->readString();
        $name = $xdr->readString();
        $value = $xdr->readUnsignedInteger32();

        return new XdrSCSpecUDTErrorEnumCaseV0($doc, $name, $value);
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
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * @param int $value
     */
    public function setValue(int $value): void
    {
        $this->value = $value;
    }
}