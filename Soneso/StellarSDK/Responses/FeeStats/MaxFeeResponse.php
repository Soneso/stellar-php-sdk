<?php

namespace Soneso\StellarSDK\Responses\FeeStats;

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
     * @return string
     */
    public function getMax(): string
    {
        return $this->max;
    }

    /**
     * @return string
     */
    public function getMin(): string
    {
        return $this->min;
    }

    /**
     * @return string
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * @return string
     */
    public function getP10(): string
    {
        return $this->p10;
    }

    /**
     * @return string
     */
    public function getP20(): string
    {
        return $this->p20;
    }

    /**
     * @return string
     */
    public function getP30(): string
    {
        return $this->p30;
    }

    /**
     * @return string
     */
    public function getP40(): string
    {
        return $this->p40;
    }

    /**
     * @return string
     */
    public function getP50(): string
    {
        return $this->p50;
    }

    /**
     * @return string
     */
    public function getP60(): string
    {
        return $this->p60;
    }

    /**
     * @return string
     */
    public function getP70(): string
    {
        return $this->p70;
    }

    /**
     * @return string
     */
    public function getP80(): string
    {
        return $this->p80;
    }

    /**
     * @return string
     */
    public function getP90(): string
    {
        return $this->p90;
    }

    /**
     * @return string
     */
    public function getP95(): string
    {
        return $this->p95;
    }

    /**
     * @return string
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