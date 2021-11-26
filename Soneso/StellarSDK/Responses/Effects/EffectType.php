<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Effects;

class EffectType
{
    public const ACCOUNT_CREATED = 0;
    public const ACCOUNT_REMOVED = 1;
    public const ACCOUNT_CREDITED = 2;
    public const ACCOUNT_DEBITED = 3;
    public const ACCOUNT_THRESHOLDS_UPDATED = 4;
    public const ACCOUNT_HOME_DOMAIN_UPDATED = 5;
    public const ACCOUNT_FLAGS_UPDATED = 6;
    public const ACCOUNT_INFLATION_DESTINATION_UPDATED = 7;
    public const SIGNER_CREATED = 8;
    public const SIGNER_REMOVED = 9;
    public const SIGNER_UPDATED = 10;

    public const TRUSTLINE_CREATED = 20;
    public const TRUSTLINE_REMOVED = 21;
    public const TRUSTLINE_UPDATED = 22;
    public const TRUSTLINE_AUTHORIZED = 23;
    public const TRUSTLINE_DEAUTHORIZED = 24;
    public const TRUSTLINE_AUTHORIZED_TO_MAINTAIN_LIABILITIES = 25;
    public const TRUSTLINE_FLAGS_UPDATED = 26;

    public const OFFER_CREATED = 30;
    public const OFFER_REMOVED = 31;
    public const OFFER_UPDATED = 32;
    public const TRADE = 33;

    public const DATA_CREATED = 40;
    public const DATA_REMOVED = 41;
    public const DATA_UPDATED = 42;
    public const SEQUENCE_BUMPED = 43;

    public const CLAIMABLE_BALANCE_CREATED = 50;
    public const CLAIMABLE_BALANCE_CLAIMANT_CREATED = 51;
    public const CLAIMABLE_BALANCE_CLAIMED = 52;

    public const ACCOUNT_SPONSORSHIP_CREATED = 60;
    public const ACCOUNT_SPONSORSHIP_UPDATED = 61;
    public const ACCOUNT_SPONSORSHIP_REMOVED = 62;
    public const TRUSTLINE_SPONSORSHIP_CREATED = 63;
    public const TRUSTLINE_SPONSORSHIP_UPDATED = 64;
    public const TRUSTLINE_SPONSORSHIP_REMOVED = 65;
    public const DATA_SPONSORSHIP_CREATED = 66;
    public const DATA_SPONSORSHIP_UPDATED = 67;
    public const DATA_SPONSORSHIP_REMOVED = 68;
    public const CLAIMABLE_BALANCE_SPONSORSHIP_CREATED = 69;
    public const CLAIMABLE_BALANCE_SPONSORSHIP_UPDATED = 70;
    public const CLAIMABLE_BALANCE_SPONSORSHIP_REMOVED = 71;
    public const SIGNER_SPONSORSHIP_CREATED = 72;
    public const SIGNER_SPONSORSHIP_UPDATED = 73;
    public const SIGNER_SPONSORSHIP_REMOVED = 74;

    public const CLAIMABLE_BALANCE_CLAWED_BACK = 80;

    public const LIQUIDITY_POOL_DEPOSITED = 90;
    public const LIQUIDITY_POOL_WITHDREW = 91;
    public const LIQUIDITY_POOL_TRADE = 92;
    public const LIQUIDITY_POOL_CREATED = 93;
    public const LIQUIDITY_POOL_REMOVED = 94;
    public const LIQUIDITY_POOL_REVOKED = 95;

}