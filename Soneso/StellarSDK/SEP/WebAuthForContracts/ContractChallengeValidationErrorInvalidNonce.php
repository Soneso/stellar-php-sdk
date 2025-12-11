<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuthForContracts;

/**
 * Exception thrown when a contract challenge has inconsistent or invalid nonce values.
 *
 * This exception is thrown when:
 * - The nonce argument is not consistent across all authorization entries
 * - The nonce is missing from the function arguments
 * - The nonce format is invalid
 *
 * Security Impact:
 * Critical security check. The nonce provides replay protection by ensuring each challenge
 * is unique and can only be used once. Inconsistent or missing nonces could allow replay
 * attacks where an old signed challenge is reused for unauthorized authentication.
 *
 * @package Soneso\StellarSDK\SEP\WebAuthForContracts
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0045.md SEP-45 Replay Prevention
 */
class ContractChallengeValidationErrorInvalidNonce extends ContractChallengeValidationError
{

}
