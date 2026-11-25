<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuth;

/**
 * Exception thrown when the challenge for a muxed client account contains a memo.
 *
 * Thrown when the challenge transaction contains a memo while the client account is a muxed
 * account (M...). Muxed accounts and memos serve the same purpose (identifying users within
 * shared accounts) and are mutually exclusive. Using both simultaneously creates ambiguity
 * in user identification. A request that combines a memo with a muxed account throws
 * InvalidArgumentException before it is sent.
 *
 * Security Implications:
 * Allowing both memo and muxed account could create user identification ambiguity and potential
 * authentication bypass vulnerabilities. The memo embedded in a muxed account address and a
 * separate transaction memo could reference different users, leading to access control violations.
 * Enforcing mutual exclusivity ensures unambiguous user identification in shared account scenarios.
 *
 * Common Scenarios:
 * - Server generates challenge with memo for muxed account address
 * - Implementation error not detecting muxed account format
 *
 * @package Soneso\StellarSDK\SEP\WebAuth
 * @see https://github.com/stellar/stellar-protocol/blob/v3.4.1/ecosystem/sep-0010.md#muxed-accounts SEP-10 Muxed Account Requirements
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0023.md SEP-23 Muxed Accounts
 */
class ChallengeValidationErrorMemoAndMuxedAccount extends \ErrorException
{

}