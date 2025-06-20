<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCSpecEventParamLocationV0
{
    public int $value;

    const SC_SPEC_EVENT_PARAM_LOCATION_DATA = 0;
    const SC_SPEC_EVENT_PARAM_LOCATION_TOPIC_LIST = 1;

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

    public static function decode(XdrBuffer $xdr): XdrSCSpecEventParamLocationV0
    {
        $value = $xdr->readInteger32();
        return new XdrSCSpecEventParamLocationV0($value);
    }

    public static function SC_SPEC_EVENT_PARAM_LOCATION_DATA() : XdrSCSpecEventParamLocationV0 {
        return new XdrSCSpecEventParamLocationV0(XdrSCSpecEventParamLocationV0::SC_SPEC_EVENT_PARAM_LOCATION_DATA);
    }

    public static function SC_SPEC_EVENT_PARAM_LOCATION_TOPIC_LIST() : XdrSCSpecEventParamLocationV0 {
        return new XdrSCSpecEventParamLocationV0(XdrSCSpecEventParamLocationV0::SC_SPEC_EVENT_PARAM_LOCATION_TOPIC_LIST);
    }
}