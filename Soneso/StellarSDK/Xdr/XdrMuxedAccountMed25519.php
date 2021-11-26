<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrMuxedAccountMed25519
{
    private int $id; //uint64
    private string $ed25519; //uint256

    public function __construct(int $id, string $ed25519) {
        $this->id = $id;
        $this->ed25519 = $ed25519;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEd25519(): string
    {
        return $this->ed25519;
    }

    public function encode(): string {
        $bytes = '';
        $bytes .= XdrEncoder::unsignedInteger64($this->id);
        $bytes .= XdrEncoder::unsignedInteger256($this->ed25519);
        return $bytes;
    }

    public function encodeInverted(): string {
        $bytes = '';
        $bytes .= XdrEncoder::unsignedInteger256($this->ed25519);
        $bytes .= XdrEncoder::unsignedInteger64($this->id);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrMuxedAccountMed25519 {
        $id = $xdr->readUnsignedInteger64();
        $ed25519 = $xdr->readUnsignedInteger256();
        return new XdrMuxedAccountMed25519($id, $ed25519);
    }

    public static function decodeInverted(XdrBuffer $xdr) : XdrMuxedAccountMed25519 {
        $ed25519 = $xdr->readUnsignedInteger256();
        $id = $xdr->readUnsignedInteger64();
        return new XdrMuxedAccountMed25519($id, $ed25519);
    }
}