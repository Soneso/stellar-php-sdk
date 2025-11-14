<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

/**
 * Account flags control authorization and trust settings for Stellar accounts.
 *
 * These flags are used with SetOptions operations to configure account behavior
 * regarding trustlines and asset authorization. Issuers can use these flags to
 * control how their custom assets can be held and transferred.
 *
 * @package Soneso\StellarSDK
 * @see SetOptionsOperation
 * @link https://developers.stellar.org Stellar developer docs
 */
class AccountFlag
{
    /**
     * Authorization required flag (0x1).
     *
     * Requires the issuing account to give explicit authorization for accounts
     * to hold its custom assets. When set, trustlines default to unauthorized
     * and require approval before they can hold or receive the asset.
     *
     * @var int
     */
    const AUTH_REQUIRED_FLAG = 1;

    /**
     * Authorization revocable flag (0x2).
     *
     * Allows the issuing account to revoke authorization for its custom assets
     * at any time. Once revoked, the trustline cannot be used to send, receive,
     * or maintain offers for the asset. The asset balance becomes frozen.
     *
     * @var int
     */
    const AUTH_REVOCABLE_FLAG = 2;

    /**
     * Authorization immutable flag (0x4).
     *
     * Permanently prevents the account from changing any authorization flags.
     * Once set, AUTH_REQUIRED_FLAG and AUTH_REVOCABLE_FLAG cannot be changed.
     * This flag itself cannot be unset once applied, ensuring the account's
     * authorization behavior is fixed forever.
     *
     * @var int
     */
    const AUTH_IMMUTABLE_FLAG = 4;

    /**
     * Clawback enabled flag (0x8).
     *
     * Allows the issuing account to claw back (burn) its custom assets from
     * any account holding them. This enables the issuer to revoke assets after
     * they have been distributed, which may be required for regulated assets.
     *
     * @var int
     * @since Protocol 17
     */
    const AUTH_CLAWBACK_ENABLED_FLAG = 8;
}