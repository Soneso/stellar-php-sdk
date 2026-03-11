<?php declare(strict_types=1);

namespace Soneso\StellarSDK\Xdr;

class XdrSCSpecEntry extends XdrSCSpecEntryBase
{
    public static function forFunctionV0(XdrSCSpecFunctionV0 $function): XdrSCSpecEntry {
        $entry = new XdrSCSpecEntry(XdrSCSpecEntryKind::FUNCTION_V0());
        $entry->functionV0 = $function;
        return $entry;
    }

    public static function forUDTStructV0(XdrSCSpecUDTStructV0 $struct): XdrSCSpecEntry {
        $entry = new XdrSCSpecEntry(XdrSCSpecEntryKind::UDT_STRUCT_V0());
        $entry->udtStructV0 = $struct;
        return $entry;
    }

    public static function forUDTUnionV0(XdrSCSpecUDTUnionV0 $union): XdrSCSpecEntry {
        $entry = new XdrSCSpecEntry(XdrSCSpecEntryKind::UDT_UNION_V0());
        $entry->udtUnionV0 = $union;
        return $entry;
    }

    public static function forUDTEnumV0(XdrSCSpecUDTEnumV0 $enum): XdrSCSpecEntry {
        $entry = new XdrSCSpecEntry(XdrSCSpecEntryKind::UDT_ENUM_V0());
        $entry->udtEnumV0 = $enum;
        return $entry;
    }

    public static function forUDTErrorEnumV0(XdrSCSpecUDTErrorEnumV0 $errorEnum): XdrSCSpecEntry {
        $entry = new XdrSCSpecEntry(XdrSCSpecEntryKind::UDT_ERROR_ENUM_V0());
        $entry->udtErrorEnumV0 = $errorEnum;
        return $entry;
    }

    public static function forEventV0(XdrSCSpecEventV0 $event): XdrSCSpecEntry {
        $entry = new XdrSCSpecEntry(XdrSCSpecEntryKind::EVENT_V0());
        $entry->eventV0 = $event;
        return $entry;
    }
}
