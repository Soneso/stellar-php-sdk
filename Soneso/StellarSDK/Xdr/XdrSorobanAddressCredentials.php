<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSorobanAddressCredentials
{
    public XdrSCAddress $address;
    public int $nonce; // int64
    public int $signatureExpirationLedger; // uint32
    public array $signatureArgs; // [XdrSCVal]

    /**
     * @param XdrSCAddress $address
     * @param int $nonce
     * @param int $signatureExpirationLedger
     * @param array $signatureArgs
     */
    public function __construct(XdrSCAddress $address, int $nonce, int $signatureExpirationLedger, array $signatureArgs)
    {
        $this->address = $address;
        $this->nonce = $nonce;
        $this->signatureExpirationLedger = $signatureExpirationLedger;
        $this->signatureArgs = $signatureArgs;
    }


    public function encode(): string {
        $bytes = $this->address->encode();
        $bytes .= XdrEncoder::integer64($this->nonce);
        $bytes .= XdrEncoder::unsignedInteger32($this->signatureExpirationLedger);
        $bytes .= XdrEncoder::integer32(count($this->signatureArgs));
        foreach($this->signatureArgs as $val) {
            if ($val instanceof XdrSCVal) {
                $bytes .= $val->encode();
            }
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrSorobanAddressCredentials {
        $address = XdrSCAddress::decode($xdr);
        $nonce = $xdr->readInteger64();
        $signatureExpirationLedger = $xdr->readUnsignedInteger32();
        $valCount = $xdr->readInteger32();
        $args = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($args, XdrSCVal::decode($xdr));
        }
        return new XdrSorobanAddressCredentials($address, $nonce, $signatureExpirationLedger, $args);
    }

    /**
     * @return XdrSCAddress
     */
    public function getAddress(): XdrSCAddress
    {
        return $this->address;
    }

    /**
     * @param XdrSCAddress $address
     */
    public function setAddress(XdrSCAddress $address): void
    {
        $this->address = $address;
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