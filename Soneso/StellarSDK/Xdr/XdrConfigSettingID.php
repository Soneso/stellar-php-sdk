<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrConfigSettingID
{
    public int $value;

    const CONFIG_SETTING_CONTRACT_MAX_SIZE_BYTES = 0;
    const CONFIG_SETTING_CONTRACT_COMPUTE_V0 = 1;
    const CONFIG_SETTING_CONTRACT_LEDGER_COST_V0 = 2;
    const CONFIG_SETTING_CONTRACT_HISTORICAL_DATA_V0 = 3;
    const CONFIG_SETTING_CONTRACT_EVENTS_V0 = 4;
    const CONFIG_SETTING_CONTRACT_BANDWIDTH_V0 = 5;
    const CONFIG_SETTING_CONTRACT_COST_PARAMS_CPU_INSTRUCTIONS = 6;
    const CONFIG_SETTING_CONTRACT_COST_PARAMS_MEMORY_BYTES = 7;
    const CONFIG_SETTING_CONTRACT_DATA_KEY_SIZE_BYTES = 8;
    const CONFIG_SETTING_CONTRACT_DATA_ENTRY_SIZE_BYTES = 9;
    const CONFIG_SETTING_STATE_ARCHIVAL = 10;
    const CONFIG_SETTING_CONTRACT_EXECUTION_LANES = 11;
    const CONFIG_SETTING_LIVE_SOROBAN_STATE_SIZE_WINDOW = 12;
    const CONFIG_SETTING_EVICTION_ITERATOR = 13;
    const CONFIG_SETTING_CONTRACT_PARALLEL_COMPUTE_V0 = 14;
    const CONFIG_SETTING_CONTRACT_LEDGER_COST_EXT_V0 = 15;
    const CONFIG_SETTING_SCP_TIMING = 16;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public static function CONFIG_SETTING_CONTRACT_MAX_SIZE_BYTES() : XdrConfigSettingID {
        return new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_MAX_SIZE_BYTES);
    }

    public static function CONFIG_SETTING_CONTRACT_COMPUTE_V0() : XdrConfigSettingID {
        return new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_COMPUTE_V0);
    }

    public static function CONFIG_SETTING_CONTRACT_LEDGER_COST_V0() : XdrConfigSettingID {
        return new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_LEDGER_COST_V0);
    }

    public static function CONFIG_SETTING_CONTRACT_HISTORICAL_DATA_V0() : XdrConfigSettingID {
        return new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_HISTORICAL_DATA_V0);
    }

    public static function CONFIG_SETTING_CONTRACT_EVENTS_V0() : XdrConfigSettingID {
        return new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_EVENTS_V0);
    }

    public static function CONFIG_SETTING_CONTRACT_BANDWIDTH_V0() : XdrConfigSettingID {
        return new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_BANDWIDTH_V0);
    }

    public static function CONFIG_SETTING_CONTRACT_COST_PARAMS_CPU_INSTRUCTIONS() : XdrConfigSettingID {
        return new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_COST_PARAMS_CPU_INSTRUCTIONS);
    }

    public static function CONFIG_SETTING_CONTRACT_COST_PARAMS_MEMORY_BYTES() : XdrConfigSettingID {
        return new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_COST_PARAMS_MEMORY_BYTES);
    }

    public static function CONFIG_SETTING_CONTRACT_DATA_KEY_SIZE_BYTES() : XdrConfigSettingID {
        return new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_DATA_KEY_SIZE_BYTES);
    }

    public static function CONFIG_SETTING_CONTRACT_DATA_ENTRY_SIZE_BYTES() : XdrConfigSettingID {
        return new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_DATA_ENTRY_SIZE_BYTES);
    }

    public static function CONFIG_SETTING_STATE_ARCHIVAL() : XdrConfigSettingID {
        return new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_STATE_ARCHIVAL);
    }

    public static function CONFIG_SETTING_CONTRACT_EXECUTION_LANES() : XdrConfigSettingID {
        return new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_EXECUTION_LANES);
    }

    public static function CONFIG_SETTING_LIVE_SOROBAN_STATE_SIZE_WINDOW() : XdrConfigSettingID {
        return new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_LIVE_SOROBAN_STATE_SIZE_WINDOW);
    }

    public static function CONFIG_SETTING_EVICTION_ITERATOR() : XdrConfigSettingID {
        return new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_EVICTION_ITERATOR);
    }

    public static function CONFIG_SETTING_CONTRACT_PARALLEL_COMPUTE_V0() : XdrConfigSettingID {
        return new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_PARALLEL_COMPUTE_V0);
    }

    public static function CONFIG_SETTING_CONTRACT_LEDGER_COST_EXT_V0() : XdrConfigSettingID {
        return new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_CONTRACT_LEDGER_COST_EXT_V0);
    }

    public static function CONFIG_SETTING_SCP_TIMING() : XdrConfigSettingID {
        return new XdrConfigSettingID(XdrConfigSettingID::CONFIG_SETTING_SCP_TIMING);
    }

    /**
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }

    public function encode(): string
    {
        return XdrEncoder::integer32($this->value);
    }

    public static function decode(XdrBuffer $xdr): XdrConfigSettingID
    {
        $value = $xdr->readInteger32();
        return new XdrConfigSettingID($value);
    }
}