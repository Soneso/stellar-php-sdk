<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

use Exception;

/**
 * Exception thrown when endpoint requires authentication but no JWT token provided.
 *
 * Indicates that the requested operation requires SEP-10 authentication, but the
 * request did not include a valid JWT token. Client should authenticate via SEP-10
 * and retry the request with the obtained token.
 *
 * @package Soneso\StellarSDK\SEP\TransferServerService
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md SEP-06 Specification
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0010.md SEP-10 Authentication
 */
class AuthenticationRequiredException extends Exception
{

}