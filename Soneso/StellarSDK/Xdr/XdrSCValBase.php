<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use InvalidArgumentException;


class XdrSCValBase
{

    public XdrSCValType $type;
    public ?bool $b = null;
    public ?XdrSCError $error = null;
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
    /**
     * @var array<XdrSCVal>|null
     */
    public ?array $vec = null;
    /**
     * @var array<XdrSCMapEntry>|null
     */
    public ?array $map = null;
    public ?XdrSCContractInstance $instance = null;
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
            case XdrSCValType::SCV_LEDGER_KEY_CONTRACT_INSTANCE:
                break;
            case XdrSCValType::SCV_ERROR:
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
                if ($this->vec !== null) {
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
                if ($this->map !== null) {
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
            case XdrSCValType::SCV_CONTRACT_INSTANCE:
                $bytes .= $this->instance->encode();
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

    public static function decode(XdrBuffer $xdr): static {
        $result = new static(XdrSCValType::decode($xdr));
        switch ($result->type->value) {
            case XdrSCValType::SCV_BOOL:
                $result->b = $xdr->readBoolean();
                break;
            case XdrSCValType::SCV_VOID:
            case XdrSCValType::SCV_LEDGER_KEY_CONTRACT_INSTANCE:
                break;
            case XdrSCValType::SCV_ERROR:
                $result->error = XdrSCError::decode($xdr);
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
            case XdrSCValType::SCV_CONTRACT_INSTANCE:
                $result->instance = XdrSCContractInstance::decode($xdr);
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

    public static function fromBase64Xdr(String $base64Xdr) : static {
        $xdr = base64_decode($base64Xdr, true);
        if ($xdr === false) {
            throw new InvalidArgumentException('Invalid base64-encoded XDR');
        }
        $xdrBuffer = new XdrBuffer($xdr);
        return static::decode($xdrBuffer);
    }

    public function toBase64Xdr() : String {
        return base64_encode($this->encode());
    }

}
