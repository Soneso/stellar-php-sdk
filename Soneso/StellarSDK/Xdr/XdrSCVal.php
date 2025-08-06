<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use GMP;


class XdrSCVal
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

    public static function decode(XdrBuffer $xdr):  XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::decode($xdr));
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

    public static function forError(XdrSCError $error) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::ERROR());
        $result->error = $error;
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
        $result = new XdrSCVal(XdrSCValType::TIMEPOINT());
        $result->timepoint = $timepoint;
        return $result;
    }

    public static function forDuration(int $duration) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::DURATION());
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

    public static function forString(String $str) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::STRING());
        $result->str = $str;
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

    public static function forContractInstance(XdrSCContractInstance $instance) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::SCV_CONTRACT_INSTANCE());
        $result->instance = $instance;
        return $result;
    }

    public static function forAddress(XdrSCAddress $address) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::ADDRESS());
        $result->address = $address;
        return $result;
    }

    public static function forLedgerKeyContractInstance() : XdrSCVal {
        return new XdrSCVal(XdrSCValType::SCV_LEDGER_KEY_CONTRACT_INSTANCE());
    }

    public static function forLedgerNonceKey(XdrSCNonceKey $nonceKey) : XdrSCVal {
        $result = new XdrSCVal(XdrSCValType::LEDGER_KEY_NONCE());
        $result->nonceKey = $nonceKey;
        return $result;
    }

    /**
     * Creates an XdrSCVal for unsigned 128-bit BigInt.
     * @param GMP|string|int $value The BigInt value (GMP object, string, or int)
     * @return XdrSCVal
     * @throws \InvalidArgumentException if value is negative or out of range
     */
    public static function forU128BigInt($value) : XdrSCVal {
        $bigInt = self::normalizeToBigInt($value);
        
        // Check if value is in valid range for U128 (0 to 2^128-1)
        if (gmp_cmp($bigInt, 0) < 0) {
            throw new \InvalidArgumentException("U128 value cannot be negative");
        }
        $maxU128 = gmp_sub(gmp_pow(2, 128), 1);
        if (gmp_cmp($bigInt, $maxU128) > 0) {
            throw new \InvalidArgumentException("Value exceeds U128 maximum");
        }
        
        list($hi, $lo) = self::bigInt128Parts($bigInt);
        return self::forU128Parts($hi, $lo);
    }

    /**
     * Creates an XdrSCVal for signed 128-bit BigInt.
     * @param GMP|string|int $value The BigInt value (GMP object, string, or int)
     * @return XdrSCVal
     * @throws \InvalidArgumentException if value is out of range
     */
    public static function forI128BigInt($value) : XdrSCVal {
        $bigInt = self::normalizeToBigInt($value);
        
        // Check if value is in valid range for I128 (-2^127 to 2^127-1)
        $minI128 = gmp_neg(gmp_pow(2, 127));
        $maxI128 = gmp_sub(gmp_pow(2, 127), 1);
        if (gmp_cmp($bigInt, $minI128) < 0 || gmp_cmp($bigInt, $maxI128) > 0) {
            throw new \InvalidArgumentException("Value out of I128 range");
        }
        
        list($hi, $lo) = self::bigInt128Parts($bigInt);
        return self::forI128Parts($hi, $lo);
    }

    /**
     * Creates an XdrSCVal for unsigned 256-bit BigInt.
     * @param GMP|string|int $value The BigInt value (GMP object, string, or int)
     * @return XdrSCVal
     * @throws \InvalidArgumentException if value is negative or out of range
     */
    public static function forU256BigInt($value) : XdrSCVal {
        $bigInt = self::normalizeToBigInt($value);
        
        // Check if value is in valid range for U256 (0 to 2^256-1)
        if (gmp_cmp($bigInt, 0) < 0) {
            throw new \InvalidArgumentException("U256 value cannot be negative");
        }
        $maxU256 = gmp_sub(gmp_pow(2, 256), 1);
        if (gmp_cmp($bigInt, $maxU256) > 0) {
            throw new \InvalidArgumentException("Value exceeds U256 maximum");
        }
        
        list($hihi, $hilo, $lohi, $lolo) = self::bigInt256Parts($bigInt);
        $result = new XdrSCVal(XdrSCValType::U256());
        $result->u256 = new XdrUInt256Parts($hihi, $hilo, $lohi, $lolo);
        return $result;
    }

    /**
     * Creates an XdrSCVal for signed 256-bit BigInt.
     * @param GMP|string|int $value The BigInt value (GMP object, string, or int)
     * @return XdrSCVal
     * @throws \InvalidArgumentException if value is out of range
     */
    public static function forI256BigInt($value) : XdrSCVal {
        $bigInt = self::normalizeToBigInt($value);
        
        // Check if value is in valid range for I256 (-2^255 to 2^255-1)
        $minI256 = gmp_neg(gmp_pow(2, 255));
        $maxI256 = gmp_sub(gmp_pow(2, 255), 1);
        if (gmp_cmp($bigInt, $minI256) < 0 || gmp_cmp($bigInt, $maxI256) > 0) {
            throw new \InvalidArgumentException("Value out of I256 range");
        }
        
        list($hihi, $hilo, $lohi, $lolo) = self::bigInt256Parts($bigInt);
        $result = new XdrSCVal(XdrSCValType::I256());
        $result->i256 = new XdrInt256Parts($hihi, $hilo, $lohi, $lolo);
        return $result;
    }

    /**
     * Converts this XdrSCVal to BigInt (GMP).
     * Only supports SCV_U128, SCV_I128, SCV_U256, and SCV_I256 types.
     * @return GMP|null GMP object or null for unsupported types
     */
    public function toBigInt() : ?GMP {
        switch ($this->type->value) {
            case XdrSCValType::SCV_U128:
                if ($this->u128 !== null) {
                    return self::bigIntFrom128Parts($this->u128->hi, $this->u128->lo, false);
                }
                break;
            case XdrSCValType::SCV_I128:
                if ($this->i128 !== null) {
                    return self::bigIntFrom128Parts($this->i128->hi, $this->i128->lo, true);
                }
                break;
            case XdrSCValType::SCV_U256:
                if ($this->u256 !== null) {
                    return self::bigIntFrom256Parts(
                        $this->u256->hiHi, $this->u256->hiLo,
                        $this->u256->loHi, $this->u256->loLo, false
                    );
                }
                break;
            case XdrSCValType::SCV_I256:
                if ($this->i256 !== null) {
                    return self::bigIntFrom256Parts(
                        $this->i256->hiHi, $this->i256->hiLo,
                        $this->i256->loHi, $this->i256->loLo, true
                    );
                }
                break;
        }
        return null;
    }

    /**
     * Normalizes input to GMP object.
     * @param GMP|string|int $value
     * @return GMP
     */
    private static function normalizeToBigInt($value) : GMP {
        if (is_object($value) && $value instanceof GMP) {
            return $value;
        }
        // gmp_init always returns a GMP object in PHP 8+
        return gmp_init(strval($value));
    }

    /**
     * Splits a BigInt into 128-bit hi/lo 64-bit parts.
     * @param GMP $value
     * @return array [hi, lo] as integers
     */
    private static function bigInt128Parts(GMP $value) : array {
        $isNegative = gmp_cmp($value, 0) < 0;
        
        // For negative numbers, work with two's complement
        if ($isNegative) {
            $value = gmp_add($value, gmp_pow(2, 128));
        }
        
        // Split into two 64-bit parts
        $mask64 = gmp_sub(gmp_pow(2, 64), 1);
        $lo = gmp_and($value, $mask64);
        $hi = gmp_div_q($value, gmp_pow(2, 64));
        
        // Convert to signed PHP integers
        $loStr = gmp_strval($lo);
        $hiStr = gmp_strval($hi);
        
        // Handle overflow for PHP's signed 64-bit integers
        $maxInt63 = gmp_pow(2, 63);
        
        // Lo is always treated as unsigned, but PHP int is signed
        if (gmp_cmp($lo, gmp_sub($maxInt63, 1)) > 0) {
            $loInt = intval(gmp_strval(gmp_sub($lo, gmp_pow(2, 64))));
        } else {
            $loInt = intval($loStr);
        }
        
        // Hi might need to be treated as signed
        if (gmp_cmp($hi, gmp_sub($maxInt63, 1)) > 0) {
            $hiInt = intval(gmp_strval(gmp_sub($hi, gmp_pow(2, 64))));
        } else {
            $hiInt = intval($hiStr);
        }
        
        return [$hiInt, $loInt];
    }

    /**
     * Splits a BigInt into 256-bit hihi/hilo/lohi/lolo 64-bit parts.
     * @param GMP $value
     * @return array [hihi, hilo, lohi, lolo] as integers
     */
    private static function bigInt256Parts(GMP $value) : array {
        $isNegative = gmp_cmp($value, 0) < 0;
        
        // For negative numbers, work with two's complement
        if ($isNegative) {
            $value = gmp_add($value, gmp_pow(2, 256));
        }
        
        // Split into four 64-bit parts
        $mask64 = gmp_sub(gmp_pow(2, 64), 1);
        $shift64 = gmp_pow(2, 64);
        $shift128 = gmp_pow(2, 128);
        $shift192 = gmp_pow(2, 192);
        
        $lolo = gmp_and($value, $mask64);
        $lohi = gmp_and(gmp_div_q($value, $shift64), $mask64);
        $hilo = gmp_and(gmp_div_q($value, $shift128), $mask64);
        $hihi = gmp_div_q($value, $shift192);
        
        // Handle overflow for PHP's signed 64-bit integers
        $maxInt63 = gmp_pow(2, 63);
        
        // Convert each part, handling unsigned to signed conversion
        if (gmp_cmp($lolo, gmp_sub($maxInt63, 1)) > 0) {
            $loloInt = intval(gmp_strval(gmp_sub($lolo, gmp_pow(2, 64))));
        } else {
            $loloInt = intval(gmp_strval($lolo));
        }
        
        if (gmp_cmp($lohi, gmp_sub($maxInt63, 1)) > 0) {
            $lohiInt = intval(gmp_strval(gmp_sub($lohi, gmp_pow(2, 64))));
        } else {
            $lohiInt = intval(gmp_strval($lohi));
        }
        
        if (gmp_cmp($hilo, gmp_sub($maxInt63, 1)) > 0) {
            $hiloInt = intval(gmp_strval(gmp_sub($hilo, gmp_pow(2, 64))));
        } else {
            $hiloInt = intval(gmp_strval($hilo));
        }
        
        if (gmp_cmp($hihi, gmp_sub($maxInt63, 1)) > 0) {
            $hihiInt = intval(gmp_strval(gmp_sub($hihi, gmp_pow(2, 64))));
        } else {
            $hihiInt = intval(gmp_strval($hihi));
        }
        
        return [$hihiInt, $hiloInt, $lohiInt, $loloInt];
    }


    /**
     * Converts 128-bit parts back to BigInt.
     * @param int $hi High 64 bits
     * @param int $lo Low 64 bits
     * @param bool $signed Whether to treat as signed
     * @return GMP
     */
    private static function bigIntFrom128Parts($hi, $lo, $signed) : GMP {
        // Convert parts to unsigned GMP values
        $hiGmp = gmp_init(sprintf('%u', $hi));
        $loGmp = gmp_init(sprintf('%u', $lo));
        
        // Combine parts: result = hi * 2^64 + lo
        $result = gmp_add(gmp_mul($hiGmp, gmp_pow(2, 64)), $loGmp);
        
        // For signed types, check if the value should be negative
        if ($signed) {
            $max128 = gmp_pow(2, 128);
            $half128 = gmp_pow(2, 127);
            
            // If the value is >= 2^127, it's negative in two's complement
            if (gmp_cmp($result, $half128) >= 0) {
                $result = gmp_sub($result, $max128);
            }
        }
        
        return $result;
    }

    /**
     * Converts 256-bit parts back to BigInt.
     * @param int $hihi Highest 64 bits
     * @param int $hilo High-low 64 bits
     * @param int $lohi Low-high 64 bits
     * @param int $lolo Lowest 64 bits
     * @param bool $signed Whether to treat as signed
     * @return GMP
     */
    private static function bigIntFrom256Parts($hihi, $hilo, $lohi, $lolo, $signed) : GMP {
        // Convert parts to unsigned GMP values
        $hihiGmp = gmp_init(sprintf('%u', $hihi));
        $hiloGmp = gmp_init(sprintf('%u', $hilo));
        $lohiGmp = gmp_init(sprintf('%u', $lohi));
        $loloGmp = gmp_init(sprintf('%u', $lolo));
        
        // Combine parts: result = hihi * 2^192 + hilo * 2^128 + lohi * 2^64 + lolo
        $result = gmp_add(
            gmp_add(
                gmp_mul($hihiGmp, gmp_pow(2, 192)),
                gmp_mul($hiloGmp, gmp_pow(2, 128))
            ),
            gmp_add(
                gmp_mul($lohiGmp, gmp_pow(2, 64)),
                $loloGmp
            )
        );
        
        // For signed types, check if the value should be negative
        if ($signed) {
            $max256 = gmp_pow(2, 256);
            $half256 = gmp_pow(2, 255);
            
            // If the value is >= 2^255, it's negative in two's complement
            if (gmp_cmp($result, $half256) >= 0) {
                $result = gmp_sub($result, $max256);
            }
        }
        
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
     * @param XdrSCValType $type
     */
    public function setType(XdrSCValType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return bool|null
     */
    public function getB(): ?bool
    {
        return $this->b;
    }

    /**
     * @param bool|null $b
     */
    public function setB(?bool $b): void
    {
        $this->b = $b;
    }

    /**
     * @return XdrSCError|null
     */
    public function getError(): ?XdrSCError
    {
        return $this->error;
    }

    /**
     * @param XdrSCError|null $error
     */
    public function setError(?XdrSCError $error): void
    {
        $this->error = $error;
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
     * @return int|null
     */
    public function getTimepoint(): ?int
    {
        return $this->timepoint;
    }

    /**
     * @param int|null $timepoint
     */
    public function setTimepoint(?int $timepoint): void
    {
        $this->timepoint = $timepoint;
    }

    /**
     * @return int|null
     */
    public function getDuration(): ?int
    {
        return $this->duration;
    }

    /**
     * @param int|null $duration
     */
    public function setDuration(?int $duration): void
    {
        $this->duration = $duration;
    }

    /**
     * @return XdrUInt128Parts|null
     */
    public function getU128(): ?XdrUInt128Parts
    {
        return $this->u128;
    }

    /**
     * @param XdrUInt128Parts|null $u128
     */
    public function setU128(?XdrUInt128Parts $u128): void
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
     * @return XdrUInt256Parts|null
     */
    public function getU256(): ?XdrUInt256Parts
    {
        return $this->u256;
    }

    /**
     * @param XdrUInt256Parts|null $u256
     */
    public function setU256(?XdrUInt256Parts $u256): void
    {
        $this->u256 = $u256;
    }

    /**
     * @return XdrInt256Parts|null
     */
    public function getI256(): ?XdrInt256Parts
    {
        return $this->i256;
    }

    /**
     * @param XdrInt256Parts|null $i256
     */
    public function setI256(?XdrInt256Parts $i256): void
    {
        $this->i256 = $i256;
    }

    /**
     * @return XdrDataValueMandatory|null
     */
    public function getBytes(): ?XdrDataValueMandatory
    {
        return $this->bytes;
    }

    /**
     * @param XdrDataValueMandatory|null $bytes
     */
    public function setBytes(?XdrDataValueMandatory $bytes): void
    {
        $this->bytes = $bytes;
    }

    /**
     * @return String|null
     */
    public function getStr(): ?string
    {
        return $this->str;
    }

    /**
     * @param String|null $str
     */
    public function setStr(?string $str): void
    {
        $this->str = $str;
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
     * @return array<XdrSCVal>|null
     */
    public function getVec(): ?array
    {
        return $this->vec;
    }

    /**
     * @param array<XdrSCVal>|null $vec
     */
    public function setVec(?array $vec): void
    {
        $this->vec = $vec;
    }

    /**
     * @return array<XdrSCMapEntry>|null
     */
    public function getMap(): ?array
    {
        return $this->map;
    }

    /**
     * @param array<XdrSCMapEntry>|null $map
     */
    public function setMap(?array $map): void
    {
        $this->map = $map;
    }

    /**
     * @return XdrSCContractInstance|null
     */
    public function getInstance(): ?XdrSCContractInstance
    {
        return $this->instance;
    }

    /**
     * @param XdrSCContractInstance|null $instance
     */
    public function setInstance(?XdrSCContractInstance $instance): void
    {
        $this->instance = $instance;
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
     * @return XdrSCNonceKey|null
     */
    public function getNonceKey(): ?XdrSCNonceKey
    {
        return $this->nonceKey;
    }

    /**
     * @param XdrSCNonceKey|null $nonceKey
     */
    public function setNonceKey(?XdrSCNonceKey $nonceKey): void
    {
        $this->nonceKey = $nonceKey;
    }

}