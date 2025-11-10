<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuth;

/**
 * Exception thrown when the challenge transaction memo value does not match the expected value.
 *
 * Thrown in two scenarios: (1) when a memo was expected but is missing from the challenge
 * transaction, or (2) when the memo value in the challenge does not match the memo value
 * requested by the client. This ensures that authentication is scoped to the correct user
 * within a shared/pooled account.
 *
 * Security Implications:
 * Memo validation prevents user impersonation within shared accounts. If memo values are not
 * strictly validated, a malicious actor could authenticate as one user but gain access to
 * another user's account data. This validation is critical for maintaining user isolation
 * in omnibus or pooled account scenarios.
 *
 * Common Scenarios:
 * - Client requested memo 12345 but challenge contains memo 67890
 * - Client requested authentication with memo but challenge has no memo
 * - Server incorrectly omits memo from challenge when one was requested
 * - Attempt to authenticate with wrong user ID in shared account
 *
 * @package Soneso\StellarSDK\SEP\WebAuth
 * @see https://github.com/stellar/stellar-protocol/blob/v3.4.1/ecosystem/sep-0010.md#memos SEP-10 Memo Validation
 */
class ChallengeValidationErrorInvalidMemoValue extends \ErrorException
{

}