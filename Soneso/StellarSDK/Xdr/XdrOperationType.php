<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrOperationType
{
    private int $value;

    const CREATE_ACCOUNT = 0;
    const PAYMENT = 1;
    const PATH_PAYMENT_STRICT_RECEIVE = 2;
    const MANAGE_SELL_OFFER = 3;
    const CREATE_PASSIVE_SELL_OFFER = 4;
    const SET_OPTIONS = 5;
    const CHANGE_TRUST = 6;
    const ALLOW_TRUST = 7;
    const ACCOUNT_MERGE = 8;
    const INFLATION = 9;
    const MANAGE_DATA = 10;
    const BUMP_SEQUENCE = 11;
    const MANAGE_BUY_OFFER = 12;
    const PATH_PAYMENT_STRICT_SEND = 13;
    const CREATE_CLAIMABLE_BALANCE = 14;
    const CLAIM_CLAIMABLE_BALANCE = 15;
    const BEGIN_SPONSORING_FUTURE_RESERVES = 16;
    const END_SPONSORING_FUTURE_RESERVES = 17;
    const REVOKE_SPONSORSHIP = 18;
    const CLAWBACK = 19;
    const CLAWBACK_CLAIMABLE_BALANCE = 20;
    const SET_TRUST_LINE_FLAGS = 21;
    const LIQUIDITY_POOL_DEPOSIT = 22;
    const LIQUIDITY_POOL_WITHDRAW = 23;
    const INVOKE_HOST_FUNCTION = 24;

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