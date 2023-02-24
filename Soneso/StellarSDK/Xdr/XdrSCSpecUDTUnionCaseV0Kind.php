<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCSpecUDTUnionCaseV0Kind
{
    public int $value;

    const SC_SPEC_UDT_UNION_CASE_VOID_V0 = 0;
    const SC_SPEC_UDT_UNION_CASE_TUPLE_V0 = 1;

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

    public static function decode(XdrBuffer $xdr): XdrSCSpecUDTUnionCaseV0Kind
    {
        $value = $xdr->readInteger32();
        return new XdrSCSpecUDTUnionCaseV0Kind($value);
    }
}