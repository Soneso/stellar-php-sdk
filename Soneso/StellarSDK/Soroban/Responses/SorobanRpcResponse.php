<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

/**
 * Abstract class for soroban rpc responses.
 *
 * @package Soneso\StellarSDK\Soroban\Responses
 */
abstract class SorobanRpcResponse
{
    /**
     * @var SorobanRpcErrorResponse|null $error Error data if the response is an error response
     */
    public ?SorobanRpcErrorResponse $error = null;

    /**
     * @param array<array-key,mixed> $jsonResponse The complete JSON response as data array
     */
    public function __construct(
        public array $jsonResponse,
    )
    {
    }

    /**
     * @return array<array-key,mixed> The complete JSON response as data array
     */
    public function getJsonResponse(): array
    {
        return $this->jsonResponse;
    }

    /**
     * @param array<array-key,mixed> $jsonResponse The complete JSON response as data array
     * @return void
     */
    public function setJsonResponse(array $jsonResponse): void
    {
        $this->jsonResponse = $jsonResponse;
    }

    /**
     * @return SorobanRpcErrorResponse|null Error data if the response is an error response
     */
    public function getError(): ?SorobanRpcErrorResponse
    {
        return $this->error;
    }

    /**
     * @param SorobanRpcErrorResponse|null $error Error data if the response is an error response
     * @return void
     */
    public function setError(?SorobanRpcErrorResponse $error): void
    {
        $this->error = $error;
    }

}