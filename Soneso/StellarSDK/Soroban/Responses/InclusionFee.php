<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

class InclusionFee
{
    /**
     * @var string $max Maximum fee
     */
    public string $max;
    /**
     * @var string $min Minimum fee
     */
    public string $min;
    /**
     * @var string $mode Fee value which occurs the most often
     */
    public string $mode;
    /**
     * @var string $p10 10th nearest-rank fee percentile
     */
    public string $p10;
    /**
     * @var string $p20 20th nearest-rank fee percentile
     */
    public string $p20;
    /**
     * @var string $p30 30th nearest-rank fee percentile
     */
    public string $p30;
    /**
     * @var string $p40 40th nearest-rank fee percentile
     */
    public string $p40;
    /**
     * @var string $p50 50th nearest-rank fee percentile
     */
    public string $p50;
    /**
     * @var string $p60 60th nearest-rank fee percentile
     */
    public string $p60;
    /**
     * @var string $p70 70th nearest-rank fee percentile
     */
    public string $p70;
    /**
     * @var string $p80 80th nearest-rank fee percentile
     */
    public string $p80;
    /**
     * @var string $p90 90th nearest-rank fee percentile
     */
    public string $p90;
    /**
     * @var string $p95 95th nearest-rank fee percentile
     */
    public string $p95;
    /**
     * @var string $p99 99th nearest-rank fee percentile
     */
    public string $p99;
    /**
     * @var string $transactionCount How many transactions are part of the distribution
     */
    public string $transactionCount;
    /**
     * @var int $ledgerCount How many consecutive ledgers form the distribution
     */
    public int $ledgerCount;

    /**
     * @param string $max Maximum fee
     * @param string $min Minimum fee
     * @param string $mode Fee value which occurs the most often
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
     * @param string $transactionCount How many transactions are part of the distribution
     * @param int $ledgerCount How many consecutive ledgers form the distribution
     */
    public function __construct(
        string $max,
        string $min,
        string $mode,
        string $p10,
        string $p20,
        string $p30,
        string $p40,
        string $p50,
        string $p60,
        string $p70,
        string $p80,
        string $p90,
        string $p95,
        string $p99,
        string $transactionCount,
        int $ledgerCount,
    )
    {
        $this->max = $max;
        $this->min = $min;
        $this->mode = $mode;
        $this->p10 = $p10;
        $this->p20 = $p20;
        $this->p30 = $p30;
        $this->p40 = $p40;
        $this->p50 = $p50;
        $this->p60 = $p60;
        $this->p70 = $p70;
        $this->p80 = $p80;
        $this->p90 = $p90;
        $this->p95 = $p95;
        $this->p99 = $p99;
        $this->transactionCount = $transactionCount;
        $this->ledgerCount = $ledgerCount;
    }

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