<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrMemoType
{
    const MEMO_NONE = 0;
    const MEMO_TEXT = 1;
    const MEMO_ID = 2;
    const MEMO_HASH = 3;
    const MEMO_RETURN = 4;

    private int $value;

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

    public static function decode(XdrBuffer $xdr) : XdrEnvelopeType {
        $value = $xdr->readInteger32();
        return new XdrEnvelopeType($value);
    }
}