<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuthForContracts;

/**
 * Base exception class for SEP-45 contract challenge authorization entry validation failures.
 *
 * Thrown when the authorization entries received from the authentication server fail general
 * validation checks that don't fall into specific validation error categories. This includes
 * issues such as invalid authorization entry structure, invalid credentials, or unsupported
 * invocation patterns.
 *
 * Security Implications:
 * Authorization entry validation is critical for preventing authentication bypass attacks. A
 * malformed or manipulated challenge could allow unauthorized access to protected services.
 * All validation failures should be treated as potential security threats and properly logged.
 *
 * Common Scenarios:
 * - Invalid authorization entry structure
 * - Malformed XDR data in authorization entries
 * - Invalid credentials type
 * - General args validation failures
 *
 * @package Soneso\StellarSDK\SEP\WebAuthForContracts
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0045.md SEP-45 Specification
 */
class ContractChallengeValidationError extends \ErrorException
{

}
