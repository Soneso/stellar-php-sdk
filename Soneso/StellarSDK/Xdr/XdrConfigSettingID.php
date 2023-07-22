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
    const CONFIG_SETTING_CONTRACT_META_DATA_V0 = 4;
    const CONFIG_SETTING_CONTRACT_BANDWIDTH_V0 = 5;
    const CONFIG_SETTING_CONTRACT_COST_PARAMS_CPU_INSTRUCTIONS = 6;
    const CONFIG_SETTING_CONTRACT_COST_PARAMS_MEMORY_BYTES = 7;
    const CONFIG_SETTING_CONTRACT_DATA_KEY_SIZE_BYTES = 8;
    const CONFIG_SETTING_CONTRACT_DATA_ENTRY_SIZE_BYTES = 9;
    const CONFIG_SETTING_STATE_EXPIRATION = 10;
    const CONFIG_SETTING_CONTRACT_EXECUTION_LANES = 11;
    const CONFIG_SETTING_BUCKETLIST_SIZE_WINDOW = 12;

    public function __construct(int $value)
    {
        $this->value = $value;
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