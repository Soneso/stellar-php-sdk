<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCObject
{

    public XdrSCObjectType $type;
    public ?array $vec = null; // [XdrSCVal]
    public ?array $map = null; // [XdrSCMapEntry]
    public ?int $u64 = null;
    public ?int $i64 = null;
    public ?XdrInt128Parts $u128 = null;
    public ?XdrInt128Parts $i128 = null;
    public ?XdrDataValueMandatory $bin = null;
    public ?XdrSCContractCode $contractCode = null;
    public ?XdrSCAddress $address = null;
    public ?XdrSCAddress $nonceAddress = null;

    /**
     * @param XdrSCObjectType $type
     */
    public function __construct(XdrSCObjectType $type)
    {
        $this->type = $type;
    }

    public function encode(): string {
        $bytes = $this->type->encode();

        switch ($this->type->value) {
            case XdrSCObjectType::SCO_VEC:
                $bytes .= XdrEncoder::integer32(count($this->vec));
                foreach($this->vec as $val) {
                    if ($val instanceof XdrSCVal) {
                        $bytes .= $val->encode();
                    }
                }
                break;
            case XdrSCObjectType::SCO_MAP:
                $bytes .= XdrEncoder::integer32(count($this->map));
                foreach($this->map as $val) {
                    if ($val instanceof XdrSCMapEntry) {
                        $bytes .= $val->encode();
                    }
                }
                break;
            case XdrSCObjectType::SCO_U64:
                $bytes .= XdrEncoder::unsignedInteger64($this->u64);
                break;
            case XdrSCObjectType::SCO_I64:
                $bytes .= XdrEncoder::integer64($this->i64);
                break;
            case XdrSCObjectType::SCO_U128:
                $bytes .= $this->u128->encode();
                break;
            case XdrSCObjectType::SCO_I128:
                $bytes .= $this->i128->encode();
                break;
            case XdrSCObjectType::SCO_BYTES:
                $bytes .= $this->bin->encode();
                break;
            case XdrSCObjectType::SCO_CONTRACT_CODE:
                $bytes .= $this->contractCode->encode();
                break;
            case XdrSCObjectType::SCO_ADDRESS:
                $bytes .= $this->address->encode();
                break;
            case XdrSCObjectType::SCO_NONCE_KEY:
                $bytes .= $this->nonceAddress->encode();
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCObject {
        $result = new XdrSCObject(XdrSCObjectType::decode($xdr));
        switch ($result->type->value) {
            case XdrSCObjectType::SCO_VEC:
                $valCount = $xdr->readInteger32();
                $arr = array();
                for ($i = 0; $i < $valCount; $i++) {
                    array_push($arr, XdrSCVal::decode($xdr));
                }
                $result->vec = $arr;
                break;
            case XdrSCObjectType::SCO_MAP:
                $valCount = $xdr->readInteger32();
                $arr = array();
                for ($i = 0; $i < $valCount; $i++) {
                    array_push($arr, XdrSCMapEntry::decode($xdr));
                }
                $result->map = $arr;
                break;
            case XdrSCObjectType::SCO_U64:
                $result->u64 = $xdr->readUnsignedInteger64();
                break;
            case XdrSCObjectType::SCO_I64:
                $result->i64 = $xdr->readInteger64();
                break;
            case XdrSCObjectType::SCO_U128:
                $result->u128 = XdrInt128Parts::decode($xdr);
                break;
            case XdrSCObjectType::SCO_I128:
                $result->i128 = XdrInt128Parts::decode($xdr);
                break;
            case XdrSCObjectType::SCO_BYTES:
                $result->bin = XdrDataValueMandatory::decode($xdr);
                break;
            case XdrSCObjectType::SCO_CONTRACT_CODE:
                $result->contractCode = XdrSCContractCode::decode($xdr);
                break;
            case XdrSCObjectType::SCO_ADDRESS:
                $result->address = XdrSCAddress::decode($xdr);
                break;
            case XdrSCObjectType::SCO_NONCE_KEY:
                $result->nonceAddress = XdrSCAddress::decode($xdr);
                break;
        }
        return $result;
    }

    /// [XdrSCVal]
    public static function forVec(array $vec) : XdrSCObject {
        $result = new XdrSCObject(XdrSCObjectType::VEC());
        $result->vec = $vec;
        return $result;
    }

    /// [XdrSCMapEntry]
    public static function forMap(array $map) : XdrSCObject {
        $result = new XdrSCObject(XdrSCObjectType::MAP());
        $result->map = $map;
        return $result;
    }

    public static function forU64(int $u64) : XdrSCObject {
        $result = new XdrSCObject(XdrSCObjectType::U64());
        $result->u64 = $u64;
        return $result;
    }

    public static function forI64(int $i64) : XdrSCObject {
        $result = new XdrSCObject(XdrSCObjectType::I64());
        $result->i64 = $i64;
        return $result;
    }

    public static function forU128(XdrInt128Parts $value) : XdrSCObject {
        $result = new XdrSCObject(XdrSCObjectType::U128());
        $result->u128 = $value;
        return $result;
    }

    public static function forI128(XdrInt128Parts $value) : XdrSCObject {
        $result = new XdrSCObject(XdrSCObjectType::I128());
        $result->i128 = $value;
        return $result;
    }

    public static function forBytes(string $bytes) : XdrSCObject {
        $result = new XdrSCObject(XdrSCObjectType::BYTES());
        $result->bin = new XdrDataValueMandatory($bytes);
        return $result;
    }

    public static function forContractCode(XdrSCContractCode $value) : XdrSCObject {
        $result = new XdrSCObject(XdrSCObjectType::CONTRACT_CODE());
        $result->contractCode = $value;
        return $result;
    }

    public static function forAddress(XdrSCAddress $value) : XdrSCObject {
        $result = new XdrSCObject(XdrSCObjectType::ADDRESS());
        $result->address = $value;
        return $result;
    }

    public static function forNonceKey(XdrSCAddress $value) : XdrSCObject {
        $result = new XdrSCObject(XdrSCObjectType::NONCE_KEY());
        $result->nonceAddress = $value;
        return $result;
    }

    public static function fromBase64Xdr(string $base64Xdr) : XdrSCObject {
        $xdr = base64_decode($base64Xdr);
        $xdrBuffer = new XdrBuffer($xdr);
        return XdrSCObject::decode($xdrBuffer);
    }

    public function toBase64Xdr() : string {
        return base64_encode($this->encode());
    }

    public static function fromContractId(string $contractIdHex) : XdrSCObject {
        return XdrSCObject::fromHexString32($contractIdHex);
    }

    public static function fromWasmId(string $wasmIdHex) : XdrSCObject {
        return XdrSCObject::fromHexString32($wasmIdHex);
    }

    private static function fromHexString32(string $hex) : XdrSCObject {
        $result = new XdrSCObject(XdrSCObjectType::BYTES());
        $bytes = pack("H*", $hex);
        if (strlen($bytes) > 32) {
            $bytes = substr($bytes, -32);
        }
        $result->bin = new XdrDataValueMandatory($bytes);
        return $result;
    }

    /**
     * @return XdrSCObjectType
     */
    public function getType(): XdrSCObjectType
    {
        return $this->type;
    }

    /**
     * @param XdrSCObjectType $type
     */
    public function setType(XdrSCObjectType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return array|null
     */
    public function getVec(): ?array
    {
        return $this->vec;
    }

    /**
     * @param array|null $vec
     */
    public function setVec(?array $vec): void
    {
        $this->vec = $vec;
    }

    /**
     * @return array|null
     */
    public function getMap(): ?array
    {
        return $this->map;
    }

    /**
     * @param array|null $map
     */
    public function setMap(?array $map): void
    {
        $this->map = $map;
    }

    /**
     * @return int|null
     */
    public function getU64(): ?int
    {
        return $this->u64;
    }

    /**
     * @param int|null $u64
     */
    public function setU64(?int $u64): void
    {
        $this->u64 = $u64;
    }

    /**
     * @return int|null
     */
    public function getI64(): ?int
    {
        return $this->i64;
    }

    /**
     * @param int|null $i64
     */
    public function setI64(?int $i64): void
    {
        $this->i64 = $i64;
    }

    /**
     * @return XdrInt128Parts|null
     */
    public function getU128(): ?XdrInt128Parts
    {
        return $this->u128;
    }

    /**
     * @param XdrInt128Parts|null $u128
     */
    public function setU128(?XdrInt128Parts $u128): void
    {
        $this->u128 = $u128;
    }

    /**
     * @return XdrInt128Parts|null
     */
    public function getI128(): ?XdrInt128Parts
    {
        return $this->i128;
    }

    /**
     * @param XdrInt128Parts|null $i128
     */
    public function setI128(?XdrInt128Parts $i128): void
    {
        $this->i128 = $i128;
    }

    /**
     * @return XdrDataValueMandatory|null
     */
    public function getBin(): ?XdrDataValueMandatory
    {
        return $this->bin;
    }

    /**
     * @param XdrDataValueMandatory|null $bin
     */
    public function setBin(?XdrDataValueMandatory $bin): void
    {
        $this->bin = $bin;
    }

    /**
     * @return XdrSCContractCode|null
     */
    public function getContractCode(): ?XdrSCContractCode
    {
        return $this->contractCode;
    }

    /**
     * @param XdrSCContractCode|null $contractCode
     */
    public function setContractCode(?XdrSCContractCode $contractCode): void
    {
        $this->contractCode = $contractCode;
    }

    /**
     * @return XdrSCAddress|null
     */
    public function getAddress(): ?XdrSCAddress
    {
        return $this->address;
    }

    /**
     * @param XdrSCAddress|null $address
     */
    public function setAddress(?XdrSCAddress $address): void
    {
        $this->address = $address;
    }

    /**
     * @return XdrSCAddress|null
     */
    public function getNonceAddress(): ?XdrSCAddress
    {
        return $this->nonceAddress;
    }

    /**
     * @param XdrSCAddress|null $nonceAddress
     */
    public function setNonceAddress(?XdrSCAddress $nonceAddress): void
    {
        $this->nonceAddress = $nonceAddress;
    }

}