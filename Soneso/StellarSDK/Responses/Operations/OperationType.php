<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;

class OperationType
{
    public const CREATE_ACCOUNT = 0;
    public const PAYMENT = 1;
    public const PATH_PAYMENT = 2;
    public const MANAGE_SELL_OFFER = 3;
    public const CREATE_PASSIVE_SELL_OFFER = 4;
    public const SET_OPTIONS = 5;
    public const CHANGE_TRUST = 6;
    public const ALLOW_TRUST = 7;
    public const ACCOUNT_MERGE = 8;
    public const INFLATION = 9;
    public const MANAGE_DATA = 10;
    public const BUMP_SEQUENCE = 11;
    public const MANAGE_BUY_OFFER = 12;
    public const PATH_PAYMENT_STRICT_SEND = 13;
    public const CREATE_CLAIMABLE_BALANCE = 14;
    public const CLAIM_CLAIMABLE_BALANCE = 15;
    public const BEGIN_SPONSORING_FUTURE_RESERVES = 16;
    public const END_SPONSORING_FUTURE_RESERVES = 17;
    public const REVOKE_SPONSORSHIP = 18;
    public const CLAWBACK = 19;
    public const CLAWBACK_CLAIMABLE_BALANCE = 20;
    public const SET_TRUSTLINE_FLAGS = 21;
    public const LIQUIDITY_POOL_DEPOSIT = 22;
    public const LIQUIDITY_POOL_WITHDRAW = 23;
    public const INVOKE_HOST_FUNCTION = 24;
    public const BUMP_FOOTPRINT_EXPIRATION = 25;
    public const RESTORE_FOOTPRINT = 26;
}