<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrContractAuth
{
    public ?XdrAddressWithNonce $addressWithNonce = null;
    public XdrAuthorizedInvocation $rootInvocation;
    public array $signatureArgs; // vec:[XdrSCVal]

    /**
     * @param XdrAddressWithNonce|null $addressWithNonce
     * @param XdrAuthorizedInvocation $rootInvocation
     * @param array $signatureArgs
     */
    public function __construct(?XdrAddressWithNonce $addressWithNonce, XdrAuthorizedInvocation $rootInvocation, array $signatureArgs)
    {
        $this->addressWithNonce = $addressWithNonce;
        $this->rootInvocation = $rootInvocation;
        $this->signatureArgs = $signatureArgs;
    }

    public function encode(): string {
        if ($this->addressWithNonce != null) {
            $bytes = XdrEncoder::integer32(1);
            $bytes .= $this->addressWithNonce->encode();
        } else {
            $bytes = XdrEncoder::integer32(0);
        }

        $bytes .= $this->rootInvocation->encode();
        $bytes .= XdrEncoder::integer32(count($this->signatureArgs));
        foreach($this->signatureArgs as $val) {
            if ($val instanceof XdrSCVal) {
                $bytes .= $val->encode();
            }
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrContractAuth {
        $aWithNonce = null;
        if ($xdr->readInteger32() == 1) {
            $aWithNonce = XdrAddressWithNonce::decode($xdr);
        }
        $rInvocation = XdrAuthorizedInvocation::decode($xdr);
        $valCount = $xdr->readInteger32();
        $args = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($args, XdrSCVal::decode($xdr));
        }

        return new XdrContractAuth($aWithNonce, $rInvocation, $args);
    }

    /**
     * @return XdrAddressWithNonce|null
     */
    public function getAddressWithNonce(): ?XdrAddressWithNonce
    {
        return $this->addressWithNonce;
    }

    /**
     * @param XdrAddressWithNonce|null $addressWithNonce
     */
    public function setAddressWithNonce(?XdrAddressWithNonce $addressWithNonce): void
    {
        $this->addressWithNonce = $addressWithNonce;
    }

    /**
     * @return XdrAuthorizedInvocation
     */
    public function getRootInvocation(): XdrAuthorizedInvocation
    {
        return $this->rootInvocation;
    }

    /**
     * @param XdrAuthorizedInvocation $rootInvocation
     */
    public function setRootInvocation(XdrAuthorizedInvocation $rootInvocation): void
    {
        $this->rootInvocation = $rootInvocation;
    }

    /**
     * @return array
     */
    public function getSignatureArgs(): array
    {
        return $this->signatureArgs;
    }

    /**
     * @param array $signatureArgs
     */
    public function setSignatureArgs(array $signatureArgs): void
    {
        $this->signatureArgs = $signatureArgs;
    }

}