<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuthForContracts;

/**
 * Exception thrown when the client's authorization entry is missing from the challenge.
 *
 * This exception is thrown when no authorization entry exists where credentials.address
 * matches the client's contract account. Per SEP-45, every challenge must include an
 * authorization entry for the client account that will be signed by the client.
 *
 * Security Impact:
 * High severity. Without a client authorization entry, the client cannot prove control
 * of their account. This indicates a malformed challenge that doesn't follow the SEP-45
 * protocol. Always reject such challenges.
 *
 * @package Soneso\StellarSDK\SEP\WebAuthForContracts
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0045.md SEP-45 Challenge Validation
 */
class ContractChallengeValidationErrorMissingClientEntry extends ContractChallengeValidationError
{

}
