<?php

namespace Soneso\StellarSDK\Responses\FeeStats;

/**
 * Represents the distribution of maximum fees submitted
 *
 * Contains statistical measures of max_fee values submitted with recent transactions including
 * min, max, mode, and various percentiles. All values are in stroops.
 *
 * @package Soneso\StellarSDK\Responses\FeeStats
 * @see FeeStatsResponse For the parent fee statistics
 * @since 1.0.0
 */
class MaxFeeResponse
{
    private string $max;
    private string $min;
    private string $mode;
    private string $p10;
    private string $p20;
    private string $p30;
    private string $p40;
    private string $p50;
    private string $p60;
    private string $p70;
    private string $p80;
    private string $p90;
    private string $p95;
    private string $p99;

    /**
     * Gets the maximum max_fee submitted
     *
     * @return string The maximum max_fee in stroops
     */
    public function getMax(): string
    {
        return $this->max;
    }

    /**
     * Gets the minimum max_fee submitted
     *
     * @return string The minimum max_fee in stroops
     */
    public function getMin(): string
    {
        return $this->min;
    }

    /**
     * Gets the mode max_fee submitted
     *
     * The most common max_fee value.
     *
     * @return string The mode max_fee in stroops
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * Gets the 10th percentile max_fee
     *
     * @return string The P10 max_fee in stroops
     */
    public function getP10(): string
    {
        return $this->p10;
    }

    /**
     * Gets the 20th percentile max_fee
     *
     * @return string The P20 max_fee in stroops
     */
    public function getP20(): string
    {
        return $this->p20;
    }

    /**
     * Gets the 30th percentile max_fee
     *
     * @return string The P30 max_fee in stroops
     */
    public function getP30(): string
    {
        return $this->p30;
    }

    /**
     * Gets the 40th percentile max_fee
     *
     * @return string The P40 max_fee in stroops
     */
    public function getP40(): string
    {
        return $this->p40;
    }

    /**
     * Gets the 50th percentile (median) max_fee
     *
     * @return string The P50 max_fee in stroops
     */
    public function getP50(): string
    {
        return $this->p50;
    }

    /**
     * Gets the 60th percentile max_fee
     *
     * @return string The P60 max_fee in stroops
     */
    public function getP60(): string
    {
        return $this->p60;
    }

    /**
     * Gets the 70th percentile max_fee
     *
     * @return string The P70 max_fee in stroops
     */
    public function getP70(): string
    {
        return $this->p70;
    }

    /**
     * Gets the 80th percentile max_fee
     *
     * @return string The P80 max_fee in stroops
     */
    public function getP80(): string
    {
        return $this->p80;
    }

    /**
     * Gets the 90th percentile max_fee
     *
     * @return string The P90 max_fee in stroops
     */
    public function getP90(): string
    {
        return $this->p90;
    }

    /**
     * Gets the 95th percentile max_fee
     *
     * @return string The P95 max_fee in stroops
     */
    public function getP95(): string
    {
        return $this->p95;
    }

    /**
     * Gets the 99th percentile max_fee
     *
     * @return string The P99 max_fee in stroops
     */
    public function getP99(): string
    {
        return $this->p99;
    }

    protected function loadFromJson(array $json) : void {
        if (isset($json['max'])) $this->max = $json['max'];
        if (isset($json['min'])) $this->min = $json['min'];
        if (isset($json['mode'])) $this->mode = $json['mode'];
        if (isset($json['p10'])) $this->p10 = $json['p10'];
        if (isset($json['p20'])) $this->p20 = $json['p20'];
        if (isset($json['p30'])) $this->p30 = $json['p30'];
        if (isset($json['p40'])) $this->p40 = $json['p40'];
        if (isset($json['p50'])) $this->p50 = $json['p50'];
        if (isset($json['p60'])) $this->p60 = $json['p60'];
        if (isset($json['p70'])) $this->p70 = $json['p70'];
        if (isset($json['p80'])) $this->p80 = $json['p80'];
        if (isset($json['p90'])) $this->p90 = $json['p90'];
        if (isset($json['p95'])) $this->p95 = $json['p95'];
        if (isset($json['p99'])) $this->p99 = $json['p99'];

    }

    public static function fromJson(array $json) : MaxFeeResponse {
        $result = new MaxFeeResponse();
        $result->loadFromJson($json);
        return $result;
    }
}