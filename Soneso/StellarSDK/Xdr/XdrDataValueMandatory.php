<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrDataValueMandatory
{
    public string $value;

    public function __construct(string $value) {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    public function encode() : string {
        return XdrEncoder::opaqueVariable($this->value);
    }

    public static function decode(XdrBuffer $xdr) :  XdrDataValueMandatory {
        return new XdrDataValueMandatory($xdr->readOpaqueVariable());
    }

    /**
     * Serialize as a hex string for TxRep (SEP-0011).
     *
     * @param string               $prefix
     * @param array<string,string> $lines
     */
    public function toTxRep(string $prefix, array &$lines): void {
        $lines[$prefix] = \Soneso\StellarSDK\Xdr\TxRepHelper::bytesToHex($this->value);
    }

    /**
     * Deserialize from a hex string in TxRep (SEP-0011).
     *
     * @param array<string,string> $map
     * @param string               $prefix
     * @return static
     */
    public static function fromTxRep(array $map, string $prefix): static {
        $hex = \Soneso\StellarSDK\Xdr\TxRepHelper::getValue($map, $prefix) ?? '';
        return new static(\Soneso\StellarSDK\Xdr\TxRepHelper::hexToBytes($hex));
    }
}