<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrManageDataOperation extends XdrManageDataOperationBase
{
    public function __construct(string $key, XdrDataValue $value) {
        parent::__construct($key, $value->getValue());
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->dataName;
    }

    /**
     * @return XdrDataValue
     */
    public function getValue(): XdrDataValue
    {
        return new XdrDataValue($this->dataValue);
    }

    public static function decode(XdrBuffer $xdr): static {
        $dataName = $xdr->readString();
        $dataValue = null;
        if ($xdr->readInteger32() !== 0) {
            $dataValue = $xdr->readOpaqueVariable();
        }
        $instance = new static($dataName, new XdrDataValue($dataValue));
        return $instance;
    }

    /**
     * Override fromTxRep to wrap the raw bytes into XdrDataValue as required
     * by this constructor.
     *
     * @param array<string,string> $map
     * @param string               $prefix
     * @return static
     */
    public static function fromTxRep(array $map, string $prefix): static {
        $dataName = TxRepHelper::unescapeString(TxRepHelper::getValue($map, $prefix . '.dataName') ?? '');
        $rawBytes = null;
        $dataValuePresent = TxRepHelper::getValue($map, $prefix . '.dataValue._present');
        if ($dataValuePresent !== null && $dataValuePresent === 'true') {
            $rawBytes = TxRepHelper::hexToBytes(TxRepHelper::getValue($map, $prefix . '.dataValue') ?? '');
        }
        return new static($dataName, new XdrDataValue($rawBytes));
    }
}
