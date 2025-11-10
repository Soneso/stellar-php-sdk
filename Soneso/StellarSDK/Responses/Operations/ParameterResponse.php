<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Responses\Operations;

/**
 * Represents a Soroban contract function parameter
 *
 * Encapsulates a single parameter passed to a smart contract function during invocation.
 * Contains the parameter type and its encoded value. Used in InvokeHostFunctionOperationResponse
 * to represent the arguments supplied to contract functions.
 *
 * @package Soneso\StellarSDK\Responses\Operations
 */
class ParameterResponse
{
    public string $type;
    public string $value;

    protected function loadFromJson(array $json) : void {
        $this->type = $json['type'];
        $this->value = $json['value'];
    }

    public static function fromJson(array $json) : ParameterResponse {
        $result = new ParameterResponse();
        $result->loadFromJson($json);
        return $result;
    }

    /**
     * Gets the parameter type
     *
     * @return string The Soroban value type (e.g., U32, Symbol, Address)
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Sets the parameter type
     *
     * @param string $type The Soroban value type
     * @return void
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * Gets the encoded parameter value
     *
     * @return string The parameter value as a base64 XDR-encoded string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Sets the encoded parameter value
     *
     * @param string $value The parameter value
     * @return void
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }

}