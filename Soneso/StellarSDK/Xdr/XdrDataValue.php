<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrDataValue
{
    private ?string $value = null;

    public function __construct(?string $value = null) {
        $this->value = $value;
    }

    /**
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    public function encode() : string {
        $bytes = "";
        if ($this->value) {
            $bytes .= XdrEncoder::boolean(true);
            $bytes .= XdrEncoder::opaqueVariable($this->value);
        }
        else {
            $bytes .= XdrEncoder::boolean(false);
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) :  XdrDataValue {
        $value = null;
        if ($xdr->readBoolean()) {
            $value = $xdr->readOpaqueVariable(64);
        }
        return new XdrDataValue($value);
    }

    public function toBase64Xdr(): string
    {
        return base64_encode($this->encode());
    }

    public static function fromBase64Xdr(string $xdr): static
    {
        $decoded = base64_decode($xdr, true);
        if ($decoded === false) {
            throw new \InvalidArgumentException('Invalid base64-encoded XDR');
        }
        return static::decode(new XdrBuffer($decoded));
    }

    /**
     * SEP-51 (XDR-JSON) emission for the DataValue typedef.
     *
     * The XDR is `typedef opaque DataValue<64>`; per SEP-51 §Opaque the
     * variable-opaque wire form is a hex string. This PHP class is a
     * hand-written wrapper used for optional fields (e.g. ManageData
     * dataValue), so the value field is nullable; the SEP-51 to/from
     * methods preserve the null state by emitting JSON null when the
     * wrapper carries no bytes.
     *
     * Mirrors the bare opaque-typedef emission
     * (XdrJsonHelper::bytesToHex / hexToBytes), with an additional null
     * guard for the optional wrapper semantics.
     */
    public function toJsonValue(): mixed
    {
        if ($this->value === null) {
            return null;
        }
        return XdrJsonHelper::bytesToHex($this->value);
    }

    public static function fromJsonValue(mixed $value): static
    {
        if ($value === null) {
            return new static(null);
        }
        if (!is_string($value)) {
            throw new \InvalidArgumentException(
                'Expected hex string or null for XdrDataValue JSON value, got ' . get_debug_type($value)
            );
        }
        return new static(XdrJsonHelper::hexToBytes($value));
    }

    public function toJson(): string
    {
        return json_encode(
            $this->toJsonValue(),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    public static function fromJson(string $json): static
    {
        return static::fromJsonValue(json_decode($json, true, 512, JSON_THROW_ON_ERROR));
    }
}