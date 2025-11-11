<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Requests;

/**
 * Part of the getEvents request parameters.
 *
 * Example:
 * ```php
 * // Match any first segment, specific symbol in second segment
 * $topicFilter = new TopicFilter([
 *     "*",  // Wildcard matches any value in first topic segment
 *     XdrSCVal::forSymbol("increment")->toBase64Xdr()  // Exact match for second segment
 * ]);
 * ```
 *
 * @see TopicFilters
 * @see https://developers.stellar.org/network/soroban-rpc/api-reference/methods/getEvents
 * @package Soneso\StellarSDK\Soroban\Requests
 */
class TopicFilter
{
    /**
     * @var array<string> $segmentMatchers For an exact segment match, a string containing a base64-encoded ScVal.
     * For a wildcard single-segment match, the string "*", matches exactly one segment.
     */
    public array $segmentMatchers;

    /**
     * Constructor.
     *
     * @param array<string> $segmentMatchers For an exact segment match, a string containing a base64-encoded ScVal.
     *  For a wildcard single-segment match, the string "*", matches exactly one segment.
     */
    public function __construct(array $segmentMatchers)
    {
        $this->segmentMatchers = $segmentMatchers;
    }

    /**
     * Builds and returns the request parameters array for the RPC API call.
     *
     * @return array<string, mixed> The request parameters formatted for Soroban RPC
     */
    public function getRequestParams() : array {
        return $this->segmentMatchers;
    }

    /**
     * Returns the segment matchers array.
     *
     * @return array<string> Array of segment matchers (base64-encoded ScVal or "*" wildcards)
     */
    public function getSegmentMatchers(): array
    {
        return $this->segmentMatchers;
    }
}