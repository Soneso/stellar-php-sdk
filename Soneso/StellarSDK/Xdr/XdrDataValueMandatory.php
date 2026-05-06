<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

use InvalidArgumentException;

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

    public function toBase64Xdr(): string {
        return base64_encode($this->encode());
    }

    public static function fromBase64Xdr(string $xdr): static {
        $decoded = base64_decode($xdr, true);
        if ($decoded === false) {
            throw new InvalidArgumentException('Invalid base64-encoded XDR');
        }
        return static::decode(new XdrBuffer($decoded));
    }

    public function toJsonValue(): string {
        return XdrJsonHelper::bytesToHex($this->value);
    }

    public static function fromJsonValue(mixed $value): static {
        if (!is_string($value)) {
            throw new InvalidArgumentException(
                'Expected string for XdrDataValueMandatory JSON value, got ' . get_debug_type($value)
            );
        }
        return new static(XdrJsonHelper::hexToBytes($value));
    }

    public function toJson(): string {
        return json_encode(
            $this->toJsonValue(),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    public static function fromJson(string $json): static {
        return static::fromJsonValue(json_decode($json, true, 512, JSON_THROW_ON_ERROR));
    }
}