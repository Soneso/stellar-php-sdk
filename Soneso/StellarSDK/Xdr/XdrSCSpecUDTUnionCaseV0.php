<?php declare(strict_types=1);

namespace Soneso\StellarSDK\Xdr;

class XdrSCSpecUDTUnionCaseV0 extends XdrSCSpecUDTUnionCaseV0Base
{
    public static function forVoidCase(XdrSCSpecUDTUnionCaseVoidV0 $case): XdrSCSpecUDTUnionCaseV0 {
        $result = new XdrSCSpecUDTUnionCaseV0(XdrSCSpecUDTUnionCaseV0Kind::forVoid());
        $result->voidCase = $case;
        return $result;
    }

    public static function forTupleCase(XdrSCSpecUDTUnionCaseTupleV0 $case): XdrSCSpecUDTUnionCaseV0 {
        $result = new XdrSCSpecUDTUnionCaseV0(XdrSCSpecUDTUnionCaseV0Kind::forTuple());
        $result->tupleCase = $case;
        return $result;
    }
}
