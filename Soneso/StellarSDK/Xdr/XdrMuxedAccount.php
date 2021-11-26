<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use Soneso\StellarSDK\Crypto\CryptoKeyType;

class XdrMuxedAccount
{
    private int $discriminant;
    private ?string $ed25519 = null;
    private ?XdrMuxedAccountMed25519 $med25519 = null;

    /**
     * Constructor. Provide $ed25519 or $med25519.
     * @param string|null $ed25519
     * @param XdrMuxedAccountMed25519|null $med25519
     */
    public function __construct(?string $ed25519 = null, ?XdrMuxedAccountMed25519 $med25519 = null) {

        if (!$ed25519 && !$med25519) {
            throw new \InvalidArgumentException("ed25519 or med25519 must be provided");
        }
        if ($ed25519 && $med25519) {
            throw new \InvalidArgumentException("can not accept both ed25519 and med25519");
        }

        if ($ed25519) {
            $this->discriminant = CryptoKeyType::KEY_TYPE_ED25519;
            $this->ed25519 = $ed25519;
        }

        if ($med25519) {
            $this->discriminant = CryptoKeyType::KEY_TYPE_MUXED_ED25519;
            $this->med25519 = $med25519;
        }

    }

    public function encode(): string {
        $bytes = XdrEncoder::unsignedInteger32($this->discriminant);
        if ($this->ed25519) {
            $bytes .= XdrEncoder::unsignedInteger256($this->ed25519);
        } else if ($this->med25519) {
            $bytes .= $this->med25519->encode();
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrMuxedAccount {
        $discriminant = $xdr->readUnsignedInteger32();
        if ($discriminant == CryptoKeyType::KEY_TYPE_ED25519) {
            $ed25519 = $xdr->readUnsignedInteger256();
            return new XdrMuxedAccount($ed25519, null);
        }
        else if ($discriminant == CryptoKeyType::KEY_TYPE_MUXED_ED25519) {
            $med25519 = XdrMuxedAccountMed25519::decode($xdr);
            return new XdrMuxedAccount(null, $med25519);
        } else {
            throw new \InvalidArgumentException("wrong discriminant " . $discriminant . "in xdrBuffer");
        }
    }

    /**
     * @return int
     */
    public function getDiscriminant(): int
    {
        return $this->discriminant;
    }

    /**
     * @return string|null
     */
    public function getEd25519(): ?string
    {
        return $this->ed25519;
    }

    /**
     * @return XdrMuxedAccountMed25519|null
     */
    public function getMed25519(): ?XdrMuxedAccountMed25519
    {
        return $this->med25519;
    }
}