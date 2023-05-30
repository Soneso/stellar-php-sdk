<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCVal
{

    public XdrSCValType $type;
    public ?bool $b = null;
    public ?XdrSCStatus $error = null;
    public ?int $u32 = null;
    public ?int $i32 = null;
    public ?int $u64 = null;
    public ?int $i64 = null;
    public ?int $timepoint= null;
    public ?int $duration = null;
    public ?XdrUInt128Parts $u128 = null;
    public ?XdrInt128Parts $i128 = null;
    public ?XdrUInt256Parts $u256 = null;
    public ?XdrInt256Parts $i256 = null;
    public ?XdrDataValueMandatory $bytes = null;
    public ?String $str = null;
    public ?String $sym = null;
    public ?array $vec = null; // [XdrSCVal]
    public ?array $map = null; // [XdrSCMapEntry]
    public ?XdrSCContractExecutable $exec = null;
    public ?XdrSCAddress $address = null;
    public ?XdrSCNonceKey $nonceKey = null;


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
            case XdrSCValType::SCV_BOOL:
                $bytes .= XdrEncoder::boolean($this->b);
                break;
            case XdrSCValType::SCV_VOID:
            case XdrSCValType::SCV_LEDGER_KEY_CONTRACT_EXECUTABLE:
                break;
            case XdrSCValType::SCV_STATUS:
                $bytes .= $this->error->encode();
                break;
            case XdrSCValType::SCV_U32:
                $bytes .= XdrEncoder::unsignedInteger32($this->u32);
                break;
            case XdrSCValType::SCV_I32:
                $bytes .= XdrEncoder::integer32($this->i32);
                break;
            case XdrSCValType::SCV_U64:
                $bytes .= XdrEncoder::unsignedInteger64($this->u64);
                break;
            case XdrSCValType::SCV_I64:
                $bytes .= XdrEncoder::integer64($this->i64);
                break;
            case XdrSCValType::SCV_TIMEPOINT:
                $bytes .= XdrEncoder::unsignedInteger64($this->timepoint);
                break;
            case XdrSCValType::SCV_DURATION:
                $bytes .= XdrEncoder::unsignedInteger64($this->duration);
                break;
            case XdrSCValType::SCV_U128:
                $bytes .= $this->u128->encode();
                break;
            case XdrSCValType::SCV_I128:
                $bytes .= $this->i128->encode();
                break;
            case XdrSCValType::SCV_U256:
                $bytes .= $this->u256->encode();
                break;
            case XdrSCValType::SCV_I256:
                $bytes .= $this->i256->encode();
                break;
            case XdrSCValType::SCV_BYTES:
                $bytes .= $this->bytes->encode();
                break;
            case XdrSCValType::SCV_STRING:
                $bytes .= XdrEncoder::string($this->str);
                break;
            case XdrSCValType::SCV_SYMBOL:
                $bytes .= XdrEncoder::string($this->sym);
                break;
            case XdrSCValType::SCV_VEC:
                if ($this->vec != null) {
                    $bytes .= XdrEncoder::integer32(1);
                    $bytes .= XdrEncoder::integer32(count($this->vec));
                    foreach($this->vec as $val) {
                        if ($val instanceof XdrSCVal) {
                            $bytes .= $val->encode();
                        }
                    }
                } else {
                    $bytes .= XdrEncoder::integer32(0);
                }
                break;
            case XdrSCValType::SCV_MAP:
                if ($this->map != null) {
                    $bytes .= XdrEncoder::integer32(1);
                    $bytes .= XdrEncoder::integer32(count($this->map));
                    foreach($this->map as $val) {
                        if ($val instanceof XdrSCMapEntry) {
                            $bytes .= $val->encode();
                        }
                    }
                } else {
                    $bytes .= XdrEncoder::integer32(0);
                }
                break;
            case XdrSCValType::SCV_CONTRACT_EXECUTABLE:
                $bytes .= $this->exec->encode();
                break;
            case XdrSCValType::SCV_ADDRESS:
                $bytes .= $this->address->encode();
                break;
            case XdrSCValType::SCV_LEDGER_KEY_NONCE:
                $bytes .= $this->nonceKey->encode();
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::decode($xdr));
        switch ($result->type->value) {
            case XdrSCValType::SCV_BOOL:
                $result->b = $xdr->readBoolean();
                break;
            case XdrSCValType::SCV_VOID:
            case XdrSCValType::SCV_LEDGER_KEY_CONTRACT_EXECUTABLE:
                break;
            case XdrSCValType::SCV_STATUS:
                $result->error = XdrSCStatus::decode($xdr);
                break;
            case XdrSCValType::SCV_U32:
                $result->u32 = $xdr->readUnsignedInteger32();
                break;
            case XdrSCValType::SCV_I32:
                $result->i32 = $xdr->readInteger32();
                break;
            case XdrSCValType::SCV_U64:
                $result->u64 = $xdr->readUnsignedInteger64();
                break;
            case XdrSCValType::SCV_I64:
                $result->i64 = $xdr->readInteger64();
                break;
            case XdrSCValType::SCV_TIMEPOINT:
                $result->timepoint = $xdr->readUnsignedInteger64();
                break;
            case XdrSCValType::SCV_DURATION:
                $result->duration= $xdr->readUnsignedInteger64();
                break;
            case XdrSCValType::SCV_U128:
                $result->u128 = XdrUInt128Parts::decode($xdr);
                break;
            case XdrSCValType::SCV_I128:
                $result->i128 = XdrInt128Parts::decode($xdr);
                break;
            case XdrSCValType::SCV_U256:
                $result->u256 = XdrUInt256Parts::decode($xdr);
                break;
            case XdrSCValType::SCV_I256:
                $result->i256 = XdrInt256Parts::decode($xdr);
                break;
            case XdrSCValType::SCV_BYTES:
                $result->bytes = XdrDataValueMandatory::decode($xdr);
                break;
            case XdrSCValType::SCV_STRING:
                $result->str = $xdr->readString();
                break;
            case XdrSCValType::SCV_SYMBOL:
                $result->sym = $xdr->readString();
                break;
            case XdrSCValType::SCV_VEC:
                if ($xdr->readInteger32() == 1) {
                    $valCount = $xdr->readInteger32();
                    $arr = array();
                    for ($i = 0; $i < $valCount; $i++) {
                        array_push($arr, XdrSCVal::decode($xdr));
                    }
                    $result->vec = $arr;
                }
                break;
            case XdrSCValType::SCV_MAP:
                if ($xdr->readInteger32() == 1) {
                    $valCount = $xdr->readInteger32();
                    $arr = array();
                    for ($i = 0; $i < $valCount; $i++) {
                        array_push($arr, XdrSCMapEntry::decode($xdr));
                    }
                    $result->map = $arr;
                }
                break;
            case XdrSCValType::SCV_CONTRACT_EXECUTABLE:
                $result->exec = XdrSCContractExecutable::decode($xdr);
                break;
            case XdrSCValType::SCV_ADDRESS:
                $result->address = XdrSCAddress::decode($xdr);
                break;
            case XdrSCValType::SCV_LEDGER_KEY_NONCE:
                $result->nonceKey = XdrSCNonceKey::decode($xdr);
                break;
        }
        return $result;
    }


    public static function forTrue() : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::BOOL());
        $result->b = true;
        return $result;
    }

    public static function forFalse() : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::BOOL());
        $result->b = false;
        return $result;
    }

    public static function forBool(bool $b) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::BOOL());
        $result->b = $b;
        return $result;
    }

    public static function forVoid() : XdrSCVal {
        return new XdrSCVal(XdrSCValType::VOID());
    }

    public static function forStatus(XdrSCStatus $error) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::STATUS());
        $result->error = $error;
        return $result;
    }

    public static function forStatusOk() : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::STATUS());
        $result->error = XdrSCStatus::ok();
        return $result;
    }

    public static function forU32(int $u32) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::U32());
        $result->u32 = $u32;
        return $result;
    }

    public static function forI32(int $i32) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::I32());
        $result->i32 = $i32;
        return $result;
    }

    public static function forU64(int $u64) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::U64());
        $result->u64= $u64;
        return $result;
    }

    public static function forI64(int $i64) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::I64());
        $result->i64 = $i64;
        return $result;
    }

    public static function forTimepoint(int $timepoint) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::U64());
        $result->timepoint = $timepoint;
        return $result;
    }

    public static function forDuration(int $duration) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::U64());
        $result->duration = $duration;
        return $result;
    }

    public static function forU128(XdrUInt128Parts $parts) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::U128());
        $result->u128 = $parts;
        return $result;
    }

    public static function forU128Parts(int $hi, int $lo) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::U128());
        $result->u128 = new XdrUInt128Parts($hi, $lo);
        return $result;
    }

    public static function forI128(XdrInt128Parts $parts) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::I128());
        $result->i128 = $parts;
        return $result;
    }

    public static function forI128Parts(int $hi, int $lo) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::I128());
        $result->i128 = new XdrInt128Parts($hi, $lo);
        return $result;
    }

    public static function forU256(XdrUInt256Parts $parts) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::U256());
        $result->u256 = $parts;
        return $result;
    }

    public static function forI256(XdrInt256Parts $parts) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::I256());
        $result->i256 = $parts;
        return $result;
    }

    public static function forBytes(string $bytes) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::BYTES());
        $result->bytes = new XdrDataValueMandatory($bytes);
        return $result;
    }

    public static function forString(String $symbol) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::STRING());
        $result->sym = $symbol;
        return $result;
    }

    public static function forSymbol(String $symbol) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::SYMBOL());
        $result->sym = $symbol;
        return $result;
    }

    public static function forVec(array $vec) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::VEC());
        $result->vec = $vec;
        return $result;
    }

    public static function forMap(array $map) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::MAP());
        $result->map = $map;
        return $result;
    }

    public static function forContractExecutable(XdrSCContractExecutable $exec) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::CONTRACT_EXECUTABLE());
        $result->exec = $exec;
        return $result;
    }

    public static function forAddress(XdrSCAddress $address) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::ADDRESS());
        $result->address = $address;
        return $result;
    }

    public static function forLedgerKeyContractExecutable() : XdrSCVal {
        return new XdrSCVal(XdrSCValType::LEDGER_KEY_CONTRACT_EXECUTABLE());
    }

    public static function forNonceKey(XdrSCNonceKey $nonceKey) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::LEDGER_KEY_NONCE());
        $result->nonceKey = $nonceKey;
        return $result;
    }

    public static function forNonceKeyWithAddress(XdrSCAddress $address) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::LEDGER_KEY_NONCE());
        $result->nonceKey = new XdrSCNonceKey($address);
        return $result;
    }

    public static function forContractId(string $contractIdHex) : XdrSCVal {
        return XdrSCVal::forHexString32($contractIdHex);
    }

    public static function forWasmId(string $wasmIdHex) : XdrSCVal {
        return XdrSCVal::forHexString32($wasmIdHex);
    }

    private static function forHexString32(string $hex) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::BYTES());
        $bytes = pack("H*", $hex);
        if (strlen($bytes) > 32) {
            $bytes = substr($bytes, -32);
        }
        $result->bytes = new XdrDataValueMandatory($bytes);
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
     * @return bool|null
     */
    public function getBool(): ?bool
    {
        return $this->b;
    }

    /**
     * @return XdrSCStatus|null
     */
    public function getError(): ?XdrSCStatus
    {
        return $this->error;
    }

    /**
     * @return int|null
     */
    public function getU32(): ?int
    {
        return $this->u32;
    }

    /**
     * @return int|null
     */
    public function getI32(): ?int
    {
        return $this->i32;
    }

    /**
     * @return int|null
     */
    public function getU64(): ?int
    {
        return $this->u64;
    }

    /**
     * @return int|null
     */
    public function getI64(): ?int
    {
        return $this->i64;
    }

    /**
     * @return int|null
     */
    public function getTimepoint(): ?int
    {
        return $this->timepoint;
    }

    /**
     * @return int|null
     */
    public function getDuration(): ?int
    {
        return $this->duration;
    }

    /**
     * @return XdrUInt128Parts|null
     */
    public function getU128(): ?XdrUInt128Parts
    {
        return $this->u128;
    }

    /**
     * @return XdrInt128Parts|null
     */
    public function getI128(): ?XdrInt128Parts
    {
        return $this->i128;
    }

    /**
     * @return XdrUInt256Parts
     */
    public function getU256(): XdrUInt256Parts
    {
        return $this->u256;
    }

    /**
     * @return XdrInt256Parts
     */
    public function getI256(): XdrInt256Parts
    {
        return $this->i256;
    }

    /**
     * @return XdrDataValueMandatory|null
     */
    public function getBytes(): ?XdrDataValueMandatory
    {
        return $this->bytes;
    }

    /**
     * @return String|null
     */
    public function getStr(): ?string
    {
        return $this->str;
    }

    /**
     * @return String|null
     */
    public function getSym(): ?string
    {
        return $this->sym;
    }

    /**
     * @return array|null
     */
    public function getVec(): ?array
    {
        return $this->vec;
    }

    /**
     * @return array|null
     */
    public function getMap(): ?array
    {
        return $this->map;
    }

    /**
     * @return XdrSCContractExecutable|null
     */
    public function getExec(): ?XdrSCContractExecutable
    {
        return $this->exec;
    }

    /**
     * @return XdrSCAddress|null
     */
    public function getAddress(): ?XdrSCAddress
    {
        return $this->address;
    }

    /**
     * @return XdrSCNonceKey|null
     */
    public function getNonceKey(): ?XdrSCNonceKey
    {
        return $this->nonceKey;
    }

}