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
    const BUMP_FOOTPRINT_EXPIRATION = 25;
    const RESTORE_FOOTPRINT = 26;

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

    public static function CREATE_ACCOUNT() : XdrOperationType {
        return new XdrOperationType(XdrOperationType::CREATE_ACCOUNT);
    }

    public static function PAYMENT() : XdrOperationType {
        return new XdrOperationType(XdrOperationType::PAYMENT);
    }

    public static function PATH_PAYMENT_STRICT_RECEIVE() : XdrOperationType {
        return new XdrOperationType(XdrOperationType::PATH_PAYMENT_STRICT_RECEIVE);
    }

    public static function MANAGE_SELL_OFFER() : XdrOperationType {
        return new XdrOperationType(XdrOperationType::MANAGE_SELL_OFFER);
    }

    public static function CREATE_PASSIVE_SELL_OFFER() : XdrOperationType {
        return new XdrOperationType(XdrOperationType::CREATE_PASSIVE_SELL_OFFER);
    }

    public static function SET_OPTIONS() : XdrOperationType {
        return new XdrOperationType(XdrOperationType::SET_OPTIONS);
    }

    public static function CHANGE_TRUST() : XdrOperationType {
        return new XdrOperationType(XdrOperationType::CHANGE_TRUST);
    }

    public static function ALLOW_TRUST() : XdrOperationType {
        return new XdrOperationType(XdrOperationType::ALLOW_TRUST);
    }

    public static function ACCOUNT_MERGE() : XdrOperationType {
        return new XdrOperationType(XdrOperationType::ACCOUNT_MERGE);
    }

    public static function INFLATION() : XdrOperationType {
        return new XdrOperationType(XdrOperationType::INFLATION);
    }

    public static function MANAGE_DATA() : XdrOperationType {
        return new XdrOperationType(XdrOperationType::MANAGE_DATA);
    }

    public static function BUMP_SEQUENCE() : XdrOperationType {
        return new XdrOperationType(XdrOperationType::BUMP_SEQUENCE);
    }

    public static function MANAGE_BUY_OFFER() : XdrOperationType {
        return new XdrOperationType(XdrOperationType::MANAGE_BUY_OFFER);
    }

    public static function PATH_PAYMENT_STRICT_SEND() : XdrOperationType {
        return new XdrOperationType(XdrOperationType::PATH_PAYMENT_STRICT_SEND);
    }

    public static function CREATE_CLAIMABLE_BALANCE() : XdrOperationType {
        return new XdrOperationType(XdrOperationType::CREATE_CLAIMABLE_BALANCE);
    }

    public static function CLAIM_CLAIMABLE_BALANCE() : XdrOperationType {
        return new XdrOperationType(XdrOperationType::CLAIM_CLAIMABLE_BALANCE);
    }

    public static function BEGIN_SPONSORING_FUTURE_RESERVES() : XdrOperationType {
        return new XdrOperationType(XdrOperationType::BEGIN_SPONSORING_FUTURE_RESERVES);
    }

    public static function END_SPONSORING_FUTURE_RESERVES() : XdrOperationType {
        return new XdrOperationType(XdrOperationType::END_SPONSORING_FUTURE_RESERVES);
    }

    public static function REVOKE_SPONSORSHIP() : XdrOperationType {
        return new XdrOperationType(XdrOperationType::REVOKE_SPONSORSHIP);
    }

    public static function CLAWBACK() : XdrOperationType {
        return new XdrOperationType(XdrOperationType::CLAWBACK);
    }

    public static function CLAWBACK_CLAIMABLE_BALANCE() : XdrOperationType {
        return new XdrOperationType(XdrOperationType::CLAWBACK_CLAIMABLE_BALANCE);
    }

    public static function SET_TRUST_LINE_FLAGS() : XdrOperationType {
        return new XdrOperationType(XdrOperationType::SET_TRUST_LINE_FLAGS);
    }

    public static function LIQUIDITY_POOL_DEPOSIT() : XdrOperationType {
        return new XdrOperationType(XdrOperationType::LIQUIDITY_POOL_DEPOSIT);
    }

    public static function LIQUIDITY_POOL_WITHDRAW() : XdrOperationType {
        return new XdrOperationType(XdrOperationType::LIQUIDITY_POOL_WITHDRAW);
    }

    public static function INVOKE_HOST_FUNCTION() : XdrOperationType {
        return new XdrOperationType(XdrOperationType::INVOKE_HOST_FUNCTION);
    }

    public static function BUMP_FOOTPRINT_EXPIRATION() : XdrOperationType {
        return new XdrOperationType(XdrOperationType::BUMP_FOOTPRINT_EXPIRATION);
    }

    public static function RESTORE_FOOTPRINT() : XdrOperationType {
        return new XdrOperationType(XdrOperationType::RESTORE_FOOTPRINT);
    }
}