<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrContractID
{

    public XdrContractIDType $type;
    public ?string $salt = null; // uint256
    public ?XdrFromEd25519PublicKey $fromEd25519PublicKey = null;
    public ?XdrAsset $asset = null;

    /**
     * @param XdrContractIDType $type
     */
    public function __construct(XdrContractIDType $type)
    {
        $this->type = $type;
    }


    public function encode(): string {
        $bytes = $this->type->encode();

        switch ($this->type->value) {
            case XdrContractIDType::CONTRACT_ID_FROM_SOURCE_ACCOUNT:
                $bytes .= XdrEncoder::unsignedInteger256($this->salt);
                break;
            case XdrContractIDType::CONTRACT_ID_FROM_ED25519_PUBLIC_KEY:
                $bytes .= $this->fromEd25519PublicKey->encode();
                break;
            case XdrContractIDType::CONTRACT_ID_FROM_ASSET:
                $bytes .= $this->asset->encode();
                break;

        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrContractID {
        $result = new XdrContractID(XdrContractIDType::decode($xdr));
        switch ($result->type->value) {
            case XdrContractIDType::CONTRACT_ID_FROM_SOURCE_ACCOUNT:
                $result->salt = $xdr->readUnsignedInteger256();
                break;
            case XdrContractIDType::CONTRACT_ID_FROM_ED25519_PUBLIC_KEY:
                $result->fromEd25519PublicKey = XdrFromEd25519PublicKey::decode($xdr);
                break;
            case XdrContractIDType::CONTRACT_ID_FROM_ASSET:
                $result->asset = XdrAsset::decode($xdr);
                break;
        }
        return $result;
    }

    public static function fromBase64Xdr(String $base64Xdr) : XdrContractID {
        $xdr = base64_decode($base64Xdr);
        $xdrBuffer = new XdrBuffer($xdr);
        return XdrContractID::decode($xdrBuffer);
    }

    public function toBase64Xdr() : String {
        return base64_encode($this->encode());
    }

    /**
     * @return XdrContractIDType
     */
    public function getType(): XdrContractIDType
    {
        return $this->type;
    }

    /**
     * @param XdrContractIDType $type
     */
    public function setType(XdrContractIDType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string|null
     */
    public function getSalt(): ?string
    {
        return $this->salt;
    }

    /**
     * @param string|null $salt
     */
    public function setSalt(?string $salt): void
    {
        $this->salt = $salt;
    }

    /**
     * @return XdrFromEd25519PublicKey|null
     */
    public function getFromEd25519PublicKey(): ?XdrFromEd25519PublicKey
    {
        return $this->fromEd25519PublicKey;
    }

    /**
     * @param XdrFromEd25519PublicKey|null $fromEd25519PublicKey
     */
    public function setFromEd25519PublicKey(?XdrFromEd25519PublicKey $fromEd25519PublicKey): void
    {
        $this->fromEd25519PublicKey = $fromEd25519PublicKey;
    }

    /**
     * @return XdrAsset|null
     */
    public function getAsset(): ?XdrAsset
    {
        return $this->asset;
    }

    /**
     * @param XdrAsset|null $asset
     */
    public function setAsset(?XdrAsset $asset): void
    {
        $this->asset = $asset;
    }

}