<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSignerKey
{
    private XdrSignerKeyType $type;
    private ?string $ed25519 = null;
    private ?string $preAuthTx = null;
    private ?string $hashX = null;
    private ?XdrSignedPayload $signedPayload = null;

    /**
     * @return XdrSignerKeyType
     */
    public function getType(): XdrSignerKeyType
    {
        return $this->type;
    }

    /**
     * @param XdrSignerKeyType $type
     */
    public function setType(XdrSignerKeyType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string|null
     */
    public function getEd25519(): ?string
    {
        return $this->ed25519;
    }

    /**
     * @param string|null $ed25519
     */
    public function setEd25519(?string $ed25519): void
    {
        $this->ed25519 = $ed25519;
    }

    /**
     * @return string|null
     */
    public function getPreAuthTx(): ?string
    {
        return $this->preAuthTx;
    }

    /**
     * @param string|null $preAuthTx
     */
    public function setPreAuthTx(?string $preAuthTx): void
    {
        $this->preAuthTx = $preAuthTx;
    }

    /**
     * @return string|null
     */
    public function getHashX(): ?string
    {
        return $this->hashX;
    }

    /**
     * @param string|null $hashX
     */
    public function setHashX(?string $hashX): void
    {
        $this->hashX = $hashX;
    }

    /**
     * @return XdrSignedPayload|null
     */
    public function getSignedPayload(): ?XdrSignedPayload
    {
        return $this->signedPayload;
    }

    /**
     * @param XdrSignedPayload|null $signedPayload
     */
    public function setSignedPayload(?XdrSignedPayload $signedPayload): void
    {
        $this->signedPayload = $signedPayload;
    }

    public function encode(): string {
        $bytes = $this->type->encode();
        if ($this->ed25519) {
            $bytes .= XdrEncoder::unsignedInteger256($this->ed25519);
        } else if ($this->preAuthTx) {
            $bytes .= XdrEncoder::unsignedInteger256($this->preAuthTx);
        } else if ($this->hashX) {
            $bytes .= XdrEncoder::unsignedInteger256($this->hashX);
        } else if ($this->signedPayload) {
            $bytes .= $this->signedPayload->encode();
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrSignerKey {

        $type = $xdr->readUnsignedInteger32();
        $result = new XdrSignerKey();
        $result->type = new XdrSignerKeyType($type);

        if ($type == XdrSignerKeyType::ED25519) {
            $value = $xdr->readUnsignedInteger256();
            $result->ed25519 = $value;
        } else if ($type == XdrSignerKeyType::PRE_AUTH_TX) {
            $value = $xdr->readUnsignedInteger256();
            $result->preAuthTx = $value;
        } else if ($type == XdrSignerKeyType::HASH_X) {
            $value = $xdr->readUnsignedInteger256();
            $result->hashX = $value;
        } else if ($type == XdrSignerKeyType::ED25519_SIGNED_PAYLOAD) {
            $value = XdrSignedPayload::decode($xdr);
            $result->signedPayload = $value;
        }
        return $result;
    }
}