<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCSpecTypeTuple
{
    public array $valueTypes;

    /**
     * @param array $valueTypes
     */
    public function __construct(array $valueTypes)
    {
        $this->valueTypes = $valueTypes;
    }

    public function encode(): string {
        $bytes = XdrEncoder::integer32(count($this->valueTypes));
        foreach($this->valueTypes as $val) {
            if ($val instanceof XdrSCSpecTypeDef) {
                $bytes .= $val->encode();
            }
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCSpecTypeTuple {
        $valCount = $xdr->readInteger32();
        $arr = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($arr, XdrSCSpecTypeDef::decode($xdr));
        }
        return new XdrSCSpecTypeTuple($arr);
    }

}