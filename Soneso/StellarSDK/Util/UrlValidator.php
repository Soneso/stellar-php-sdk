<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Util;

use InvalidArgumentException;

/**
 * Validates service URLs for security requirements.
 */
class UrlValidator
{
    /**
     * Validates that a service URL uses HTTPS.
     *
     * HTTP is only allowed for localhost and 127.0.0.1 to support local development.
     * All other URLs must use HTTPS to protect sensitive data in transit.
     *
     * @param string $url The service URL to validate
     * @throws InvalidArgumentException If the URL does not use HTTPS and is not localhost
     */
    public static function validateHttpsRequired(string $url): void
    {
        $scheme = strtolower(parse_url($url, PHP_URL_SCHEME) ?? '');

        if ($scheme === 'https') {
            return;
        }

        if ($scheme === 'http') {
            $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
            if ($host === 'localhost' || $host === '127.0.0.1' || $host === '[::1]') {
                return;
            }
        }

        throw new InvalidArgumentException(
            'Service URL must use HTTPS. HTTP is only allowed for localhost.'
        );
    }

    /**
     * Validates that a string is a safe domain name for URL construction.
     *
     * Rejects values containing path separators, traversal sequences, whitespace,
     * query/fragment delimiters, null bytes, or other characters not valid in hostnames.
     * Only allows alphanumeric characters, hyphens, dots, colons (for ports),
     * and square brackets (for IPv6 literals).
     *
     * @param string $domain The domain to validate
     * @throws InvalidArgumentException If the domain contains unsafe characters
     */
    public static function validateDomain(string $domain): void
    {
        if ($domain === '' || !preg_match('/\A[a-zA-Z0-9.\-:\[\]]+\z/', $domain)) {
            throw new InvalidArgumentException(
                "Invalid domain: contains unsafe characters"
            );
        }
    }

    /**
     * Validates that a string is safe to use as a URL path segment.
     *
     * Rejects values that contain path traversal sequences, path separators,
     * query/fragment delimiters, null bytes, or are empty.
     *
     * @param string $value The value to validate
     * @param string $paramName The parameter name for error messages
     * @throws InvalidArgumentException If the value is not safe for use in a URL path
     */
    public static function validatePathSegment(string $value, string $paramName): void
    {
        if ($value === '' || str_contains($value, '/') || str_contains($value, '\\')
            || str_contains($value, '..') || str_contains($value, "\0")
            || str_contains($value, '?') || str_contains($value, '#')) {
            throw new InvalidArgumentException(
                "Invalid value for '$paramName': contains unsafe characters for URL path"
            );
        }
    }
}
