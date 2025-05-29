<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Contract;

/**
 * This class is used in ContractSpec
 */
class NativeUnionVal
{
    public string $tag;
    public ?array $values = null;

    /**
     * @param string $tag
     * @param array|null $values
     */
    public function __construct(string $tag, ?array $values = null)
    {
        $this->tag = $tag;
        $this->values = $values;
    }
}