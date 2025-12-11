<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuthForContracts;

/**
 * Exception thrown when the server's authorization entry has an invalid signature.
 *
 * This exception is thrown when the server's authorization entry (where credentials.address
 * matches the server account) does not have a valid signature from the server's signing key.
 * This validation ensures the challenge actually came from the legitimate authentication server.
 *
 * Security Impact:
 * Critical security check. The server signature proves the challenge originated from the
 * legitimate authentication server and not from an attacker. Without a valid server signature,
 * anyone could create fake challenges to capture client signatures. Always verify the server
 * signature before proceeding with authentication.
 *
 * @package Soneso\StellarSDK\SEP\WebAuthForContracts
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0045.md SEP-45 Challenge Validation
 */
class ContractChallengeValidationErrorInvalidServerSignature extends ContractChallengeValidationError
{

}
