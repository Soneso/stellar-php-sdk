<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Recovery;

use Exception;

/**
 * Exception thrown when the SEP-30 recovery server returns HTTP 409 Conflict.
 *
 * This indicates a resource state conflict, such as:
 * - Account already registered with the recovery server (attempting duplicate registration)
 * - Account update conflicts with existing state
 *
 * The most common scenario is attempting to register an account that has already been
 * registered. Clients should first check if an account exists using accountDetails()
 * before attempting registration, or use updateIdentitiesForAccount() to modify an
 * existing registration.
 *
 * @package Soneso\StellarSDK\SEP\Recovery
 * @see https://github.com/stellar/stellar-protocol/blob/v0.8.1/ecosystem/sep-0030.md#errors
 * @see RecoveryService::accountDetails()
 * @see RecoveryService::updateIdentitiesForAccount()
 */
class SEP30ConflictResponseException extends Exception
{

}