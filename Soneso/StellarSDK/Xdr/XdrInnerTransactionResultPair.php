<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;


class XdrInnerTransactionResultPair
{
    public string $transactionHash;
    public XdrInnerTransactionResult $result;

    /**
     * @param string $transactionHash
     * @param XdrInnerTransactionResult $result
     */
    public function __construct(string $transactionHash, XdrInnerTransactionResult $result)
    {
        $this->transactionHash = $transactionHash;
        $this->result = $result;
    }

    public function encode(): string {
        $transactionHashBytes = pack("H*", $this->transactionHash);
        if (strlen($transactionHashBytes) > 32) {
            $$transactionHashBytes = substr($$transactionHashBytes, -32);
        }
        $bytes = XdrEncoder::opaqueFixed($transactionHashBytes, 32);
        $bytes .= $this->result->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrInnerTransactionResultPair
    {
        $transactionHash = bin2hex($xdr->readOpaqueFixed(32));
        $result = XdrInnerTransactionResult::decode($xdr);
        return new XdrInnerTransactionResultPair($transactionHash, $result);
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
     * @return string
     */
    public function getTransactionHash(): string
    {
        return $this->transactionHash;
    }

    /**
     * @param string $transactionHash
     */
    public function setTransactionHash(string $transactionHash): void
    {
        $this->transactionHash = $transactionHash;
    }

    /**
     * @return XdrInnerTransactionResult
     */
    public function getResult(): XdrInnerTransactionResult
    {
        return $this->result;
    }

    /**
     * @param XdrInnerTransactionResult $result
     */
    public function setResult(XdrInnerTransactionResult $result): void
    {
        $this->result = $result;
    }
}