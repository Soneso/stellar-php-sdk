<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSorobanTransactionDataExt
{
    public int $discriminant;
    public ?XdrSorobanResourcesExtV0 $resourceExt = null;

    /**
     * @param int $discriminant
     * @param XdrSorobanResourcesExtV0|null $resourceExt
     */
    public function __construct(int $discriminant, ?XdrSorobanResourcesExtV0 $resourceExt = null)
    {
        $this->discriminant = $discriminant;
        $this->resourceExt = $resourceExt;
    }


    /**
     * @return int
     */
    public function getDiscriminant(): int {
        return $this->discriminant;
    }

    public function encode() : string {
        $bytes = XdrEncoder::integer32($this->discriminant);
        if ($this->discriminant == 1) {
            $bytes .= $this->resourceExt->encode();
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrSorobanTransactionDataExt {
        $v = $xdr->readInteger32();
        $result = new XdrSorobanTransactionDataExt($v);
        if ($v == 1) {
            $result->resourceExt = XdrSorobanResourcesExtV0::decode($xdr);
        }
        return $result;
    }
}