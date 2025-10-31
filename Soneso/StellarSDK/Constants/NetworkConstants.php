<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Constants;

/**
 * HTTP Status Code Constants
 *
 * This class contains HTTP status codes used throughout the SDK for
 * network request handling and error detection. These constants ensure
 * consistent handling of HTTP responses across different API interactions.
 *
 * References:
 * - RFC 7231 (HTTP/1.1): https://tools.ietf.org/html/rfc7231
 * - RFC 6585 (Additional HTTP Status Codes): https://tools.ietf.org/html/rfc6585
 *
 * Note: This class cannot be instantiated. All constants are static and
 * should be accessed directly via the class name.
 */
final class NetworkConstants
{
    // Private constructor to prevent instantiation
    private function __construct() {}

    // ============================================================================
    // HTTP STATUS CODE CONSTANTS
    // ============================================================================
    // HTTP status codes used in network request handling and error detection.
    //
    // Reference: RFC 7231 (HTTP/1.1 Semantics and Content)
    // @see https://tools.ietf.org/html/rfc7231

    /**
     * HTTP 200 OK status code.
     *
     * Indicates that the request succeeded. The meaning of success depends
     * on the HTTP method:
     * - GET: Resource fetched successfully
     * - POST: Resource created or action completed
     *
     * This is the standard success response code.
     *
     * @see https://tools.ietf.org/html/rfc7231#section-6.3.1
     */
    public const HTTP_OK = 200;

    /**
     * HTTP 400 Bad Request status code.
     *
     * Indicates that the server cannot process the request due to client
     * error (e.g., malformed request syntax, invalid request message framing,
     * or deceptive request routing).
     *
     * Common causes:
     * - Invalid transaction format
     * - Missing required parameters
     * - Malformed JSON
     *
     * @see https://tools.ietf.org/html/rfc7231#section-6.5.1
     */
    public const HTTP_BAD_REQUEST = 400;

    /**
     * HTTP 403 Forbidden status code.
     *
     * Indicates that the server understood the request but refuses to
     * authorize it. Unlike 401, authentication will not help.
     *
     * Common causes:
     * - Insufficient permissions
     * - Rate limiting
     * - IP blacklisting
     *
     * @see https://tools.ietf.org/html/rfc7231#section-6.5.3
     */
    public const HTTP_FORBIDDEN = 403;

    /**
     * HTTP 404 Not Found status code.
     *
     * Indicates that the server cannot find the requested resource.
     *
     * Common causes:
     * - Account doesn't exist
     * - Transaction not found
     * - Invalid endpoint
     *
     * @see https://tools.ietf.org/html/rfc7231#section-6.5.4
     */
    public const HTTP_NOT_FOUND = 404;

    /**
     * HTTP 409 Conflict status code.
     *
     * Indicates that the request could not be completed due to a conflict
     * with the current state of the target resource.
     *
     * Common causes:
     * - Transaction already submitted
     * - Resource version conflict
     * - State transition not allowed
     *
     * @see https://tools.ietf.org/html/rfc7231#section-6.5.8
     */
    public const HTTP_CONFLICT = 409;

    /**
     * HTTP 500 Internal Server Error status code.
     *
     * Indicates that the server encountered an unexpected condition that
     * prevented it from fulfilling the request. This is a generic error
     * message when no more specific message is suitable.
     *
     * Common causes:
     * - Server misconfiguration
     * - Unhandled exception
     * - Database errors
     *
     * @see https://tools.ietf.org/html/rfc7231#section-6.6.1
     */
    public const HTTP_INTERNAL_SERVER_ERROR = 500;

    /**
     * HTTP 503 Service Unavailable status code.
     *
     * Indicates that the server is not ready to handle the request. This is
     * typically a temporary condition, such as:
     * - Server maintenance
     * - Server overload
     * - Network issues
     *
     * The response may include a Retry-After header indicating when the
     * client should retry.
     *
     * @see https://tools.ietf.org/html/rfc7231#section-6.6.4
     */
    public const HTTP_SERVICE_UNAVAILABLE = 503;
}