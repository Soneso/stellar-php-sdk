<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use InvalidArgumentException;

class XdrContractCodeEntryExtV1
{
    public XdrExtensionPoint $ext;
    public XdrContractCodeCostInputs $costInputs;

    /**
     * @param XdrExtensionPoint $ext
     * @param XdrContractCodeCostInputs $costInputs
     */
    public function __construct(XdrExtensionPoint $ext, XdrContractCodeCostInputs $costInputs)
    {
        $this->ext = $ext;
        $this->costInputs = $costInputs;
    }


    public function encode(): string {
        $bytes = $this->ext->encode();
        $bytes .= $this->costInputs->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrContractCodeEntryExtV1 {
        $ext = XdrExtensionPoint::decode($xdr);
        $costInputs = XdrContractCodeCostInputs::decode($xdr);

        return new XdrContractCodeEntryExtV1(
            $ext,
            $costInputs,
        );
    }

    /**
     * @return XdrExtensionPoint
     */
    public function getExt(): XdrExtensionPoint
    {
        return $this->ext;
    }

    /**
     * @param XdrExtensionPoint $ext
     */
    public function setExt(XdrExtensionPoint $ext): void
    {
        $this->ext = $ext;
    }

    /**
     * @return XdrContractCodeCostInputs
     */
    public function getCostInputs(): XdrContractCodeCostInputs
    {
        return $this->costInputs;
    }

    /**
     * @param XdrContractCodeCostInputs $costInputs
     */
    public function setCostInputs(XdrContractCodeCostInputs $costInputs): void
    {
        $this->costInputs = $costInputs;
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

    public function toJsonValue(): array {
        return [
            'ext' => 'v0',
            'cost_inputs' => $this->costInputs->toJsonValue(),
        ];
    }

    public static function fromJsonValue(mixed $value): static {
        if (is_array($value) && array_key_exists('$schema', $value)) {
            unset($value['$schema']);
        }
        if (!is_array($value)) {
            throw new InvalidArgumentException(
                'Expected object for XdrContractCodeEntryExtV1 JSON value, got ' . get_debug_type($value)
            );
        }
        if (!array_key_exists('ext', $value)) {
            throw new InvalidArgumentException(
                'Missing required field ext for XdrContractCodeEntryExtV1'
            );
        }
        if ($value['ext'] !== 'v0') {
            throw new InvalidArgumentException(
                'Expected v0 for XdrContractCodeEntryExtV1 extension point field ext, got '
                . (is_string($value['ext']) ? "'" . XdrJsonHelper::safePreview($value['ext']) . "'" : get_debug_type($value['ext']))
            );
        }
        if (!array_key_exists('cost_inputs', $value)) {
            throw new InvalidArgumentException(
                'Missing required field cost_inputs for XdrContractCodeEntryExtV1'
            );
        }
        $costInputs = XdrContractCodeCostInputs::fromJsonValue($value['cost_inputs']);
        return new static(new XdrExtensionPoint(0), $costInputs);
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
