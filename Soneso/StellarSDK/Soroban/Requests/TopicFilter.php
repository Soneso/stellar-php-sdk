<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Requests;

/**
 * Part of the getEvents request parameters.
 * https://soroban.stellar.org/api/methods/getEvents
 * example: $topicFilter = new TopicFilter(["*", XdrSCVal::forSymbol("increment")->toBase64Xdr()]);
 */
class TopicFilter
{
    public array $segmentMatchers; // [string]

    public function __construct(array $segmentMatchers)
    {
        $this->segmentMatchers = $segmentMatchers;
    }

    public function getRequestParams() : array {
        return $this->segmentMatchers;
    }
}