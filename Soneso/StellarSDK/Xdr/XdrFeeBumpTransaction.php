<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrFeeBumpTransaction
{
    private XdrMuxedAccount $feeSource;
    private int $fee; //int64
    private XdrFeeBumpTransactionInnerTx $innerTx;
    private XdrFeeBumpTransactionExt $ext;

    public function __construct(XdrMuxedAccount $feeSource, int $fee, XdrFeeBumpTransactionInnerTx $innerTx, ?XdrFeeBumpTransactionExt $ext = null)
    {
        $this->feeSource = $feeSource;
        $this->fee = $fee;
        $this->innerTx = $innerTx;
        if ($ext == null) {
            $this->ext = new XdrFeeBumpTransactionExt(0);
        } else {
            $this->ext = $ext;
        }
    }

    /**
     * @return XdrMuxedAccount
     */
    public function getFeeSource(): XdrMuxedAccount
    {
        return $this->feeSource;
    }

    /**
     * @return int
     */
    public function getFee(): int
    {
        return $this->fee;
    }

    /**
     * @return XdrFeeBumpTransactionInnerTx
     */
    public function getInnerTx(): XdrFeeBumpTransactionInnerTx
    {
        return $this->innerTx;
    }

    /**
     * @return XdrFeeBumpTransactionExt
     */
    public function getExt(): XdrFeeBumpTransactionExt
    {
        return $this->ext;
    }

    public function encode(): string {
        $bytes = $this->feeSource->encode();
        $bytes .= XdrEncoder::integer64($this->fee);
        $bytes .= $this->innerTx->encode();
        $bytes .= $this->ext->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrFeeBumpTransaction {
        $feeSource = XdrMuxedAccount::decode($xdr);
        $fee = $xdr->readInteger64();
        $innerTx = XdrFeeBumpTransactionInnerTx::decode($xdr);
        $ext = XdrFeeBumpTransactionExt::decode($xdr);
        return new XdrFeeBumpTransaction($feeSource, $fee, $innerTx, $ext);
    }
}