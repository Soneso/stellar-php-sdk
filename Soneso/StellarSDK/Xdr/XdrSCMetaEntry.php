<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCMetaEntry
{

    public XdrSCMetaKind $type;
    public ?XdrSCMetaV0 $v0;

    /**
     * @param XdrSCMetaKind $type
     */
    public function __construct(XdrSCMetaKind $type)
    {
        $this->type = $type;
    }


    public function encode(): string {
        $bytes = $this->type->encode();
        switch ($this->type->value) {
            case XdrSCMetaKind::SC_META_V0:
                $bytes .= $this->v0->encode();
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCMetaEntry
    {
        $result = new XdrSCMetaEntry(XdrSCMetaKind::decode($xdr));
        switch ($result->type->value) {
            case XdrSCMetaKind::SC_META_V0:
                $result->v0 = XdrSCMetaV0::decode($xdr);
                break;
        }
        return $result;
    }

    public static function fromBase64Xdr(String $base64Xdr) : XdrSCMetaEntry {
        $xdr = base64_decode($base64Xdr);
        $xdrBuffer = new XdrBuffer($xdr);
        return XdrSCMetaEntry::decode($xdrBuffer);
    }

    public function toBase64Xdr() : String {
        return base64_encode($this->encode());
    }

    /**
     * @return XdrSCMetaKind
     */
    public function getType(): XdrSCMetaKind
    {
        return $this->type;
    }

    /**
     * @param XdrSCMetaKind $type
     */
    public function setType(XdrSCMetaKind $type): void
    {
        $this->type = $type;
    }

    /**
     * @return XdrSCMetaV0|null
     */
    public function getV0(): ?XdrSCMetaV0
    {
        return $this->v0;
    }

    /**
     * @param XdrSCMetaV0|null $v0
     */
    public function setV0(?XdrSCMetaV0 $v0): void
    {
        $this->v0 = $v0;
    }
}