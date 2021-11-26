<?php

namespace Soneso\StellarSDK\Responses\FeeStats;

use Soneso\StellarSDK\Responses\Response;

class FeeStatsResponse extends Response
{
    private string $lastLedger;
    private string $lastLedgerBaseFee;
    private string $ledgerCapacityUsage;
    private FeeChargedResponse $feeCharged;
    private MaxFeeResponse $maxFee;

    /**
     * @return string
     */
    public function getLastLedger(): string
    {
        return $this->lastLedger;
    }

    /**
     * @return string
     */
    public function getLastLedgerBaseFee(): string
    {
        return $this->lastLedgerBaseFee;
    }

    /**
     * @return string
     */
    public function getLedgerCapacityUsage(): string
    {
        return $this->ledgerCapacityUsage;
    }

    /**
     * @return FeeChargedResponse
     */
    public function getFeeCharged(): FeeChargedResponse
    {
        return $this->feeCharged;
    }

    /**
     * @return MaxFeeResponse
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