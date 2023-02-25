<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;


class XdrHashIDPreimageContractAuth
{
    public string $networkID; // hash
    public int $nonce; // uint64
    public XdrAuthorizedInvocation $invocation;

    /**
     * @param string $networkID
     * @param int $nonce
     * @param XdrAuthorizedInvocation $invocation
     */
    public function __construct(string $networkID, int $nonce, XdrAuthorizedInvocation $invocation)
    {
        $this->networkID = $networkID;
        $this->nonce = $nonce;
        $this->invocation = $invocation;
    }


    public function encode() : string {
        $bytes = XdrEncoder::opaqueFixed($this->networkID, 32);
        $bytes .= XdrEncoder::unsignedInteger64($this->nonce);
        $bytes .= $this->invocation->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrHashIDPreimageContractAuth {
        $networkID = $xdr->readOpaqueFixed(32);
        $nonce = $xdr->readUnsignedInteger64();
        $invocation = XdrAuthorizedInvocation::decode($xdr);

        return new XdrHashIDPreimageContractAuth($networkID, $nonce, $invocation);
    }

    /**
     * @return string
     */
    public function getNetworkID(): string
    {
        return $this->networkID;
    }

    /**
     * @param string $networkID
     */
    public function setNetworkID(string $networkID): void
    {
        $this->networkID = $networkID;
    }

    /**
     * @return int
     */
    public function getNonce(): int
    {
        return $this->nonce;
    }

    /**
     * @param int $nonce
     */
    public function setNonce(int $nonce): void
    {
        $this->nonce = $nonce;
    }

    /**
     * @return XdrAuthorizedInvocation
     */
    public function getInvocation(): XdrAuthorizedInvocation
    {
        return $this->invocation;
    }

    /**
     * @param XdrAuthorizedInvocation $invocation
     */
    public function setInvocation(XdrAuthorizedInvocation $invocation): void
    {
        $this->invocation = $invocation;
    }

}