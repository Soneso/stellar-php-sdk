<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCMetaV0
{

    public string $key;
    public string $value;

    /**
     * @param string $key
     * @param string $value
     */
    public function __construct(string $key, string $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    public function encode(): string {
        $bytes = XdrEncoder::string($this->key);
        $bytes .= XdrEncoder::string($this->value);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCMetaV0 {
        $key = $xdr->readString();
        $value = $xdr->readString();

        return new XdrSCMetaV0($key, $value);
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }

}