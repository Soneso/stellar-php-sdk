<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCSpecEntry
{

    public XdrSCSpecEntryKind $type;
    public ?XdrSCSpecFunctionV0 $functionV0 = null;
    public ?XdrSCSpecUDTStructV0 $udtStructV0 = null;
    public ?XdrSCSpecUDTUnionV0 $udtUnionV0 = null;
    public ?XdrSCSpecUDTEnumV0 $udtEnumV0 = null;
    public ?XdrSCSpecUDTErrorEnumV0 $udtErrorEnumV0 = null;

    /**
     * @param XdrSCSpecEntryKind $type
     */
    public function __construct(XdrSCSpecEntryKind $type)
    {
        $this->type = $type;
    }

    public function encode(): string {
        $bytes = $this->type->encode();

        switch ($this->type->value) {
            case XdrSCSpecEntryKind::SC_SPEC_ENTRY_FUNCTION_V0:
                $bytes .= $this->functionV0->encode();
                break;
            case XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_STRUCT_V0:
                $bytes .= $this->udtStructV0->encode();
                break;
            case XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_UNION_V0:
                $bytes .= $this->udtUnionV0->encode();
                break;
            case XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_ENUM_V0:
                $bytes .= $this->udtEnumV0->encode();
                break;
            case XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_ERROR_ENUM_V0:
                $bytes .= $this->udtErrorEnumV0->encode();
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCSpecEntry {
        $result = new XdrSCSpecEntry(XdrSCSpecEntryKind::decode($xdr));
        switch ($result->type->value) {
            case XdrSCSpecEntryKind::SC_SPEC_ENTRY_FUNCTION_V0:
                $result->functionV0 = XdrSCSpecFunctionV0::decode($xdr);
                break;
            case XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_STRUCT_V0:
                $result->udtStructV0 = XdrSCSpecUDTStructV0::decode($xdr);
                break;
            case XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_UNION_V0:
                $result->udtUnionV0 = XdrSCSpecUDTUnionV0::decode($xdr);
                break;
            case XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_ENUM_V0:
                $result->udtEnumV0 = XdrSCSpecUDTEnumV0::decode($xdr);
                break;
            case XdrSCSpecEntryKind::SC_SPEC_ENTRY_UDT_ERROR_ENUM_V0:
                $result->udtErrorEnumV0 = XdrSCSpecUDTErrorEnumV0::decode($xdr);
                break;
        }
        return $result;
    }

    public static function fromBase64Xdr(String $base64Xdr) : XdrSCSpecEntry {
        $xdr = base64_decode($base64Xdr);
        $xdrBuffer = new XdrBuffer($xdr);
        return XdrSCSpecEntry::decode($xdrBuffer);
    }

    public function toBase64Xdr() : String {
        return base64_encode($this->encode());
    }

    /**
     * @return XdrSCSpecEntryKind
     */
    public function getType(): XdrSCSpecEntryKind
    {
        return $this->type;
    }

    /**
     * @param XdrSCSpecEntryKind $type
     */
    public function setType(XdrSCSpecEntryKind $type): void
    {
        $this->type = $type;
    }

    /**
     * @return XdrSCSpecFunctionV0|null
     */
    public function getFunctionV0(): ?XdrSCSpecFunctionV0
    {
        return $this->functionV0;
    }

    /**
     * @param XdrSCSpecFunctionV0|null $functionV0
     */
    public function setFunctionV0(?XdrSCSpecFunctionV0 $functionV0): void
    {
        $this->functionV0 = $functionV0;
    }

    /**
     * @return XdrSCSpecUDTStructV0|null
     */
    public function getUdtStructV0(): ?XdrSCSpecUDTStructV0
    {
        return $this->udtStructV0;
    }

    /**
     * @param XdrSCSpecUDTStructV0|null $udtStructV0
     */
    public function setUdtStructV0(?XdrSCSpecUDTStructV0 $udtStructV0): void
    {
        $this->udtStructV0 = $udtStructV0;
    }

    /**
     * @return XdrSCSpecUDTUnionV0|null
     */
    public function getUdtUnionV0(): ?XdrSCSpecUDTUnionV0
    {
        return $this->udtUnionV0;
    }

    /**
     * @param XdrSCSpecUDTUnionV0|null $udtUnionV0
     */
    public function setUdtUnionV0(?XdrSCSpecUDTUnionV0 $udtUnionV0): void
    {
        $this->udtUnionV0 = $udtUnionV0;
    }

    /**
     * @return XdrSCSpecUDTEnumV0|null
     */
    public function getUdtEnumV0(): ?XdrSCSpecUDTEnumV0
    {
        return $this->udtEnumV0;
    }

    /**
     * @param XdrSCSpecUDTEnumV0|null $udtEnumV0
     */
    public function setUdtEnumV0(?XdrSCSpecUDTEnumV0 $udtEnumV0): void
    {
        $this->udtEnumV0 = $udtEnumV0;
    }

    /**
     * @return XdrSCSpecUDTErrorEnumV0|null
     */
    public function getUdtErrorEnumV0(): ?XdrSCSpecUDTErrorEnumV0
    {
        return $this->udtErrorEnumV0;
    }

    /**
     * @param XdrSCSpecUDTErrorEnumV0|null $udtErrorEnumV0
     */
    public function setUdtErrorEnumV0(?XdrSCSpecUDTErrorEnumV0 $udtErrorEnumV0): void
    {
        $this->udtErrorEnumV0 = $udtErrorEnumV0;
    }

}