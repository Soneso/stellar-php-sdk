<?php

namespace Soneso\StellarSDK\Responses\FeeStats;

use Soneso\StellarSDK\Responses\Response;

/**
 * Represents fee statistics from Horizon
 *
 * Contains statistical information about transaction fees including the last ledger details,
 * base fee, capacity usage, and distributions of fees charged and max fees. This helps
 * developers determine appropriate fee levels for transactions.
 *
 * @package Soneso\StellarSDK\Responses\FeeStats
 * @see FeeChargedResponse For fee charged statistics
 * @see MaxFeeResponse For max fee statistics
 * @see https://developers.stellar.org Stellar developer docs Horizon Fee Stats API
 * @since 1.0.0
 */
class FeeStatsResponse extends Response
{
    private string $lastLedger;
    private string $lastLedgerBaseFee;
    private string $ledgerCapacityUsage;
    private FeeChargedResponse $feeCharged;
    private MaxFeeResponse $maxFee;

    /**
     * Gets the sequence number of the last ledger
     *
     * @return string The last ledger sequence number
     */
    public function getLastLedger(): string
    {
        return $this->lastLedger;
    }

    /**
     * Gets the base fee in stroops for the last ledger
     *
     * @return string The base fee amount
     */
    public function getLastLedgerBaseFee(): string
    {
        return $this->lastLedgerBaseFee;
    }

    /**
     * Gets the capacity usage for the last ledger
     *
     * Represents the percentage of ledger capacity used.
     *
     * @return string The capacity usage as a string
     */
    public function getLedgerCapacityUsage(): string
    {
        return $this->ledgerCapacityUsage;
    }

    /**
     * Gets the fee charged statistics
     *
     * @return FeeChargedResponse The fee charged distribution
     */
    public function getFeeCharged(): FeeChargedResponse
    {
        return $this->feeCharged;
    }

    /**
     * Gets the max fee statistics
     *
     * @return MaxFeeResponse The max fee distribution
     */
    public function getMaxFee(): MaxFeeResponse
    {
        return $this->maxFee;
    }

    protected function loadFromJson(array $json) : void {

        if (isset($json['last_ledger'])) $this->lastLedger = $json['last_ledger'];
        if (isset($json['last_ledger_base_fee'])) $this->lastLedgerBaseFee = $json['last_ledger_base_fee'];
        if (isset($json['ledger_capacity_usage'])) $this->ledgerCapacityUsage = $json['ledger_capacity_usage'];
        if (isset($json['max_fee'])) $this->maxFee = MaxFeeResponse::fromJson($json['max_fee']);
        if (isset($json['fee_charged'])) $this->feeCharged = FeeChargedResponse::fromJson($json['fee_charged']);

    }

    public static function fromJson(array $json) : FeeStatsResponse
    {
        $result = new FeeStatsResponse();
        $result->loadFromJson($json);
        return $result;
    }
}