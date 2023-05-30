<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrSorobanTransactionData
{
    public XdrSorobanResources $resources;
    public int $refundableFee; // Portion of transaction `fee` allocated to refundable fees.
    public XdrExtensionPoint $ext;

    /**
     * @param XdrSorobanResources $resources
     * @param int $refundableFee
     * @param XdrExtensionPoint $ext
     */
    public function __construct(XdrSorobanResources $resources, int $refundableFee, XdrExtensionPoint $ext)
    {
        $this->resources = $resources;
        $this->refundableFee = $refundableFee;
        $this->ext = $ext;
    }

    public function encode(): string {
        $bytes = $this->resources->encode();
        $bytes .= XdrEncoder::integer64($this->refundableFee);
        $bytes .= $this->ext->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrSorobanTransactionData {
        $resources = XdrSorobanResources::decode($xdr);
        $refundableFee = $xdr->readInteger64();
        $ext = XdrExtensionPoint::decode($xdr);

        return new XdrSorobanTransactionData($resources, $refundableFee, $ext);
    }

    public static function fromBase64Xdr(String $base64Xdr) : XdrSorobanTransactionData {
        $xdr = base64_decode($base64Xdr);
        $xdrBuffer = new XdrBuffer($xdr);
        return XdrSorobanTransactionData::decode($xdrBuffer);
    }

    public function toBase64Xdr() : String {
        return base64_encode($this->encode());
    }

    /**
     * @return XdrSorobanResources
     */
    public function getResources(): XdrSorobanResources
    {
        return $this->resources;
    }

    /**
     * @param XdrSorobanResources $resources
     */
    public function setResources(XdrSorobanResources $resources): void
    {
        $this->resources = $resources;
    }

    /**
     * @return int
     */
    public function getRefundableFee(): int
    {
        return $this->refundableFee;
    }

    /**
     * @param int $refundableFee
     */
    public function setRefundableFee(int $refundableFee): void
    {
        $this->refundableFee = $refundableFee;
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