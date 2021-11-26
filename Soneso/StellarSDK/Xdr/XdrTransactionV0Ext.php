<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrTransactionV0Ext
{
    private int $discriminant;

    public function __construct(int $discriminant) {
        $this->discriminant = $discriminant;
    }

    /**
     * @return int
     */
    public function getDiscriminant(): int {
        return $this->discriminant;
    }

    public function encode() : string {
        return XdrEncoder::integer32($this->discriminant);
    }

    public static function decode(XdrBuffer $xdr) : XdrTransactionV0Ext {
        $v = $xdr->readInteger32();
        return new XdrTransactionV0Ext($v);
    }
}