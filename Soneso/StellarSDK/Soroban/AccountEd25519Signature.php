<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban;

use Soneso\StellarSDK\Xdr\XdrSCMapEntry;
use Soneso\StellarSDK\Xdr\XdrSCVal;

/**
 * https://soroban.stellar.org/docs/how-to-guides/invoking-contracts-with-transactions#stellar-account-signatures
 */
class AccountEd25519Signature
{
    public string $publicKey; //raw public key of 32 bytes.
    public string $signatureBytes;

    /**
     * @param string $publicKey
     * @param string $signatureBytes
     */
    public function __construct(string $publicKey, string $signatureBytes)
    {
        $this->publicKey = $publicKey;
        $this->signatureBytes = $signatureBytes;
    }

    public function toXdrSCVal() : XdrSCVal {
        $pkMapEntry = new XdrSCMapEntry(XdrSCVal::forSymbol("public_key"), XdrSCVal::forBytes($this->publicKey));
        $sigMapEntry = new XdrSCMapEntry(XdrSCVal::forSymbol("signature"), XdrSCVal::forBytes($this->signatureBytes));
        return XdrSCVal::forMap([$pkMapEntry, $sigMapEntry]);
    }

    /**
     * @return string
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * @param string $publicKey
     */
    public function setPublicKey(string $publicKey): void
    {
        $this->publicKey = $publicKey;
    }

    /**
     * @return string
     */
    public function getSignatureBytes(): string
    {
        return $this->signatureBytes;
    }

    /**
     * @param string $signatureBytes
     */
    public function setSignatureBytes(string $signatureBytes): void
    {
        $this->signatureBytes = $signatureBytes;
    }
}