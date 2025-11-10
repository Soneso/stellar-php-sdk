<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuth;

/**
 * Exception thrown when the challenge transaction sequence number is not zero.
 *
 * Thrown when the challenge transaction has a sequence number other than 0. SEP-10 requires
 * sequence number 0 to ensure the challenge transaction cannot be executed on the Stellar
 * network. This is a fundamental security requirement of the authentication protocol.
 *
 * Security Implications:
 * This is one of the most critical security validations in SEP-10. A sequence number of 0
 * makes the transaction invalid for execution on the Stellar network, ensuring that signing
 * the challenge cannot result in any blockchain state changes. If a malicious server provides
 * a challenge with a valid sequence number, signing it could authorize executable operations
 * that transfer funds or modify account settings. Always verify sequence number is exactly 0.
 *
 * Common Scenarios:
 * - Malicious server attempts to trick client into signing executable transaction
 * - Server implementation error using actual account sequence number
 * - Attack attempting to execute operations under guise of authentication
 * - Replay attack using previously valid transaction as challenge
 *
 * @package Soneso\StellarSDK\SEP\WebAuth
 * @see https://github.com/stellar/stellar-protocol/blob/v3.4.1/ecosystem/sep-0010.md#challenge SEP-10 Sequence Number Requirement
 */
class ChallengeValidationErrorInvalidSeqNr extends \ErrorException
{

}