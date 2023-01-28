<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use phpseclib3\Math\BigInteger;

class XdrInnerTransactionResult
{
    public BigInteger $feeCharged;
    public XdrTransactionResultResult $result;
    public XdrTransactionResultExt $ext;

    public function encode(): string {
        $bytes = XdrEncoder::bigInteger64($this->feeCharged);
        $bytes .= $this->result->encode();
        $bytes .= $this->ext->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrInnerTransactionResult
    {
        $result = new XdrInnerTransactionResult();
        $result->feeCharged = new BigInteger($xdr->readInteger64());
        $result->result = XdrTransactionResultResult::decode($xdr);
        $result->ext = XdrTransactionResultExt::decode($xdr);
        return $result;
    }

    public static function fromBase64Xdr(String $base64Xdr) : XdrInnerTransactionResult {
        $xdr = base64_decode($base64Xdr);
        $xdrBuffer = new XdrBuffer($xdr);
        return XdrInnerTransactionResult::decode($xdrBuffer);
    }

    public function toBase64Xdr() : String {
        return base64_encode($this->encode());
    }

    /**
     * @return BigInteger
     */
    public function getFeeCharged(): BigInteger
    {
        return $this->feeCharged;
    }

    /**
     * @param BigInteger $feeCharged
     */
    public function setFeeCharged(BigInteger $feeCharged): void
    {
        $this->feeCharged = $feeCharged;
    }

    /**
     * @return XdrTransactionResultResult
     */
    public function getResult(): XdrTransactionResultResult
    {
        return $this->result;
    }

    /**
     * @param XdrTransactionResultResult $result
     */
    public function setResult(XdrTransactionResultResult $result): void
    {
        $this->result = $result;
    }

    /**
     * @return XdrTransactionResultExt
     */
    public function getExt(): XdrTransactionResultExt
    {
        return $this->ext;
    }

    /**
     * @param XdrTransactionResultExt $ext
     */
    public function setExt(XdrTransactionResultExt $ext): void
    {
        $this->ext = $ext;
    }
}