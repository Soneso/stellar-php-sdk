<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuthForContracts;

/**
 * Exception thrown when a contract challenge has an invalid contract address.
 *
 * This exception is thrown when the contract_address in an authorization entry does not
 * match the WEB_AUTH_CONTRACT_ID from the server's stellar.toml. This validation ensures
 * that the authorization is for the correct web authentication contract and prevents
 * substitution attacks where an attacker might try to use a different contract.
 *
 * Security Impact:
 * Critical security check. If the contract address doesn't match, the challenge may be
 * for a different contract that could have malicious logic. Always reject such challenges.
 *
 * @package Soneso\StellarSDK\SEP\WebAuthForContracts
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0045.md SEP-45 Challenge Validation
 */
class ContractChallengeValidationErrorInvalidContractAddress extends ContractChallengeValidationError
{

}
