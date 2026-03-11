<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrDecoratedSignature extends XdrDecoratedSignatureBase
{
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
        return base64_encode($this->getSignature());
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
        return $this->getSignature();
    }
}
