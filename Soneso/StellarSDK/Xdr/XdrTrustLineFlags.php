<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrTrustLineFlags
{
    private int $value;

    public const AUTHORIZED_FLAG = 1;
    public const AUTHORIZED_TO_MAINTAIN_LIABILITIES_FLAG = 2;
    public const TRUSTLINE_CLAWBACK_ENABLED_FLAG = 4;

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

    public static function decode(XdrBuffer $xdr) : XdrOperationType {
        $value = $xdr->readInteger32();
        return new XdrOperationType($value);
    }
}