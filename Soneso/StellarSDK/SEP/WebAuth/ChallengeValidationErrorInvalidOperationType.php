<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\SEP\WebAuth;

/**
 * Exception thrown when the challenge transaction contains operations other than ManageData.
 *
 * Thrown when any operation in the challenge transaction is not of type MANAGE_DATA. SEP-10
 * strictly requires all operations in the challenge to be ManageData operations. This prevents
 * malicious servers from embedding executable operations that could harm the client's account.
 *
 * Security Implications:
 * This validation is critical for preventing malicious transaction execution. If a challenge
 * contained payment or other executable operations, signing it could authorize unintended
 * transfers or account modifications. By restricting to ManageData operations only, SEP-10
 * ensures that signing a challenge cannot execute any operations on the Stellar network,
 * as the transaction has sequence number 0 and cannot be submitted.
 *
 * Common Scenarios:
 * - Malicious server attempts to embed Payment operation in challenge
 * - Server misconfiguration includes SetOptions or other executable operations
 * - Attack attempt to trick client into signing fund transfer
 * - Challenge contains CreateAccount or other account-modifying operations
 *
 * @package Soneso\StellarSDK\SEP\WebAuth
 * @see https://github.com/stellar/stellar-protocol/blob/v3.4.1/ecosystem/sep-0010.md#challenge SEP-10 Operation Requirements
 */
class ChallengeValidationErrorInvalidOperationType extends \ErrorException
{

}