<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Crypto;

/**
 * Represents the type of cryptographic key used in Stellar
 *
 * Stellar supports multiple key types for different purposes:
 * - ED25519: Standard Ed25519 public key for signing transactions
 * - PRE_AUTH_TX: Pre-authorized transaction hash
 * - HASH_X: Hash of a preimage used for hash-locked transactions
 * - ED25519_SIGNED_PAYLOAD: Ed25519 key with additional payload signature
 * - MUXED_ED25519: Multiplexed Ed25519 account (virtual accounts)
 *
 * @package Soneso\StellarSDK\Crypto
 * @see https://developers.stellar.org Stellar developer docs Documentation on signature types
 */
class CryptoKeyType
{
    public const KEY_TYPE_ED25519 = 0;
    public const KEY_TYPE_PRE_AUTH_TX = 1;
    public const KEY_TYPE_HASH_X = 2;
    public const KEY_TYPE_ED25519_SIGNED_PAYLOAD = 3;
    public const KEY_TYPE_MUXED_ED25519 = 256;

    private int $value;

    /**
     * CryptoKeyType constructor
     *
     * @param int $value The numeric value of the key type
     */
    public function __construct(int $value) {
        $this->value = $value;
    }

    /**
     * Creates a CryptoKeyType from XDR encoded data
     *
     * @param string $xdr The XDR encoded key type
     * @return CryptoKeyType The decoded key type
     */
    public static function fromXdr(string $xdr) : CryptoKeyType{
        $unpacked = unpack('Cversion', substr($xdr, 0, 1));
        return new CryptoKeyType($unpacked['version']);
    }

    /**
     * Encodes the key type to XDR format
     *
     * @return string The XDR encoded key type
     */
    public function toXdr(): string {
        return pack('C', $this->value);
    }
}