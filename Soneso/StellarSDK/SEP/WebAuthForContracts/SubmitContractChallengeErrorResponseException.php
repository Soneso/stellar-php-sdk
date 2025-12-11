<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuthForContracts;

/**
 * Exception thrown when submitting signed authorization entries returns an error.
 *
 * This exception is thrown when the POST request to the WEB_AUTH_FOR_CONTRACTS_ENDPOINT
 * with signed authorization entries succeeds (returns 200 or 400) but the response contains
 * an error field instead of a JWT token. This indicates the server validated the request
 * but rejected the authorization entries for authentication.
 *
 * Common reasons for submission errors:
 * - Invalid signatures on the client authorization entry
 * - Missing required signatures
 * - Signature threshold not met
 * - Nonce already used (replay attempt detected)
 * - Signature expiration ledger too old or in the past
 * - Authorization entries modified after being issued
 * - Contract account not authorized for authentication
 * - Client domain verification failed
 *
 * The error message from the server is included in the exception message and should
 * provide details about why the submission was rejected.
 *
 * @package Soneso\StellarSDK\SEP\WebAuthForContracts
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0045.md SEP-45 Token Endpoint
 */
class SubmitContractChallengeErrorResponseException extends \Exception
{

}
