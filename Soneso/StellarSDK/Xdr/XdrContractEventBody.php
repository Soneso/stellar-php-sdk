<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrContractEventBody
{
    public int $v;
    public ?XdrContractEventBodyV0 $v0 = null;
    /**
     * @param int $v
     */
    public function __construct(int $v)
    {
        $this->v = $v;
    }

    public function encode() : string {
        $bytes = XdrEncoder::integer32($this->v);

        switch ($this->v) {
            case 0:
                $bytes .= $this->v0->encode();
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrContractEventBody {
        $v = $xdr->readInteger32();
        $result = new XdrContractEventBody($v);
        switch ($v) {
            case 0:
                $result->v0 = XdrContractEventBodyV0::decode($xdr);
                break;
        }
        return $result;
    }

}