<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrAssetType
{
    private int $value;

    const ASSET_TYPE_NATIVE = 0;
    const ASSET_TYPE_CREDIT_ALPHANUM4 = 1;
    const ASSET_TYPE_CREDIT_ALPHANUM12 = 2;
    const ASSET_TYPE_POOL_SHARE = 3;

    public function __construct(int $value) {
        $this->value = $value;
    }

    /**
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }

    public function encode(): string {
        return XdrEncoder::integer32($this->value);
    }

    public static function decode(XdrBuffer $xdr) : XdrAssetType {
        $value = $xdr->readInteger32();
        return new XdrAssetType($value);
    }
}