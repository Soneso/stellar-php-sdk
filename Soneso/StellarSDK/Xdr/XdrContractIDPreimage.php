<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrContractIDPreimage extends XdrContractIDPreimageBase
{
    // Backward-compatible flattened fields (base uses nested fromAddress/fromAsset)
    public ?XdrSCAddress $address = null;
    public ?string $salt = null; // uint256
    public ?XdrAsset $asset = null;

    public function encode(): string {
        // Sync flattened fields to base nested struct before encoding
        if ($this->address !== null && $this->salt !== null) {
            $this->fromAddress = new XdrContractIDPreimageFromAddress($this->address, $this->salt);
        }
        if ($this->asset !== null) {
            $this->fromAsset = $this->asset;
        }
        return parent::encode();
    }

    public static function decode(XdrBuffer $xdr): static {
        $result = parent::decode($xdr);
        // Sync base nested struct to flattened fields after decoding
        if ($result->fromAddress !== null) {
            $result->address = $result->fromAddress->address;
            $result->salt = $result->fromAddress->salt;
        }
        if ($result->fromAsset !== null) {
            $result->asset = $result->fromAsset;
        }
        return $result;
    }

    // Backward-compatible getters/setters
    public function getAddress(): ?XdrSCAddress { return $this->address; }
    public function setAddress(?XdrSCAddress $address): void { $this->address = $address; }
    public function getSalt(): ?string { return $this->salt; }
    public function setSalt(?string $salt): void { $this->salt = $salt; }
    public function getAsset(): ?XdrAsset { return $this->asset; }
    public function setAsset(?XdrAsset $asset): void { $this->asset = $asset; }

    public static function forAddress(XdrSCAddress $address, String $saltHex): XdrContractIDPreimage {
        $result = new XdrContractIDPreimage(XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ADDRESS());
        $result->address = $address;
        $result->salt = hex2bin($saltHex);
        return $result;
    }

    public static function forAsset(XdrAsset $asset): XdrContractIDPreimage {
        $result = new XdrContractIDPreimage(XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ASSET());
        $result->asset = $asset;
        return $result;
    }
}
