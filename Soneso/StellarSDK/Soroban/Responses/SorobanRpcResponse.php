<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

/**
 * Abstract class for soroban rpc responses.
 */
abstract class SorobanRpcResponse
{
    /**
     * @var array<array-key, mixed> $jsonResponse the complete json response as data array.
     */
    public array $jsonResponse;

    /**
     * @var SorobanRpcErrorResponse|null If the response is an error response, then here is the error data.
     */
    public ?SorobanRpcErrorResponse $error = null;

    /**
     * @param array<array-key, mixed> $jsonResponse the complete json response as data array.
     */
    public function __construct(array $jsonResponse)
    {
        $this->jsonResponse = $jsonResponse;
    }

    /**
     * @return array<array-key, mixed> the complete json response as data array.
     */
    public function getJsonResponse(): array
    {
        return $this->jsonResponse;
    }

    /**
     * @param array<array-key, mixed> $jsonResponse
     */
    public function setJsonResponse(array $jsonResponse): void
    {
        $this->jsonResponse = $jsonResponse;
    }

    /**
     * @return SorobanRpcErrorResponse|null If the response is an error response, then here is the error data.
     */
    public function getError(): ?SorobanRpcErrorResponse
    {
        return $this->error;
    }

    /**
     * @param SorobanRpcErrorResponse|null $error If the response is an error response, then here is the error data.
     */
    public function setError(?SorobanRpcErrorResponse $error): void
    {
        $this->error = $error;
    }

}