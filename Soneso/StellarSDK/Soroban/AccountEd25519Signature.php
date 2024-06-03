<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban;

use Soneso\StellarSDK\Xdr\XdrSCMapEntry;
use Soneso\StellarSDK\Xdr\XdrSCVal;

/**
 * This class is used during the signing of soroban authorization entries.
 */
class AccountEd25519Signature
{
    /**
     * @var string $publicKey raw public key of 32 bytes.
     */
    public string $publicKey;

    /**
     * @var string $signatureBytes the raw signature bytes.
     */
    public string $signatureBytes;

    /**
     * @param string $publicKey raw public key of 32 bytes.
     * @param string $signatureBytes the raw signature bytes.
     */
    public function __construct(string $publicKey, string $signatureBytes)
    {
        $this->publicKey = $publicKey;
        $this->signatureBytes = $signatureBytes;
    }

    /**
     * Returns a scval map containing the public key and signature bytes.
     *
     * @return XdrSCVal the scval map containing the public key and signature bytes.
     */
    public function toXdrSCVal() : XdrSCVal {
        $pkMapEntry = new XdrSCMapEntry(XdrSCVal::forSymbol("public_key"), XdrSCVal::forBytes($this->publicKey));
        $sigMapEntry = new XdrSCMapEntry(XdrSCVal::forSymbol("signature"), XdrSCVal::forBytes($this->signatureBytes));
        return XdrSCVal::forMap([$pkMapEntry, $sigMapEntry]);
    }

    /**
     * @return string raw public key of 32 bytes.
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * @param string $publicKey raw public key of 32 bytes.
     */
    public function setPublicKey(string $publicKey): void
    {
        $this->publicKey = $publicKey;
    }

    /**
     * @return string the raw signature bytes.
     */
    public function getSignatureBytes(): string
    {
        return $this->signatureBytes;
    }

    /**
     * @param string $signatureBytes the raw signature bytes.
     */
    public function setSignatureBytes(string $signatureBytes): void
    {
        $this->signatureBytes = $signatureBytes;
    }
}