<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Recovery;

use Exception;

/**
 * Exception thrown when the SEP-30 recovery server returns HTTP 404 Not Found.
 *
 * This indicates that the requested resource does not exist or is not accessible, such as:
 * - Account address not registered with the recovery server
 * - Signing address not recognized for the account
 * - Account deleted or removed from recovery server
 * - Authenticated identity does not have access to the requested account
 *
 * For GET requests, this typically means the account was never registered or has been deleted.
 * For signing requests, verify the signing address is one provided by the server during
 * registration or from a recent account details query.
 *
 * @package Soneso\StellarSDK\SEP\Recovery
 * @see https://github.com/stellar/stellar-protocol/blob/v0.8.1/ecosystem/sep-0030.md#errors
 * @see RecoveryService
 */
class SEP30NotFoundResponseException extends Exception
{

}