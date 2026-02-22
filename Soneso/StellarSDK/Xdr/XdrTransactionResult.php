<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use InvalidArgumentException;
use phpseclib3\Math\BigInteger;

class XdrTransactionResult
{
    public BigInteger $feeCharged;
    public XdrTransactionResultResult $result;
    public XdrTransactionResultExt $ext;

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

    public function encode(): string {
        $bytes = XdrEncoder::bigInteger64($this->feeCharged);
        $bytes .= $this->result->encode();
        $bytes .= $this->ext->encode();
        return $bytes;
    }

    /**
     * @param XdrBuffer $xdr
     * @return XdrTransactionResult
     */
    public static function decode(XdrBuffer $xdr) : XdrTransactionResult
    {
        $result = new XdrTransactionResult();
        $result->feeCharged = new BigInteger($xdr->readInteger64());
        $result->result = XdrTransactionResultResult::decode($xdr);
        $result->ext = XdrTransactionResultExt::decode($xdr);
        return $result;
    }

    public static function fromBase64Xdr(String $base64Xdr) : XdrTransactionResult {
        $xdr = base64_decode($base64Xdr, true);
        if ($xdr === false) {
            throw new InvalidArgumentException('Invalid base64-encoded XDR');
        }
        $xdrBuffer = new XdrBuffer($xdr);
        return XdrTransactionResult::decode($xdrBuffer);
    }

    public function toBase64Xdr() : String {
        return base64_encode($this->encode());
    }
}