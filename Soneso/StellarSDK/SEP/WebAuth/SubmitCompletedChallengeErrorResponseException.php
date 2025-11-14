<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuth;

/**
 * Exception thrown when submitting the signed challenge returns an error response.
 *
 * Thrown when the authentication server responds with an error after receiving the signed
 * challenge transaction. This typically indicates validation failures on the server side,
 * such as invalid signatures, insufficient signing weight, or other authentication requirements
 * not being met. The exception message contains the error details from the server response.
 *
 * Security Implications:
 * Server-side validation errors indicate the authentication attempt failed security checks.
 * Common causes include insufficient signature weight, incorrect signers, or attempts to
 * authenticate with signatures that don't meet the server's threshold requirements. These
 * errors prevent unauthorized access when the client cannot prove adequate control of the
 * account according to the server's policy.
 *
 * Common Scenarios:
 * - Signed challenge missing required signatures
 * - Signature weight does not meet server's threshold requirements
 * - Invalid signatures from unauthorized signers
 * - Challenge transaction modified after signing
 * - Server-side validation detecting potential attack or anomaly
 * - Account doesn't exist and server requires existing accounts
 * - Client domain signature missing when required
 *
 * @package Soneso\StellarSDK\SEP\WebAuth
 * @see https://github.com/stellar/stellar-protocol/blob/v3.4.1/ecosystem/sep-0010.md#token SEP-10 Token Endpoint
 */
class SubmitCompletedChallengeErrorResponseException extends \ErrorException
{

}