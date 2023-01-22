<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrFromEd25519PublicKey
{

    public string $key;
    public XdrSignature $signature;
    public string $salt;

    /**
     * @param string $key
     * @param XdrSignature $signature
     * @param string $salt
     */
    public function __construct(string $key, XdrSignature $signature, string $salt)
    {
        $this->key = $key;
        $this->signature = $signature;
        $this->salt = $salt;
    }


    public function encode(): string {
        $bytes = XdrEncoder::unsignedInteger256($this->key);
        $bytes .= $this->signature->encode();
        $bytes .= XdrEncoder::unsignedInteger256($this->salt);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrFromEd25519PublicKey {
        return new XdrFromEd25519PublicKey($xdr->readUnsignedInteger256(), XdrSignature::decode($xdr), $xdr->readUnsignedInteger256());
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    /**
     * @return XdrSignature
     */
    public function getSignature(): XdrSignature
    {
        return $this->signature;
    }

    /**
     * @param XdrSignature $signature
     */
    public function setSignature(XdrSignature $signature): void
    {
        $this->signature = $signature;
    }

    /**
     * @return string
     */
    public function getSalt(): string
    {
        return $this->salt;
    }

    /**
     * @param string $salt
     */
    public function setSalt(string $salt): void
    {
        $this->salt = $salt;
    }

}