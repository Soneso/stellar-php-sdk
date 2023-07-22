<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrBumpFootprintExpirationOp
{
    public XdrExtensionPoint $ext;
    public int $ledgersToExpire;

    /**
     * @param XdrExtensionPoint $ext
     * @param int $ledgersToExpire
     */
    public function __construct(XdrExtensionPoint $ext, int $ledgersToExpire)
    {
        $this->ext = $ext;
        $this->ledgersToExpire = $ledgersToExpire;
    }


    public function encode(): string {
        $bytes = $this->ext->encode();
        $bytes .= XdrEncoder::unsignedInteger32($this->ledgersToExpire);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrBumpFootprintExpirationOp {
        $ext = XdrExtensionPoint::decode($xdr);
        $ledgersToExpire = $xdr->readUnsignedInteger32();

        return new XdrBumpFootprintExpirationOp($ext, $ledgersToExpire);
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
    public function getLedgersToExpire(): int
    {
        return $this->ledgersToExpire;
    }

    /**
     * @param int $ledgersToExpire
     */
    public function setLedgersToExpire(int $ledgersToExpire): void
    {
        $this->ledgersToExpire = $ledgersToExpire;
    }

}
