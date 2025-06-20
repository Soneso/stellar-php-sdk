<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCSpecEventDataFormat
{
    public int $value;

    const SC_SPEC_EVENT_DATA_FORMAT_SINGLE_VALUE = 0;
    const SC_SPEC_EVENT_DATA_FORMAT_VEC = 1;
    const SC_SPEC_EVENT_DATA_FORMAT_MAP = 2;

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

    public static function decode(XdrBuffer $xdr): XdrSCSpecEventDataFormat
    {
        $value = $xdr->readInteger32();
        return new XdrSCSpecEventDataFormat($value);
    }

    public static function SC_SPEC_EVENT_DATA_FORMAT_SINGLE_VALUE() : XdrSCSpecEventDataFormat {
        return new XdrSCSpecEventDataFormat(XdrSCSpecEventDataFormat::SC_SPEC_EVENT_DATA_FORMAT_SINGLE_VALUE);
    }

    public static function SC_SPEC_EVENT_DATA_FORMAT_VEC() : XdrSCSpecEventDataFormat {
        return new XdrSCSpecEventDataFormat(XdrSCSpecEventDataFormat::SC_SPEC_EVENT_DATA_FORMAT_VEC);
    }

    public static function SC_SPEC_EVENT_DATA_FORMAT_MAP() : XdrSCSpecEventDataFormat {
        return new XdrSCSpecEventDataFormat(XdrSCSpecEventDataFormat::SC_SPEC_EVENT_DATA_FORMAT_MAP);
    }
}