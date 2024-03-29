<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrSorobanTransactionData
{
    public XdrExtensionPoint $ext;
    public XdrSorobanResources $resources;
    public int $resourceFee; // Portion of transaction `fee` allocated to refundable fees.

    /**
     * @param XdrExtensionPoint $ext
     * @param XdrSorobanResources $resources
     * @param int $resourceFee
     */
    public function __construct(XdrExtensionPoint $ext, XdrSorobanResources $resources, int $resourceFee)
    {
        $this->ext = $ext;
        $this->resources = $resources;
        $this->resourceFee = $resourceFee;
    }


    public function encode(): string {
        $bytes = $this->ext->encode();
        $bytes .= $this->resources->encode();
        $bytes .= XdrEncoder::integer64($this->resourceFee);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrSorobanTransactionData {
        $ext = XdrExtensionPoint::decode($xdr);
        $resources = XdrSorobanResources::decode($xdr);
        $resourceFee = $xdr->readInteger64();

        return new XdrSorobanTransactionData($ext, $resources, $resourceFee);
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
    public function getResourceFee(): int
    {
        return $this->resourceFee;
    }

    /**
     * @param int $resourceFee
     */
    public function setResourceFee(int $resourceFee): void
    {
        $this->resourceFee = $resourceFee;
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