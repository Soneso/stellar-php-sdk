<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

/**
 * Fee distribution statistics for transaction inclusion.
 *
 * @package Soneso\StellarSDK\Soroban\Responses
 */
class InclusionFee
{
    /**
     * @param string $max Maximum fee
     * @param string $min Minimum fee
     * @param string $mode Fee value which occurs most often
     * @param string $p10 10th nearest-rank fee percentile
     * @param string $p20 20th nearest-rank fee percentile
     * @param string $p30 30th nearest-rank fee percentile
     * @param string $p40 40th nearest-rank fee percentile
     * @param string $p50 50th nearest-rank fee percentile
     * @param string $p60 60th nearest-rank fee percentile
     * @param string $p70 70th nearest-rank fee percentile
     * @param string $p80 80th nearest-rank fee percentile
     * @param string $p90 90th nearest-rank fee percentile
     * @param string $p95 95th nearest-rank fee percentile
     * @param string $p99 99th nearest-rank fee percentile
     * @param string $transactionCount Number of transactions in the distribution
     * @param int $ledgerCount Number of consecutive ledgers forming the distribution
     */
    public function __construct(
        public string $max,
        public string $min,
        public string $mode,
        public string $p10,
        public string $p20,
        public string $p30,
        public string $p40,
        public string $p50,
        public string $p60,
        public string $p70,
        public string $p80,
        public string $p90,
        public string $p95,
        public string $p99,
        public string $transactionCount,
        public int $ledgerCount,
    )
    {
    }

    /**
     * Creates an instance from JSON-RPC response data
     *
     * @param array<string,mixed> $json The JSON response data
     * @return static The created instance
     */
    public static function fromJson(array $json): InclusionFee
    {
        return new InclusionFee(
            $json['max'],
            $json['min'],
            $json['mode'],
            $json['p10'],
            $json['p20'],
            $json['p30'],
            $json['p40'],
            $json['p50'],
            $json['p60'],
            $json['p70'],
            $json['p80'],
            $json['p90'],
            $json['p95'],
            $json['p99'],
            $json['transactionCount'],
            $json['ledgerCount'],
        );
    }

}