<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrContractIDPreimage
{

    public XdrContractIDPreimageType $type;
    public ?XdrSCAddress $address= null;
    public ?string $salt = null; // uint256
    public ?XdrAsset $asset = null;

    /**
     * @param XdrContractIDPreimageType $type
     */
    public function __construct(XdrContractIDPreimageType $type)
    {
        $this->type = $type;
    }


    public function encode(): string {
        $bytes = $this->type->encode();

        switch ($this->type->value) {
            case XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ADDRESS:
                $bytes .= $this->address->encode();
                $bytes .= XdrEncoder::unsignedInteger256($this->salt);
                break;
            case XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ASSET:
                $bytes .= $this->asset->encode();
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrContractIDPreimage {
        $result = new XdrContractIDPreimage(XdrContractIDPreimageType::decode($xdr));
        switch ($result->type->value) {
            case XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ADDRESS:
                $result->address = XdrSCAddress::decode($xdr);
                $result->salt = $xdr->readUnsignedInteger256();
                break;
            case XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ASSET:
                $result->asset = XdrAsset::decode($xdr);
                break;
        }
        return $result;
    }

    public static function fromBase64Xdr(String $base64Xdr) : XdrContractIDPreimage {
        $xdr = base64_decode($base64Xdr);
        $xdrBuffer = new XdrBuffer($xdr);
        return XdrContractIDPreimage::decode($xdrBuffer);
    }

    public function toBase64Xdr() : String {
        return base64_encode($this->encode());
    }

    /**
     * @return XdrContractIDPreimageType
     */
    public function getType(): XdrContractIDPreimageType
    {
        return $this->type;
    }

    /**
     * @param XdrContractIDPreimageType $type
     */
    public function setType(XdrContractIDPreimageType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return XdrSCAddress|null
     */
    public function getAddress(): ?XdrSCAddress
    {
        return $this->address;
    }

    /**
     * @param XdrSCAddress|null $address
     */
    public function setAddress(?XdrSCAddress $address): void
    {
        $this->address = $address;
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