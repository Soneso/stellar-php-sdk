<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCSpecTypeDef
{

    public XdrSCSpecType $type;

    public ?XdrSCSpecTypeOption $option = null;
    public ?XdrSCSpecTypeResult $result = null;
    public ?XdrSCSpecTypeVec $vec = null;
    public ?XdrSCSpecTypeMap $map = null;
    public ?XdrSCSpecTypeSet $set = null;
    public ?XdrSCSpecTypeTuple $tuple = null;
    public ?XdrSCSpecTypeBytesN $bytesN = null;
    public ?XdrSCSpecTypeUDT $udt = null;

    /**
     * @param XdrSCSpecType $type
     */
    public function __construct(XdrSCSpecType $type)
    {
        $this->type = $type;
    }


    public static function forOption(XdrSCSpecTypeOption $option) : XdrSCSpecTypeDef {
        $result = new XdrSCSpecTypeDef(XdrSCSpecType::OPTION());
        $result->option = $option;
        return $result;
    }

    public static function forResult(XdrSCSpecTypeResult $result) : XdrSCSpecTypeDef {
        $res = new XdrSCSpecTypeDef(XdrSCSpecType::RESULT());
        $res->result = $result;
        return $res;
    }

    public static function forVec(XdrSCSpecTypeVec $vec) : XdrSCSpecTypeDef {
        $result = new XdrSCSpecTypeDef(XdrSCSpecType::VEC());
        $result->vec = $vec;
        return $result;
    }

    public static function forMap(XdrSCSpecTypeMap $map) : XdrSCSpecTypeDef {
        $result = new XdrSCSpecTypeDef(XdrSCSpecType::MAP());
        $result->map = $map;
        return $result;
    }

    public static function forSet(XdrSCSpecTypeSet $set) : XdrSCSpecTypeDef {
        $result = new XdrSCSpecTypeDef(XdrSCSpecType::SET());
        $result->set = $set;
        return $result;
    }

    public static function forTuple(XdrSCSpecTypeTuple $tuple) : XdrSCSpecTypeDef {
        $result = new XdrSCSpecTypeDef(XdrSCSpecType::TUPLE());
        $result->tuple = $tuple;
        return $result;
    }

    public static function forBytesN(XdrSCSpecTypeBytesN $bytesN) : XdrSCSpecTypeDef {
        $result = new XdrSCSpecTypeDef(XdrSCSpecType::BYTES_N());
        $result->bytesN = $bytesN;
        return $result;
    }

    public static function forUDT(XdrSCSpecTypeUDT $udt) : XdrSCSpecTypeDef {
        $result = new XdrSCSpecTypeDef(XdrSCSpecType::UDT());
        $result->udt = $udt;
        return $result;
    }

    public static function BOOL() : XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::BOOL());
    }

    public static function VOID() : XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::VOID());
    }

    public static function STATUS() : XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::STATUS());
    }

    public static function U32() : XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::U32());
    }

    public static function I32() : XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::I32());
    }

    public static function U64() : XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::U64());
    }

    public static function I64() : XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::I64());
    }

    public static function TIMEPOINT() : XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::TIMEPOINT());
    }

    public static function DURATION() : XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::DURATION());
    }

    public static function U128() : XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::U128());
    }

    public static function I128() : XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::I128());
    }

    public static function U256() : XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::U256());
    }

    public static function I256() : XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::I256());
    }

    public static function BYTES() : XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::BYTES());
    }

    public static function STRING() : XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::STRING());
    }

    public static function SYMBOL() : XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::SYMBOL());
    }

    public static function ADDRESS() : XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::ADDRESS());
    }

    public function encode(): string {
        $bytes = $this->type->encode();

        switch ($this->type->value) {
            case XdrSCSpecType::SC_SPEC_TYPE_VAL:
            case XdrSCSpecType::SC_SPEC_TYPE_BOOL:
            case XdrSCSpecType::SC_SPEC_TYPE_VOID:
            case XdrSCSpecType::SC_SPEC_TYPE_STATUS:
            case XdrSCSpecType::SC_SPEC_TYPE_U32:
            case XdrSCSpecType::SC_SPEC_TYPE_I32:
            case XdrSCSpecType::SC_SPEC_TYPE_U64:
            case XdrSCSpecType::SC_SPEC_TYPE_I64:
            case XdrSCSpecType::SC_SPEC_TYPE_TIMEPOINT:
            case XdrSCSpecType::SC_SPEC_TYPE_DURATION:
            case XdrSCSpecType::SC_SPEC_TYPE_U128:
            case XdrSCSpecType::SC_SPEC_TYPE_I128:
            case XdrSCSpecType::SC_SPEC_TYPE_U256:
            case XdrSCSpecType::SC_SPEC_TYPE_I256:
            case XdrSCSpecType::SC_SPEC_TYPE_BYTES:
            case XdrSCSpecType::SC_SPEC_TYPE_STRING:
            case XdrSCSpecType::SC_SPEC_TYPE_SYMBOL:
            case XdrSCSpecType::SC_SPEC_TYPE_ADDRESS:
                break;
            case XdrSCSpecType::SC_SPEC_TYPE_OPTION:
                $bytes .= $this->option->encode();
                break;
            case XdrSCSpecType::SC_SPEC_TYPE_RESULT:
                $bytes .= $this->result->encode();
                break;
            case XdrSCSpecType::SC_SPEC_TYPE_VEC:
                $bytes .= $this->vec->encode();
                break;
            case XdrSCSpecType::SC_SPEC_TYPE_MAP:
                $bytes .= $this->map->encode();
                break;
            case XdrSCSpecType::SC_SPEC_TYPE_SET:
                $bytes .= $this->set->encode();
                break;
            case XdrSCSpecType::SC_SPEC_TYPE_TUPLE:
                $bytes .= $this->tuple->encode();
                break;
            case XdrSCSpecType::SC_SPEC_TYPE_BYTES_N:
                $bytes .= $this->bytesN->encode();
                break;
            case XdrSCSpecType::SC_SPEC_TYPE_UDT:
                $bytes .= $this->udt->encode();
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCSpecTypeDef {
        $result = new XdrSCSpecTypeDef(XdrSCSpecType::decode($xdr));
        switch ($result->type->value) {
            case XdrSCSpecType::SC_SPEC_TYPE_VAL:
            case XdrSCSpecType::SC_SPEC_TYPE_BOOL:
            case XdrSCSpecType::SC_SPEC_TYPE_VOID:
            case XdrSCSpecType::SC_SPEC_TYPE_STATUS:
            case XdrSCSpecType::SC_SPEC_TYPE_U32:
            case XdrSCSpecType::SC_SPEC_TYPE_I32:
            case XdrSCSpecType::SC_SPEC_TYPE_U64:
            case XdrSCSpecType::SC_SPEC_TYPE_I64:
            case XdrSCSpecType::SC_SPEC_TYPE_TIMEPOINT:
            case XdrSCSpecType::SC_SPEC_TYPE_DURATION:
            case XdrSCSpecType::SC_SPEC_TYPE_U128:
            case XdrSCSpecType::SC_SPEC_TYPE_I128:
            case XdrSCSpecType::SC_SPEC_TYPE_U256:
            case XdrSCSpecType::SC_SPEC_TYPE_I256:
            case XdrSCSpecType::SC_SPEC_TYPE_BYTES:
            case XdrSCSpecType::SC_SPEC_TYPE_STRING:
            case XdrSCSpecType::SC_SPEC_TYPE_SYMBOL:
            case XdrSCSpecType::SC_SPEC_TYPE_ADDRESS:
                break;
            case XdrSCSpecType::SC_SPEC_TYPE_OPTION:
                $result->option = XdrSCSpecTypeOption::decode($xdr);
                break;
            case XdrSCSpecType::SC_SPEC_TYPE_RESULT:
                $result->result = XdrSCSpecTypeResult::decode($xdr);
                break;
            case XdrSCSpecType::SC_SPEC_TYPE_VEC:
                $result->vec = XdrSCSpecTypeVec::decode($xdr);
                break;
            case XdrSCSpecType::SC_SPEC_TYPE_MAP:
                $result->map = XdrSCSpecTypeMap::decode($xdr);
                break;
            case XdrSCSpecType::SC_SPEC_TYPE_SET:
                $result->set = XdrSCSpecTypeSet::decode($xdr);
                break;
            case XdrSCSpecType::SC_SPEC_TYPE_TUPLE:
                $result->tuple = XdrSCSpecTypeTuple::decode($xdr);
                break;
            case XdrSCSpecType::SC_SPEC_TYPE_BYTES_N:
                $result->bytesN = XdrSCSpecTypeBytesN::decode($xdr);
                break;
            case XdrSCSpecType::SC_SPEC_TYPE_UDT:
                $result->udt = XdrSCSpecTypeUDT::decode($xdr);
                break;
        }
        return $result;
    }

    /**
     * @return XdrSCSpecType
     */
    public function getType(): XdrSCSpecType
    {
        return $this->type;
    }

    /**
     * @param XdrSCSpecType $type
     */
    public function setType(XdrSCSpecType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return XdrSCSpecTypeOption|null
     */
    public function getOption(): ?XdrSCSpecTypeOption
    {
        return $this->option;
    }

    /**
     * @param XdrSCSpecTypeOption|null $option
     */
    public function setOption(?XdrSCSpecTypeOption $option): void
    {
        $this->option = $option;
    }

    /**
     * @return XdrSCSpecTypeResult|null
     */
    public function getResult(): ?XdrSCSpecTypeResult
    {
        return $this->result;
    }

    /**
     * @param XdrSCSpecTypeResult|null $result
     */
    public function setResult(?XdrSCSpecTypeResult $result): void
    {
        $this->result = $result;
    }

    /**
     * @return XdrSCSpecTypeVec|null
     */
    public function getVec(): ?XdrSCSpecTypeVec
    {
        return $this->vec;
    }

    /**
     * @param XdrSCSpecTypeVec|null $vec
     */
    public function setVec(?XdrSCSpecTypeVec $vec): void
    {
        $this->vec = $vec;
    }

    /**
     * @return XdrSCSpecTypeMap|null
     */
    public function getMap(): ?XdrSCSpecTypeMap
    {
        return $this->map;
    }

    /**
     * @param XdrSCSpecTypeMap|null $map
     */
    public function setMap(?XdrSCSpecTypeMap $map): void
    {
        $this->map = $map;
    }

    /**
     * @return XdrSCSpecTypeSet|null
     */
    public function getSet(): ?XdrSCSpecTypeSet
    {
        return $this->set;
    }

    /**
     * @param XdrSCSpecTypeSet|null $set
     */
    public function setSet(?XdrSCSpecTypeSet $set): void
    {
        $this->set = $set;
    }

    /**
     * @return XdrSCSpecTypeTuple|null
     */
    public function getTuple(): ?XdrSCSpecTypeTuple
    {
        return $this->tuple;
    }

    /**
     * @param XdrSCSpecTypeTuple|null $tuple
     */
    public function setTuple(?XdrSCSpecTypeTuple $tuple): void
    {
        $this->tuple = $tuple;
    }

    /**
     * @return XdrSCSpecTypeBytesN|null
     */
    public function getBytesN(): ?XdrSCSpecTypeBytesN
    {
        return $this->bytesN;
    }

    /**
     * @param XdrSCSpecTypeBytesN|null $bytesN
     */
    public function setBytesN(?XdrSCSpecTypeBytesN $bytesN): void
    {
        $this->bytesN = $bytesN;
    }

    /**
     * @return XdrSCSpecTypeUDT|null
     */
    public function getUdt(): ?XdrSCSpecTypeUDT
    {
        return $this->udt;
    }

    /**
     * @param XdrSCSpecTypeUDT|null $udt
     */
    public function setUdt(?XdrSCSpecTypeUDT $udt): void
    {
        $this->udt = $udt;
    }
}