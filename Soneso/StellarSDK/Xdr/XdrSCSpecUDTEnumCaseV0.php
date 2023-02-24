<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCSpecUDTEnumCaseV0
{

    public string $doc;
    public array $name; // [string]
    public int $value;

    /**
     * @param string $doc
     * @param array $name
     * @param int $value
     */
    public function __construct(string $doc, array $name, int $value)
    {
        $this->doc = $doc;
        $this->name = $name;
        $this->value = $value;
    }


    public function encode(): string {
        $bytes = XdrEncoder::string($this->doc);
        $bytes .= XdrEncoder::integer32(count($this->name));
        foreach($this->name as $val) {
            $bytes .= XdrEncoder::string($val);
        }
        $bytes .= XdrEncoder::unsignedInteger32($this->value);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCSpecUDTEnumCaseV0 {
        $doc = $xdr->readString();
        $valCount = $xdr->readInteger32();
        $arr = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($arr, $xdr->readString());
        }
        $value = $xdr->readUnsignedInteger32();

        return new XdrSCSpecUDTEnumCaseV0($doc, $arr, $value);
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
     * @return array [string]
     */
    public function getName(): array
    {
        return $this->name;
    }

    /**
     * @param array $name [string]
     */
    public function setName(array $name): void
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