<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuthForContracts;

/**
 * Exception thrown when a contract challenge has an invalid network_passphrase.
 *
 * This exception is thrown when the network_passphrase provided in the challenge response
 * does not match the expected network passphrase for the current network (testnet or pubnet).
 * This validation ensures the client is authenticating against the correct network and prevents
 * cross-network authentication attacks.
 *
 * Security Impact:
 * High security check. An incorrect network_passphrase indicates a network mismatch that could
 * lead to signatures being valid on an unintended network. This could enable replay attacks
 * where authentication credentials from testnet are used on pubnet, or vice versa. Always
 * verify the network_passphrase matches the expected network.
 *
 * @package Soneso\StellarSDK\SEP\WebAuthForContracts
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0045.md SEP-45 Challenge Validation
 */
class ContractChallengeValidationErrorInvalidNetworkPassphrase extends ContractChallengeValidationError
{

}
