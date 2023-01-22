<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrConfigSetting
{

    public XdrConfigSettingType $type;
    public ?int $uint32Val = null;

    /**
     * @param XdrConfigSettingType $type
     */
    public function __construct(XdrConfigSettingType $type)
    {
        $this->type = $type;
    }


    public function encode(): string {
        $bytes = $this->type->encode();

        switch ($this->type->value) {
            case XdrConfigSettingType::CONFIG_SETTING_TYPE_UINT32:
                $bytes .= XdrEncoder::unsignedInteger32($this->uint32Val);
                break;

        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrConfigSetting {
        $result = new XdrConfigSetting(XdrConfigSettingType::decode($xdr));
        switch ($result->type->value) {
            case XdrConfigSettingType::CONFIG_SETTING_TYPE_UINT32:
                $result->uint32Val = $xdr->readUnsignedInteger32();
                break;
        }
        return $result;
    }

    /**
     * @return XdrConfigSettingType
     */
    public function getType(): XdrConfigSettingType
    {
        return $this->type;
    }

    /**
     * @param XdrConfigSettingType $type
     */
    public function setType(XdrConfigSettingType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return int|null
     */
    public function getUint32Val(): ?int
    {
        return $this->uint32Val;
    }

    /**
     * @param int|null $uint32Val
     */
    public function setUint32Val(?int $uint32Val): void
    {
        $this->uint32Val = $uint32Val;
    }

}