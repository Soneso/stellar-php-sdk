<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\URIScheme;

use ErrorException;

/**
 * Exception thrown when SEP-7 URI validation or processing fails.
 *
 * This exception extends ErrorException and uses error codes to indicate
 * specific validation failures during SEP-7 URI processing. Each error code
 * corresponds to a specific step in the URI validation workflow.
 *
 * Error codes:
 * - 0 (invalidSignature): Cryptographic signature verification failed
 * - 1 (invalidOriginDomain): origin_domain is not a valid FQDN
 * - 2 (missingOriginDomain): origin_domain parameter not present in URI
 * - 3 (missingSignature): signature parameter not present in URI
 * - 4 (tomlNotFoundOrInvalid): stellar.toml file not found or malformed
 * - 5 (tomlSignatureMissing): URI_REQUEST_SIGNING_KEY not in stellar.toml
 *
 * Security Note: These error codes help identify potential security issues
 * or attacks. Always check error codes when handling URISchemeError exceptions.
 *
 * @package Soneso\StellarSDK\SEP\URIScheme
 */
class URISchemeError extends ErrorException
{
    /** Cryptographic signature verification failed (code 0) */
    const invalidSignature = 0;

    /** Origin domain is not a valid fully qualified domain name (code 1) */
    const invalidOriginDomain = 1;

    /** Required origin_domain parameter missing from URI (code 2) */
    const missingOriginDomain = 2;

    /** Required signature parameter missing from URI (code 3) */
    const missingSignature = 3;

    /** stellar.toml file not found at origin domain or contains invalid TOML (code 4) */
    const tomlNotFoundOrInvalid = 4;

    /** URI_REQUEST_SIGNING_KEY not present in stellar.toml (code 5) */
    const tomlSignatureMissing = 5;

    /**
     * Returns human-readable error message for this exception.
     *
     * Converts the error code into a descriptive error message string.
     *
     * @return string Error message with "URISchemeError: " prefix
     */
    public function toString() : string {
        return match ($this->code) {
            URISchemeError::invalidSignature => "URISchemeError: invalid Signature",
            URISchemeError::invalidOriginDomain => "URISchemeError: invalid Origin Domain",
            URISchemeError::missingOriginDomain => "URISchemeError: missing Origin Domain",
            URISchemeError::missingSignature => "URISchemeError: missing Signature",
            URISchemeError::tomlNotFoundOrInvalid => "URISchemeError: toml not found or invalid",
            URISchemeError::tomlSignatureMissing => "URISchemeError: Toml Signature Missing",
            default => "URISchemeError: unknown error",
        };
    }
}