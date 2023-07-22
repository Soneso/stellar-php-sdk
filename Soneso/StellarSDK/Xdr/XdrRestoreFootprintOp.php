<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrRestoreFootprintOp
{
    public XdrExtensionPoint $ext;

    /**
     * @param XdrExtensionPoint $ext
     */
    public function __construct(XdrExtensionPoint $ext)
    {
        $this->ext = $ext;
    }


    public function encode(): string {
        return $this->ext->encode();
    }

    public static function decode(XdrBuffer $xdr):  XdrRestoreFootprintOp {
        return new XdrRestoreFootprintOp(XdrExtensionPoint::decode($xdr));
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

}
