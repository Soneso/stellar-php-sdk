<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;


class XdrConfigSettingEntry
{
    public XdrConfigSettingID $configSettingID;
    public ?int $contractMaxSizeBytes = null;
    public ?XdrConfigSettingContractComputeV0 $contractCompute = null;
    public ?XdrConfigSettingContractLedgerCostV0 $contractLedgerCost = null;
    public ?XdrConfigSettingContractHistoricalDataV0 $contractHistoricalData = null;
    public ?XdrConfigSettingContractMetaDataV0 $contractMetaData = null;
    public ?XdrConfigSettingContractBandwidthV0 $contractBandwidth = null;
    public ?XdrContractCostParams $contractCostParamsCpuInsns = null;
    public ?XdrContractCostParams $contractCostParamsMemBytes = null;
    public ?int $contractDataKeySizeBytes = null;
    public ?int $contractDataEntrySizeBytes = null;
    public ?XdrStateExpirationSettings $stateExpirationSettings = null;
    public ?XdrConfigSettingContractExecutionLanesV0 $contractExecutionLanes = null;
    public ?array $bucketListSizeWindow = null; // [uint64]
    /**
     * @param XdrConfigSettingID $configSettingID
     */
    public function __construct(XdrConfigSettingID $configSettingID)
    {
        $this->configSettingID = $configSettingID;
    }


