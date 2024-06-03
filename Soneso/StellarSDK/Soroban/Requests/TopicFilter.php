<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Requests;

/**
 * Part of the getEvents request parameters.
 * https://developers.stellar.org/network/soroban-rpc/api-reference/methods/getEvents
 * example: $topicFilter = new TopicFilter(["*", XdrSCVal::forSymbol("increment")->toBase64Xdr()]);
 */
class TopicFilter
{
    /**
     * @var array<String> $segmentMatchers For an exact segment match, a string containing a base64-encoded ScVal.
     * For a wildcard single-segment match, the string "*", matches exactly one segment.
     */
    public array $segmentMatchers;

    /**
     * Constructor.
     *
     * @param array<String> $segmentMatchers For an exact segment match, a string containing a base64-encoded ScVal.
     *  For a wildcard single-segment match, the string "*", matches exactly one segment.
     */
    public function __construct(array $segmentMatchers)
    {
        $this->segmentMatchers = $segmentMatchers;
    }

    public function getRequestParams() : array {
        return $this->segmentMatchers;
    }
}