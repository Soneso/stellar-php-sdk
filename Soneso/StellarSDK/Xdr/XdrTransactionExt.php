<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrTransactionExt
{
    public int $discriminant;
    public ?XdrSorobanTransactionData $sorobanTransactionData = null;

    public function __construct(int $discriminant, ?XdrSorobanTransactionData $sorobanTransactionData = null) {
        $this->discriminant = $discriminant;
        $this->sorobanTransactionData = $sorobanTransactionData;
    }

    /**
     * @return int
     */
    public function getDiscriminant(): int {
        return $this->discriminant;
    }

    public function encode() : string {
        $bytes = XdrEncoder::integer32($this->discriminant);
        switch ($this->discriminant) {
            case 1:
                $bytes .= $this->sorobanTransactionData->encode();
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrTransactionExt {
        $v = $xdr->readInteger32();
        $result = new XdrTransactionExt($v);
        switch ($v) {
            case 1:
                $result->sorobanTransactionData = XdrSorobanTransactionData::decode($xdr);
                break;
        }
        return $result;
    }
}