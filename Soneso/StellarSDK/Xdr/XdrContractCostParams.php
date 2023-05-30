<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrContractCostParams
{
    public array $entries; // [XdrContractCostParamEntry]

    public function __construct(array $entries)
    {
        $this->entries = $entries;
    }

    public function encode(): string {
        $bytes = XdrEncoder::integer32(count($this->entries));
        foreach($this->entries as $val) {
            $bytes .= $val->encode();
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrContractCostParams {
        $valCount = $xdr->readInteger32();
        $entriesArr = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($entriesArr, XdrContractCostParamEntry::decode($xdr));
        }

        return new XdrContractCostParams($entriesArr);
    }

    public static function fromBase64Xdr(String $base64Xdr) : XdrContractCostParams {
        $xdr = base64_decode($base64Xdr);
        $xdrBuffer = new XdrBuffer($xdr);
        return XdrContractCostParams::decode($xdrBuffer);
    }

    public function toBase64Xdr() : String {
        return base64_encode($this->encode());
    }
}