<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;


class XdrHashIDPreimageSorobanAuthorization
{
    public string $networkID; // hash
    public int $nonce; // int64
    public int $signatureExpirationLedger; // uint32
    public XdrSorobanAuthorizedInvocation $invocation;

    /**
     * @param string $networkID
     * @param int $nonce
     * @param int $signatureExpirationLedger
     * @param XdrSorobanAuthorizedInvocation $invocation
     */
    public function __construct(string $networkID, int $nonce, int $signatureExpirationLedger, XdrSorobanAuthorizedInvocation $invocation)
    {
        $this->networkID = $networkID;
        $this->nonce = $nonce;
        $this->signatureExpirationLedger = $signatureExpirationLedger;
        $this->invocation = $invocation;
    }


    public function encode() : string {
        $bytes = XdrEncoder::opaqueFixed($this->networkID, 32);
        $bytes .= XdrEncoder::integer64($this->nonce);
        $bytes .= XdrEncoder::unsignedInteger32($this->signatureExpirationLedger);
        $bytes .= $this->invocation->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrHashIDPreimageSorobanAuthorization {
        $networkID = $xdr->readOpaqueFixed(32);
        $nonce = $xdr->readInteger64();
        $signatureExpirationLedger = $xdr->readUnsignedInteger32();
        $invocation = XdrSorobanAuthorizedInvocation::decode($xdr);

        return new XdrHashIDPreimageSorobanAuthorization($networkID, $nonce, $signatureExpirationLedger, $invocation);
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
     * @return int
     */
    public function getSignatureExpirationLedger(): int
    {
        return $this->signatureExpirationLedger;
    }

    /**
     * @param int $signatureExpirationLedger
     */
    public function setSignatureExpirationLedger(int $signatureExpirationLedger): void
    {
        $this->signatureExpirationLedger = $signatureExpirationLedger;
    }

    /**
     * @return XdrSorobanAuthorizedInvocation
     */
    public function getInvocation(): XdrSorobanAuthorizedInvocation
    {
        return $this->invocation;
    }

    /**
     * @param XdrSorobanAuthorizedInvocation $invocation
     */
    public function setInvocation(XdrSorobanAuthorizedInvocation $invocation): void
    {
        $this->invocation = $invocation;
    }

}