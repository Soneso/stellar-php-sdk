<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuth;

/**
 * Exception thrown when operation source accounts in the challenge transaction are invalid.
 *
 * Thrown when operations in the challenge do not have the required source accounts according
 * to SEP-10 rules: the first operation must have the client account as source, subsequent
 * operations must have either the server account or (for client_domain) the client domain
 * account as source. This validates proper operation attribution.
 *
 * Security Implications:
 * Source account validation prevents operation substitution attacks and ensures operations
 * are attributed to the correct parties. The first operation must prove control of the client
 * account, while subsequent operations (like web_auth_domain) must be attributed to the server
 * to prevent clients from manipulating authentication metadata. Incorrect source accounts could
 * allow authentication bypass or domain confusion attacks.
 *
 * Common Scenarios:
 * - First operation has wrong source account (not the client account being authenticated)
 * - web_auth_domain operation has client account as source instead of server account
 * - client_domain operation has wrong source account (not the client domain account)
 * - Operation missing source account (null source)
 * - Server misconfiguration attributing operations incorrectly
 *
 * @package Soneso\StellarSDK\SEP\WebAuth
 * @see https://github.com/stellar/stellar-protocol/blob/v3.4.1/ecosystem/sep-0010.md#challenge SEP-10 Operation Source Accounts
 */
class ChallengeValidationErrorInvalidSourceAccount extends \ErrorException
{

}