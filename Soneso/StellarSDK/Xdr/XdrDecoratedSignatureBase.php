<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrDecoratedSignatureBase
{
    /**
     * @var string opaque<4>
     */
    private string $hint;

    /**
     * @var string opaque<64>
     */
    private string $signature;

    public function __construct($hint, $signature)
    {
        $this->hint = $hint;
        $this->signature = $signature;
    }

    /**
     * @return string
     */
    public function getHint(): string
    {
        return $this->hint;
    }

    /**
     * @return string
     */
    public function getSignature(): string
    {
        return $this->signature;
    }

    /**
     * @param string $hint
     */
    public function setHint(string $hint): void
    {
        $this->hint = $hint;
    }

    /**
     * @param string $signature
     */
    public function setSignature(string $signature): void
    {
        $this->signature = $signature;
    }

    /**
     * @inheritDoc
     */
    public function encode(): string
    {

        $bytes = XdrEncoder::opaqueFixed($this->hint, 4);
        $bytes .= XdrEncoder::opaqueVariable($this->signature);

        return $bytes;
    }


    /**
     * @param XdrBuffer $xdr
     * @return static
     */
    public static function decode(XdrBuffer $xdr) : static
    {
        $hint = $xdr->readOpaqueFixed(4);
        $signature = $xdr->readOpaqueVariable();

        return new static($hint, $signature);
    }
}
