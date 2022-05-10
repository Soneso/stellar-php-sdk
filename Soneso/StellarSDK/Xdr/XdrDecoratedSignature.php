<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrDecoratedSignature
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
     * @return XdrDecoratedSignature
     */
    public static function decode(XdrBuffer $xdr) : XdrDecoratedSignature
    {
        $hint = $xdr->readOpaqueFixed(4);
        $signature = $xdr->readOpaqueVariable();

        return new XdrDecoratedSignature($hint, $signature);
    }

    /**
     * @return string
     */
    public function toBase64(): string
    {
        return base64_encode($this->encode());
    }

    /**
     * @return string
     */
    public function getWithoutHintBase64(): string
    {
        return base64_encode($this->signature);
    }

    /**
     * Returns the raw 64 bytes representing the signature
     *
     * This does not include the hint
     *
     * @return string
     */
    public function getRawSignature(): string
    {
        return $this->signature;
    }
}