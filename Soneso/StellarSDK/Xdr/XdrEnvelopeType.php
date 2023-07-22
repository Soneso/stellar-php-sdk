<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrEnvelopeType
{
    private int $value;

    const ENVELOPE_TYPE_TX_V0 = 0;
    const ENVELOPE_TYPE_SCP = 1;
    const ENVELOPE_TYPE_TX = 2;
    const ENVELOPE_TYPE_AUTH = 3;
    const ENVELOPE_TYPE_SCPVALUE = 4;
    const ENVELOPE_TYPE_TX_FEE_BUMP = 5;
    const ENVELOPE_TYPE_OP_ID = 6;
    const ENVELOPE_TYPE_POOL_REVOKE_OP_ID = 7;
    const ENVELOPE_TYPE_CONTRACT_ID = 8;
    const ENVELOPE_TYPE_SOROBAN_AUTHORIZATION = 9;

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