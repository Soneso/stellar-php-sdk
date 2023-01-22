<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;


class XdrConfigSettingEntry
{
    public XdrConfigSettingEntryExt $ext;
    public XdrConfigSettingID $configSettingID;
    public XdrConfigSetting $configSetting;

    /**
     * @param XdrConfigSettingEntryExt $ext
     * @param XdrConfigSettingID $configSettingID
     * @param XdrConfigSetting $configSetting
     */
    public function __construct(XdrConfigSettingEntryExt $ext, XdrConfigSettingID $configSettingID, XdrConfigSetting $configSetting)
    {
        $this->ext = $ext;
        $this->configSettingID = $configSettingID;
        $this->configSetting = $configSetting;
    }


    public function encode(): string {
        $bytes = $this->ext->encode();
        $bytes .= $this->configSettingID->encode();
        $bytes .= $this->configSetting->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrConfigSettingEntry {
        $ext = XdrConfigSettingEntryExt::decode($xdr);
        $configSettingID = XdrConfigSettingID::decode($xdr);
        $configSetting = XdrConfigSetting::decode($xdr);

        return new XdrConfigSettingEntry($ext, $configSettingID, $configSetting);
    }

    /**
     * @return XdrConfigSettingEntryExt
     */
    public function getExt(): XdrConfigSettingEntryExt
    {
        return $this->ext;
    }

    /**
     * @param XdrConfigSettingEntryExt $ext
     */
    public function setExt(XdrConfigSettingEntryExt $ext): void
    {
        $this->ext = $ext;
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
     * @return XdrConfigSetting
     */
    public function getConfigSetting(): XdrConfigSetting
    {
        return $this->configSetting;
    }

    /**
     * @param XdrConfigSetting $configSetting
     */
    public function setConfigSetting(XdrConfigSetting $configSetting): void
    {
        $this->configSetting = $configSetting;
    }
}