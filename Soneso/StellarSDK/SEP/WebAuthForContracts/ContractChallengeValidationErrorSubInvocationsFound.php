<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuthForContracts;

/**
 * Exception thrown when a contract challenge authorization entry contains sub-invocations.
 *
 * This exception is thrown when an authorization entry's rootInvocation contains any
 * sub-invocations. Per SEP-45, authorization entries for web authentication must not
 * have sub-invocations as this could authorize additional unintended contract calls.
 *
 * Security Impact:
 * Critical security check. Sub-invocations could authorize the contract to perform
 * additional operations beyond authentication verification. Always reject challenges
 * with sub-invocations to prevent unauthorized contract interactions.
 *
 * @package Soneso\StellarSDK\SEP\WebAuthForContracts
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0045.md SEP-45 Challenge Validation
 */
class ContractChallengeValidationErrorSubInvocationsFound extends ContractChallengeValidationError
{

}
