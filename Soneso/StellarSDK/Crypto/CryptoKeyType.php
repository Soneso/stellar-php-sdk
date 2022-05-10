<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Crypto;

class CryptoKeyType
{
    public const KEY_TYPE_ED25519 = 0;
    public const KEY_TYPE_PRE_AUTH_TX = 1;
    public const KEY_TYPE_HASH_X = 2;
    public const KEY_TYPE_ED25519_SIGNED_PAYLOAD = 3;
    public const KEY_TYPE_MUXED_ED25519 = 256;

    private int $value;

    public function __construct(int $value) {
        $this->value = $value;
    }

    public static function fromXdr(string $xdr) : CryptoKeyType{
        $unpacked = unpack('Cversion', substr($xdr, 0, 1));
        return new CryptoKeyType($unpacked['version']);
    }

    public function toXdr(): string {
        return pack('C', $this->value);
    }
}