<?php declare(strict_types=1);

namespace Soneso\StellarSDK\Xdr;

class XdrSCSpecTypeDef extends XdrSCSpecTypeDefBase
{
    public static function forOption(XdrSCSpecTypeOption $option): XdrSCSpecTypeDef {
        $result = new XdrSCSpecTypeDef(XdrSCSpecType::OPTION());
        $result->option = $option;
        return $result;
    }

    public static function forResult(XdrSCSpecTypeResult $result): XdrSCSpecTypeDef {
        $res = new XdrSCSpecTypeDef(XdrSCSpecType::RESULT());
        $res->result = $result;
        return $res;
    }

    public static function forVec(XdrSCSpecTypeVec $vec): XdrSCSpecTypeDef {
        $result = new XdrSCSpecTypeDef(XdrSCSpecType::VEC());
        $result->vec = $vec;
        return $result;
    }

    public static function forMap(XdrSCSpecTypeMap $map): XdrSCSpecTypeDef {
        $result = new XdrSCSpecTypeDef(XdrSCSpecType::MAP());
        $result->map = $map;
        return $result;
    }

    public static function forTuple(XdrSCSpecTypeTuple $tuple): XdrSCSpecTypeDef {
        $result = new XdrSCSpecTypeDef(XdrSCSpecType::TUPLE());
        $result->tuple = $tuple;
        return $result;
    }

    public static function forBytesN(XdrSCSpecTypeBytesN $bytesN): XdrSCSpecTypeDef {
        $result = new XdrSCSpecTypeDef(XdrSCSpecType::BYTES_N());
        $result->bytesN = $bytesN;
        return $result;
    }

    public static function forUDT(XdrSCSpecTypeUDT $udt): XdrSCSpecTypeDef {
        $result = new XdrSCSpecTypeDef(XdrSCSpecType::UDT());
        $result->udt = $udt;
        return $result;
    }

    public static function BOOL(): XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::BOOL());
    }

    public static function VOID(): XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::VOID());
    }

    public static function STATUS(): XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::ERROR());
    }

    public static function U32(): XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::U32());
    }

    public static function I32(): XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::I32());
    }

    public static function U64(): XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::U64());
    }

    public static function I64(): XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::I64());
    }

    public static function TIMEPOINT(): XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::TIMEPOINT());
    }

    public static function DURATION(): XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::DURATION());
    }

    public static function U128(): XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::U128());
    }

    public static function I128(): XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::I128());
    }

    public static function U256(): XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::U256());
    }

    public static function I256(): XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::I256());
    }

    public static function BYTES(): XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::BYTES());
    }

    public static function STRING(): XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::STRING());
    }

    public static function SYMBOL(): XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::SYMBOL());
    }

    public static function ADDRESS(): XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::ADDRESS());
    }

    public static function MUXED_ADDRESS(): XdrSCSpecTypeDef {
        return new XdrSCSpecTypeDef(XdrSCSpecType::MUXED_ADDRESS());
    }
}
