<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

/**
 * @phpstan-consistent-constructor
 */
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
}
