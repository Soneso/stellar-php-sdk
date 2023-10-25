<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrExtendFootprintTTLOp
{
    public XdrExtensionPoint $ext;
    public int $extendTo;

    /**
     * @param XdrExtensionPoint $ext
     * @param int $extendTo
     */
    public function __construct(XdrExtensionPoint $ext, int $extendTo)
    {
        $this->ext = $ext;
        $this->extendTo = $extendTo;
    }


    public function encode(): string {
        $bytes = $this->ext->encode();
        $bytes .= XdrEncoder::unsignedInteger32($this->extendTo);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrExtendFootprintTTLOp {
        $ext = XdrExtensionPoint::decode($xdr);
        $extendTo = $xdr->readUnsignedInteger32();

        return new XdrExtendFootprintTTLOp($ext, $extendTo);
    }

    /**
     * @return XdrExtensionPoint
     */
    public function getExt(): XdrExtensionPoint
    {
        return $this->ext;
    }

    /**
     * @param XdrExtensionPoint $ext
     */
    public function setExt(XdrExtensionPoint $ext): void
    {
        $this->ext = $ext;
    }

    /**
     * @return int
     */
    public function getExtendTo(): int
    {
        return $this->extendTo;
    }

    /**
     * @param int $extendTo
     */
    public function setExtendTo(int $extendTo): void
    {
        $this->extendTo = $extendTo;
    }

}
