<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSignedPayload
{

    private string $ed25519; //uint256
    private string $payload;

    /**
     * @param string $ed25519
     * @param string $payload bytes
     */
    public function __construct(string $ed25519, string $payload)
    {
        $this->ed25519 = $ed25519;
        $this->payload = $payload;
    }

    /**
     * @return string
     */
    public function getEd25519(): string
    {
        return $this->ed25519;
    }

    /**
     * @param string $ed25519
     */
    public function setEd25519(string $ed25519): void
    {
        $this->ed25519 = $ed25519;
    }

    /**
     * @return string
     */
    public function getPayload(): string
    {
        return $this->payload;
    }

    /**
     * @param string $payload
     */
    public function setPayload(string $payload): void
    {
        $this->payload = $payload;
    }


    public function encode(): string {
        $bytes = XdrEncoder::unsignedInteger256($this->ed25519);
        $bytes .= XdrEncoder::opaqueVariable($this->payload);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrSignedPayload {
        $key = $xdr->readUnsignedInteger256();
        $value = $xdr->readOpaqueVariable(64);
        return new XdrSignedPayload($key, $value);
    }
}