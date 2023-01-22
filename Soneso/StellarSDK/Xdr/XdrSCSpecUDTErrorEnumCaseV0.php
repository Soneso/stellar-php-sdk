<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCSpecUDTErrorEnumCaseV0
{

    public array $name; // [string]
    public int $value;

    /**
     * @param array $name [string]
     * @param int $value
     */
    public function __construct(array $name, int $value)
    {
        $this->name = $name;
        $this->value = $value;
    }


    public function encode(): string {
        $bytes = XdrEncoder::integer32(count($this->name));
        foreach($this->name as $val) {
            $bytes .= XdrEncoder::string($val);
        }
        $bytes .= XdrEncoder::unsignedInteger32($this->value);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCSpecUDTErrorEnumCaseV0 {
        $valCount = $xdr->readInteger32();
        $arr = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($arr, $xdr->readString());
        }
        $value = $xdr->readUnsignedInteger32();

        return new XdrSCSpecUDTErrorEnumCaseV0($arr, $value);
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