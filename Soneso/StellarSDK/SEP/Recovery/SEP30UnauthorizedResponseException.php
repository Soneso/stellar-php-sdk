<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Recovery;

use Exception;

/**
 * Exception thrown when the SEP-30 recovery server returns HTTP 401 Unauthorized.
 *
 * This indicates authentication failure, such as:
 * - Missing JWT token in the Authorization header
 * - Invalid or expired JWT token
 * - JWT token does not prove ownership of the account (for registration/updates)
 * - JWT token does not authenticate as a registered identity (for signing operations)
 * - JWT signature verification failed
 *
 * Clients must obtain a valid SEP-10 JWT token or external authentication provider
 * token before making authenticated requests. Ensure tokens are transmitted over
 * HTTPS and not expired.
 *
 * @package Soneso\StellarSDK\SEP\Recovery
 * @see https://github.com/stellar/stellar-protocol/blob/v0.8.1/ecosystem/sep-0030.md#errors
 * @see RecoveryService
 * @see https://github.com/stellar/stellar-protocol/blob/v3.4.1/ecosystem/sep-0010.md SEP-10 v3.4.1 Authentication
 */
class SEP30UnauthorizedResponseException extends Exception
{

}