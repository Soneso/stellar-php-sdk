<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCVal
{

    public XdrSCValType $type;
    public ?int $u63 = null;
    public ?int $u32 = null;
    public ?int $i32 = null;
    public ?XdrSCStatic $ic = null;
    public ?XdrSCObject $obj = null;
    public ?String $sym = null;
    public ?int $bits = null;
    public ?XdrSCStatus $status = null;

    /**
     * @param XdrSCValType $type
     */
    public function __construct(XdrSCValType $type)
    {
        $this->type = $type;
    }

    public function encode(): string {
        $bytes = $this->type->encode();

        switch ($this->type->value) {
            case XdrSCValType::SCV_U63:
                $bytes .= XdrEncoder::unsignedInteger64($this->u63);
                break;
            case XdrSCValType::SCV_U32:
                $bytes .= XdrEncoder::unsignedInteger32($this->u32);
                break;
            case XdrSCValType::SCV_I32:
                $bytes .= XdrEncoder::integer32($this->i32);
                break;
            case XdrSCValType::SCV_STATIC:
                $bytes .= $this->ic->encode();
                break;
            case XdrSCValType::SCV_OBJECT:
                if ($this->obj != null) {
                    $bytes .= XdrEncoder::integer32(1);
                    $bytes .= $this->obj->encode();
                } else {
                    $bytes .= XdrEncoder::integer32(0);
                }
                break;
            case XdrSCValType::SCV_SYMBOL:
                $bytes .= XdrEncoder::string($this->sym);
                break;
            case XdrSCValType::SCV_BITSET:
                $bytes .= XdrEncoder::unsignedInteger64($this->bits);
                break;
            case XdrSCValType::SCV_STATUS:
                $bytes .= $this->status->encode();
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::decode($xdr));
        switch ($result->type->value) {
            case XdrSCValType::SCV_U63:
                $result->u63 = $xdr->readUnsignedInteger64();
                break;
            case XdrSCValType::SCV_U32:
                $result->u32 = $xdr->readUnsignedInteger32();
                break;
            case XdrSCValType::SCV_I32:
                $result->i32 = $xdr->readInteger32();
                break;
            case XdrSCValType::SCV_STATIC:
                $result->ic = XdrSCStatic::decode($xdr);
                break;
            case XdrSCValType::SCV_OBJECT:
                if ($xdr->readInteger32() == 1) {
                    $result->obj = XdrSCObject::decode($xdr);
                }
                break;
            case XdrSCValType::SCV_SYMBOL:
                $result->sym = $xdr->readString();
                break;
            case XdrSCValType::SCV_BITSET:
                $result->bits = $xdr->readUnsignedInteger64();
                break;
            case XdrSCValType::SCV_STATUS:
                $result->status = XdrSCStatus::decode($xdr);
                break;
        }
        return $result;
    }

    // [XdrSCVal]
    public function getVec() : ?array {
        return $this->obj?->vec;
    }

    // [XdrSCMapEntry]
    public function getMap() : ?array {
        return $this->obj?->map;
    }

    public function getU64() : ?int {
        return $this->obj?->u64;
    }

    public function getI64() : ?int {
        return $this->obj?->i64;
    }

    public function getU128() : ?XdrInt128Parts {
        return $this->obj?->u128;
    }

    public function getI128() : ?XdrInt128Parts {
        return $this->obj?->i128;
    }

    public function getBytes() : ?string {
        return $this->obj?->bin?->getValue();
    }

    public function getContractCode() : ?XdrSCContractCode {
        return $this->obj?->contractCode;
    }

    public function getAccountID() : ?XdrAccountID {
        return $this->obj?->accountID;
    }

    public static function fromU63(int $u63) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::U63());
        $result->u63 = $u63;
        return $result;
    }

    public static function fromU32(int $u32) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::U32());
        $result->u32 = $u32;
        return $result;
    }

    public static function fromI32(int $i32) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::I32());
        $result->i32 = $i32;
        return $result;
    }

    public static function fromStatic(XdrSCStatic $ic) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::STATIC());
        $result->ic = $ic;
        return $result;
    }

    public static function fromObject(XdrSCObject $object) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::OBJECT());
        $result->obj = $object;
        return $result;
    }

    public static function fromSymbol(String $symbol) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::SYMBOL());
        $result->sym = $symbol;
        return $result;
    }

    public static function fromBitset(int $bits) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::BITSET());
        $result->bits = $bits;
        return $result;
    }

    public static function fromStatus(XdrSCStatus $status) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::STATUS());
        $result->status = $status;
        return $result;
    }

    public static function fromBase64Xdr(String $base64Xdr) : XdrSCVal {
        $xdr = base64_decode($base64Xdr);
        $xdrBuffer = new XdrBuffer($xdr);
        return XdrSCVal::decode($xdrBuffer);
    }

    public function toBase64Xdr() : String {
        return base64_encode($this->encode());
    }

    /**
     * @return XdrSCValType
     */
    public function getType(): XdrSCValType
    {
        return $this->type;
    }

    /**
     * @param XdrSCValType $type
     */
    public function setType(XdrSCValType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return int|null
     */
    public function getU63(): ?int
    {
        return $this->u63;
    }

    /**
     * @param int|null $u63
     */
    public function setU63(?int $u63): void
    {
        $this->u63 = $u63;
    }

    /**
     * @return int|null
     */
    public function getU32(): ?int
    {
        return $this->u32;
    }

    /**
     * @param int|null $u32
     */
    public function setU32(?int $u32): void
    {
        $this->u32 = $u32;
    }

    /**
     * @return int|null
     */
    public function getI32(): ?int
    {
        return $this->i32;
    }

    /**
     * @param int|null $i32
     */
    public function setI32(?int $i32): void
    {
        $this->i32 = $i32;
    }

    /**
     * @return XdrSCStatic|null
     */
    public function getIc(): ?XdrSCStatic
    {
        return $this->ic;
    }

    /**
     * @param XdrSCStatic|null $ic
     */
    public function setIc(?XdrSCStatic $ic): void
    {
        $this->ic = $ic;
    }

    /**
     * @return XdrSCObject|null
     */
    public function getObj(): ?XdrSCObject
    {
        return $this->obj;
    }

    /**
     * @param XdrSCObject|null $obj
     */
    public function setObj(?XdrSCObject $obj): void
    {
        $this->obj = $obj;
    }

    /**
     * @return String|null
     */
    public function getSym(): ?string
    {
        return $this->sym;
    }

    /**
     * @param String|null $sym
     */
    public function setSym(?string $sym): void
    {
        $this->sym = $sym;
    }

    /**
     * @return int|null
     */
    public function getBits(): ?int
    {
        return $this->bits;
    }

    /**
     * @param int|null $bits
     */
    public function setBits(?int $bits): void
    {
        $this->bits = $bits;
    }

    /**
     * @return XdrSCStatus|null
     */
    public function getStatus(): ?XdrSCStatus
    {
        return $this->status;
    }

    /**
     * @param XdrSCStatus|null $status
     */
    public function setStatus(?XdrSCStatus $status): void
    {
        $this->status = $status;
    }
}