    public function encode(): string {
        $bytes = $this->configSettingID->encode();
        switch ($this->configSettingID->value) {
            case XdrConfigSettingID::CONFIG_SETTING_CONTRACT_MAX_SIZE_BYTES:
                $bytes .= XdrEncoder::unsignedInteger32($this->contractMaxSizeBytes);
                break;
            case XdrConfigSettingID::CONFIG_SETTING_CONTRACT_COMPUTE_V0:
                $bytes .= $this->contractCompute->encode();
                break;
            case XdrConfigSettingID::CONFIG_SETTING_CONTRACT_LEDGER_COST_V0:
                $bytes .= $this->contractLedgerCost->encode();
                break;
            case XdrConfigSettingID::CONFIG_SETTING_CONTRACT_HISTORICAL_DATA_V0:
                $bytes .= $this->contractHistoricalData->encode();
                break;
            case XdrConfigSettingID::CONFIG_SETTING_CONTRACT_META_DATA_V0:
                $bytes .= $this->contractMetaData->encode();
                break;
            case XdrConfigSettingID::CONFIG_SETTING_CONTRACT_BANDWIDTH_V0:
                $bytes .= $this->contractBandwidth->encode();
                break;
            case XdrConfigSettingID::CONFIG_SETTING_CONTRACT_COST_PARAMS_CPU_INSTRUCTIONS:
                $bytes .= $this->contractCostParamsCpuInsns->encode();
                break;
            case XdrConfigSettingID::CONFIG_SETTING_CONTRACT_COST_PARAMS_MEMORY_BYTES:
                $bytes .= $this->contractCostParamsMemBytes->encode();
                break;
            case XdrConfigSettingID::CONFIG_SETTING_CONTRACT_DATA_KEY_SIZE_BYTES:
                $bytes .= XdrEncoder::unsignedInteger32($this->contractDataKeySizeBytes);
                break;
            case XdrConfigSettingID::CONFIG_SETTING_CONTRACT_DATA_ENTRY_SIZE_BYTES:
                $bytes .= XdrEncoder::unsignedInteger32($this->contractDataEntrySizeBytes);
                break;
            case XdrConfigSettingID::CONFIG_SETTING_STATE_EXPIRATION:
                $bytes .= $this->stateExpirationSettings->encode();
                break;
            case XdrConfigSettingID::CONFIG_SETTING_CONTRACT_EXECUTION_LANES:
                $bytes .= $this->contractExecutionLanes->encode();
                break;
            case XdrConfigSettingID::CONFIG_SETTING_BUCKETLIST_SIZE_WINDOW:
                $bytes .= XdrEncoder::integer32(count($this->bucketListSizeWindow));
                foreach($this->bucketListSizeWindow as $val) {
                    $bytes .= XdrEncoder::unsignedInteger64($val);
                }
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrConfigSettingEntry {
        $v = $xdr->readInteger32();
        $result = new XdrConfigSettingEntry(new XdrConfigSettingID($v));
        switch ($v) {
            case XdrConfigSettingID::CONFIG_SETTING_CONTRACT_MAX_SIZE_BYTES:
                $result->contractMaxSizeBytes = $xdr->readUnsignedInteger32();
                break;
            case XdrConfigSettingID::CONFIG_SETTING_CONTRACT_COMPUTE_V0:
                $result->contractCompute = XdrConfigSettingContractComputeV0::decode($xdr);
                break;
            case XdrConfigSettingID::CONFIG_SETTING_CONTRACT_LEDGER_COST_V0:
                $result->contractLedgerCost = XdrConfigSettingContractLedgerCostV0::decode($xdr);
                break;
            case XdrConfigSettingID::CONFIG_SETTING_CONTRACT_HISTORICAL_DATA_V0:
                $result->contractHistoricalData = XdrConfigSettingContractHistoricalDataV0::decode($xdr);
                break;
            case XdrConfigSettingID::CONFIG_SETTING_CONTRACT_META_DATA_V0:
                $result->contractMetaData = XdrConfigSettingContractMetaDataV0::decode($xdr);
                break;
            case XdrConfigSettingID::CONFIG_SETTING_CONTRACT_BANDWIDTH_V0:
                $result->contractBandwidth = XdrConfigSettingContractBandwidthV0::decode($xdr);
                break;
            case XdrConfigSettingID::CONFIG_SETTING_CONTRACT_COST_PARAMS_CPU_INSTRUCTIONS:
                $result->contractCostParamsCpuInsns = XdrContractCostParams::decode($xdr);
                break;
            case XdrConfigSettingID::CONFIG_SETTING_CONTRACT_COST_PARAMS_MEMORY_BYTES:
                $result->contractCostParamsMemBytes = XdrContractCostParams::decode($xdr);
                break;
            case XdrConfigSettingID::CONFIG_SETTING_CONTRACT_DATA_KEY_SIZE_BYTES:
                $result->contractDataKeySizeBytes = $xdr->readUnsignedInteger32();
                break;
            case XdrConfigSettingID::CONFIG_SETTING_CONTRACT_DATA_ENTRY_SIZE_BYTES:
                $result->contractDataEntrySizeBytes = $xdr->readUnsignedInteger32();
                break;
            case XdrConfigSettingID::CONFIG_SETTING_STATE_EXPIRATION:
                $result->stateExpirationSettings = XdrStateExpirationSettings::decode($xdr);
                break;
            case XdrConfigSettingID::CONFIG_SETTING_CONTRACT_EXECUTION_LANES:
                $result->contractExecutionLanes = XdrConfigSettingContractExecutionLanesV0::decode($xdr);
                break;
            case XdrConfigSettingID::CONFIG_SETTING_BUCKETLIST_SIZE_WINDOW:
                $valCount = $xdr->readInteger32();
                $entriesArr = array();
                for ($i = 0; $i < $valCount; $i++) {
                    array_push($entriesArr, $xdr->readUnsignedInteger64());
                }
                $result->bucketListSizeWindow = $entriesArr;
                break;
        }
        return $result;
    }

    /**
     * @return XdrConfigSettingID
     */
    public function getConfigSettingID(): XdrConfigSettingID
    {
        return $this->configSettingID;
    }

    /**
     * @param XdrConfigSettingID $configSettingID
     */
    public function setConfigSettingID(XdrConfigSettingID $configSettingID): void
    {
        $this->configSettingID = $configSettingID;
    }

    /**
     * @return int|null
     */
    public function getContractMaxSizeBytes(): ?int
    {
        return $this->contractMaxSizeBytes;
    }

    /**
     * @param int|null $contractMaxSizeBytes
     */
    public function setContractMaxSizeBytes(?int $contractMaxSizeBytes): void
    {
        $this->contractMaxSizeBytes = $contractMaxSizeBytes;
    }

    /**
     * @return XdrConfigSettingContractComputeV0|null
     */
    public function getContractCompute(): ?XdrConfigSettingContractComputeV0
    {
        return $this->contractCompute;
    }

    /**
     * @param XdrConfigSettingContractComputeV0|null $contractCompute
     */
    public function setContractCompute(?XdrConfigSettingContractComputeV0 $contractCompute): void
    {
        $this->contractCompute = $contractCompute;
    }

    /**
     * @return XdrConfigSettingContractLedgerCostV0|null
     */
    public function getContractLedgerCost(): ?XdrConfigSettingContractLedgerCostV0
    {
        return $this->contractLedgerCost;
    }

    /**
     * @param XdrConfigSettingContractLedgerCostV0|null $contractLedgerCost
     */
    public function setContractLedgerCost(?XdrConfigSettingContractLedgerCostV0 $contractLedgerCost): void
    {
        $this->contractLedgerCost = $contractLedgerCost;
    }

    /**
     * @return XdrConfigSettingContractHistoricalDataV0|null
     */
    public function getContractHistoricalData(): ?XdrConfigSettingContractHistoricalDataV0
    {
        return $this->contractHistoricalData;
    }

    /**
     * @param XdrConfigSettingContractHistoricalDataV0|null $contractHistoricalData
     */
    public function setContractHistoricalData(?XdrConfigSettingContractHistoricalDataV0 $contractHistoricalData): void
    {
        $this->contractHistoricalData = $contractHistoricalData;
    }

    /**
     * @return XdrConfigSettingContractMetaDataV0|null
     */
    public function getContractMetaData(): ?XdrConfigSettingContractMetaDataV0
    {
        return $this->contractMetaData;
    }

    /**
     * @param XdrConfigSettingContractMetaDataV0|null $contractMetaData
     */
    public function setContractMetaData(?XdrConfigSettingContractMetaDataV0 $contractMetaData): void
    {
        $this->contractMetaData = $contractMetaData;
    }

    /**
     * @return XdrConfigSettingContractBandwidthV0|null
     */
    public function getContractBandwidth(): ?XdrConfigSettingContractBandwidthV0
    {
        return $this->contractBandwidth;
    }

    /**
     * @param XdrConfigSettingContractBandwidthV0|null $contractBandwidth
     */
    public function setContractBandwidth(?XdrConfigSettingContractBandwidthV0 $contractBandwidth): void
    {
        $this->contractBandwidth = $contractBandwidth;
    }

    /**
     * @return XdrContractCostParams|null
     */
    public function getContractCostParamsCpuInsns(): ?XdrContractCostParams
    {
        return $this->contractCostParamsCpuInsns;
    }

    /**
     * @param XdrContractCostParams|null $contractCostParamsCpuInsns
     */
    public function setContractCostParamsCpuInsns(?XdrContractCostParams $contractCostParamsCpuInsns): void
    {
        $this->contractCostParamsCpuInsns = $contractCostParamsCpuInsns;
    }

    /**
     * @return XdrContractCostParams|null
     */
    public function getContractCostParamsMemBytes(): ?XdrContractCostParams
    {
        return $this->contractCostParamsMemBytes;
    }

    /**
     * @param XdrContractCostParams|null $contractCostParamsMemBytes
     */
    public function setContractCostParamsMemBytes(?XdrContractCostParams $contractCostParamsMemBytes): void
    {
        $this->contractCostParamsMemBytes = $contractCostParamsMemBytes;
    }

    /**
     * @return int|null
     */
    public function getContractDataKeySizeBytes(): ?int
    {
        return $this->contractDataKeySizeBytes;
    }

    /**
     * @param int|null $contractDataKeySizeBytes
     */
    public function setContractDataKeySizeBytes(?int $contractDataKeySizeBytes): void
    {
        $this->contractDataKeySizeBytes = $contractDataKeySizeBytes;
    }

    /**
     * @return int|null
     */
    public function getContractDataEntrySizeBytes(): ?int
    {
        return $this->contractDataEntrySizeBytes;
    }

    /**
     * @param int|null $contractDataEntrySizeBytes
     */
    public function setContractDataEntrySizeBytes(?int $contractDataEntrySizeBytes): void
    {
        $this->contractDataEntrySizeBytes = $contractDataEntrySizeBytes;
    }

    /**
     * @return XdrStateExpirationSettings|null
     */
    public function getStateExpirationSettings(): ?XdrStateExpirationSettings
    {
        return $this->stateExpirationSettings;
    }

    /**
     * @param XdrStateExpirationSettings|null $stateExpirationSettings
     */
    public function setStateExpirationSettings(?XdrStateExpirationSettings $stateExpirationSettings): void
    {
        $this->stateExpirationSettings = $stateExpirationSettings;
    }

    /**
     * @return XdrConfigSettingContractExecutionLanesV0|null
     */
    public function getContractExecutionLanes(): ?XdrConfigSettingContractExecutionLanesV0
    {
        return $this->contractExecutionLanes;
    }

    /**
     * @param XdrConfigSettingContractExecutionLanesV0|null $contractExecutionLanes
     */
    public function setContractExecutionLanes(?XdrConfigSettingContractExecutionLanesV0 $contractExecutionLanes): void
    {
        $this->contractExecutionLanes = $contractExecutionLanes;
    }

    /**
     * @return array|null
     */
    public function getBucketListSizeWindow(): ?array
    {
        return $this->bucketListSizeWindow;
    }

    /**
     * @param array|null $bucketListSizeWindow
     */
    public function setBucketListSizeWindow(?array $bucketListSizeWindow): void
    {
        $this->bucketListSizeWindow = $bucketListSizeWindow;
    }

}