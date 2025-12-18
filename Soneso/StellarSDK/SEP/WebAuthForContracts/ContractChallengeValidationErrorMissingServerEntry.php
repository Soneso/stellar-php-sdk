<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuthForContracts;

/**
 * Exception thrown when the server's authorization entry is missing from the challenge.
 *
 * This exception is thrown when no authorization entry exists where credentials.address
 * matches the server's signing account. Per SEP-45, every challenge must include a signed
 * authorization entry from the server to prove the challenge's authenticity.
 *
 * Security Impact:
 * Critical security check. Without a server authorization entry, there's no proof the
 * challenge came from the legitimate authentication server. This could indicate a fake
 * challenge created by an attacker to capture client signatures. Always reject challenges
 * without a server entry.
 *
 * @package Soneso\StellarSDK\SEP\WebAuthForContracts
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0045.md SEP-45 Challenge Validation
 */
class ContractChallengeValidationErrorMissingServerEntry extends ContractChallengeValidationError
{

}
