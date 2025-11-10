<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuth;

/**
 * Exception thrown when the challenge transaction contains an invalid memo type.
 *
 * Thrown when the challenge transaction includes a memo that is not of type MEMO_ID. SEP-10
 * only supports ID memos for distinguishing users of shared/pooled accounts. Other memo types
 * (MEMO_TEXT, MEMO_HASH, MEMO_RETURN) are not permitted in challenge transactions.
 *
 * Security Implications:
 * Restricting memo types to MEMO_ID ensures consistent user identification across shared accounts
 * and prevents potential confusion or injection attacks through memo manipulation. Only ID memos
 * provide the deterministic, numeric identification required for secure account scoping.
 *
 * Common Scenarios:
 * - Server incorrectly generates challenge with MEMO_TEXT instead of MEMO_ID
 * - Attempt to use MEMO_HASH for user identification (unsupported)
 * - Malformed memo type in the challenge transaction
 * - Client requesting authentication with non-ID memo type
 *
 * @package Soneso\StellarSDK\SEP\WebAuth
 * @see https://github.com/stellar/stellar-protocol/blob/v3.4.1/ecosystem/sep-0010.md#memos SEP-10 Memo Requirements
 */
class ChallengeValidationErrorInvalidMemoType extends \ErrorException
{

}