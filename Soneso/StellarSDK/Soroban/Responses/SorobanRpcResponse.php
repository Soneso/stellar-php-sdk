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
    public array $jsonResponse;
    public ?SorobanRpcErrorResponse $error = null;

    /**
     * @param array $jsonResponse
     */
    public function __construct(array $jsonResponse)
    {
        $this->jsonResponse = $jsonResponse;
    }

    /**
     * @return array
     */
    public function getJsonResponse(): array
    {
        return $this->jsonResponse;
    }

    /**
     * @param array $jsonResponse
     */
    public function setJsonResponse(array $jsonResponse): void
    {
        $this->jsonResponse = $jsonResponse;
    }

    /**
     * @return SorobanRpcErrorResponse|null
     */
    public function getError(): ?SorobanRpcErrorResponse
    {
        return $this->error;
    }

    /**
     * @param SorobanRpcErrorResponse|null $error
     */
    public function setError(?SorobanRpcErrorResponse $error): void
    {
        $this->error = $error;
    }

}