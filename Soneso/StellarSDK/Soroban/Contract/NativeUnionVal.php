<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Contract;

/**
 * Container for contract union variant values
 *
 * This class represents a union variant value for Soroban smart contracts. Unions in Soroban
 * allow for variant types where one of multiple possible cases is active. Each union has a
 * tag (case name) and optional associated values for that case.
 *
 * Used by ContractSpec::nativeToXdrSCVal() when converting native PHP values to XDR for
 * user-defined union types.
 *
 * @package Soneso\StellarSDK\Soroban\Contract
 * @see ContractSpec::nativeToXdrSCVal() For type conversion usage
 * @see ContractSpec::nativeToUnion() For union-specific conversion
 * @since 1.0.0
 */
class NativeUnionVal
{
    /**
     * @var string $tag The name of the union case/variant to use
     */
    public string $tag;

    /**
     * @var array|null $values The values associated with this union case. Null for void cases,
     * array of values for tuple cases. The array must match the expected types defined in the
     * contract spec for this union case.
     */
    public ?array $values = null;

    /**
     * @param string $tag The name of the union case/variant to use
     * @param array|null $values The values for this case. Null for void cases, array for tuple cases.
     */
    public function __construct(string $tag, ?array $values = null)
    {
        $this->tag = $tag;
        $this->values = $values;
    }
}