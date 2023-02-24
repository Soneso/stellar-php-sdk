<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCSpecUDTUnionCaseV0
{

    public XdrSCSpecUDTUnionCaseV0Kind $kind;
    public ?XdrSCSpecUDTUnionCaseVoidV0 $voidCase = null;
    public ?XdrSCSpecUDTUnionCaseTupleV0 $tupleCase = null;

    /**
     * @param XdrSCSpecUDTUnionCaseV0Kind $kind
     */
    public function __construct(XdrSCSpecUDTUnionCaseV0Kind $kind)
    {
        $this->kind = $kind;
    }


    public function encode(): string {
        $bytes = $this->kind->encode();

        switch ($this->kind->value) {
            case XdrSCSpecUDTUnionCaseV0Kind::SC_SPEC_UDT_UNION_CASE_VOID_V0:
                $bytes .= $this->voidCase->encode();
                break;
            case XdrSCSpecUDTUnionCaseV0Kind::SC_SPEC_UDT_UNION_CASE_TUPLE_V0:
                $bytes .= $this->tupleCase->encode();
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCSpecUDTUnionCaseV0 {
        $result = new XdrSCSpecUDTUnionCaseV0(XdrSCSpecUDTUnionCaseV0Kind::decode($xdr));
        switch ($result->kind->getValue()) {
            case XdrSCSpecUDTUnionCaseV0Kind::SC_SPEC_UDT_UNION_CASE_VOID_V0:
                $result->voidCase = XdrSCSpecUDTUnionCaseVoidV0::decode($xdr);
                break;
            case XdrSCSpecUDTUnionCaseV0Kind::SC_SPEC_UDT_UNION_CASE_TUPLE_V0:
                $result->tupleCase = XdrSCSpecUDTUnionCaseTupleV0::decode($xdr);
                break;
        }
        return $result;
    }

    /**
     * @return XdrSCSpecUDTUnionCaseV0Kind
     */
    public function getKind(): XdrSCSpecUDTUnionCaseV0Kind
    {
        return $this->kind;
    }

    /**
     * @param XdrSCSpecUDTUnionCaseV0Kind $kind
     */
    public function setKind(XdrSCSpecUDTUnionCaseV0Kind $kind): void
    {
        $this->kind = $kind;
    }

    /**
     * @return XdrSCSpecUDTUnionCaseVoidV0|null
     */
    public function getVoidCase(): ?XdrSCSpecUDTUnionCaseVoidV0
    {
        return $this->voidCase;
    }

    /**
     * @param XdrSCSpecUDTUnionCaseVoidV0|null $voidCase
     */
    public function setVoidCase(?XdrSCSpecUDTUnionCaseVoidV0 $voidCase): void
    {
        $this->voidCase = $voidCase;
    }

    /**
     * @return XdrSCSpecUDTUnionCaseTupleV0|null
     */
    public function getTupleCase(): ?XdrSCSpecUDTUnionCaseTupleV0
    {
        return $this->tupleCase;
    }

    /**
     * @param XdrSCSpecUDTUnionCaseTupleV0|null $tupleCase
     */
    public function setTupleCase(?XdrSCSpecUDTUnionCaseTupleV0 $tupleCase): void
    {
        $this->tupleCase = $tupleCase;
    }
}