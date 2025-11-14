<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuth;

/**
 * Base exception class for SEP-10 challenge transaction validation failures.
 *
 * Thrown when the challenge transaction received from the authentication server fails general
 * validation checks that don't fall into specific validation error categories. This includes
 * issues such as invalid transaction structure, invalid envelope type, or unsupported transaction
 * features.
 *
 * Security Implications:
 * Challenge validation is critical for preventing authentication bypass attacks. A malformed or
 * manipulated challenge could allow unauthorized access to protected services. All challenge
 * validation failures should be treated as potential security threats and properly logged.
 *
 * Common Scenarios:
 * - Invalid transaction envelope type (not ENVELOPE_TYPE_TX)
 * - Zero operations in the challenge transaction
 * - Invalid operation type in the transaction structure
 * - Malformed XDR data in the challenge
 *
 * @package Soneso\StellarSDK\SEP\WebAuth
 * @see https://github.com/stellar/stellar-protocol/blob/v3.4.1/ecosystem/sep-0010.md#challenge SEP-10 Challenge Specification
 */
class ChallengeValidationError extends \ErrorException
{

}