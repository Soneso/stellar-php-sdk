<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

use Exception;

/**
 * Exception thrown when a SEP-24 request requires authentication but none was provided.
 *
 * This exception is raised when the anchor service returns HTTP 403 Forbidden, indicating that
 * the request requires authentication via SEP-10 or SEP-45. All endpoints in SEP-24 may require
 * authentication at the anchor's discretion, even if authentication is listed as optional in
 * the specification.
 *
 * HTTP Status: 403 Forbidden
 *
 * Client Handling:
 * - Obtain a valid SEP-10 JWT token via authentication flow
 * - Retry the request with the JWT in the Authorization header
 * - Ensure the token has not expired
 * - Verify the token has appropriate permissions for the requested operation
 *
 * @package Soneso\StellarSDK\SEP\Interactive
 * @see https://github.com/stellar/stellar-protocol/blob/v3.8.0/ecosystem/sep-0024.md SEP-24 Hosted Deposit and Withdrawal
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0010.md SEP-10 Stellar Authentication
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0045.md SEP-45 Stellar Smart Accounts Authentication
 */
class SEP24AuthenticationRequiredException extends Exception
{

}