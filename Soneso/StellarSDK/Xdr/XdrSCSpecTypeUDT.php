<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCSpecTypeUDT
{

    public array $name;

    /**
     * @param array $name
     */
    public function __construct(array $name)
    {
        $this->name = $name;
    }


    public function encode(): string {
        $bytes = XdrEncoder::integer32(count($this->name));
        foreach($this->name as $val) {
            $bytes .= XdrEncoder::string($val);
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCSpecTypeUDT {
        $valCount = $xdr->readInteger32();
        $arr = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($arr, $xdr->readString());
        }
        return new XdrSCSpecTypeUDT($arr);
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
}