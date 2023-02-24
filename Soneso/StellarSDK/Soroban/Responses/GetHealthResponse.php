<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

/**
 * General node health check response.
 */
class GetHealthResponse extends SorobanRpcResponse
{
    const HEALTHY = "healthy";

    public ?string $status = null;

    public static function fromJson(array $json) : GetHealthResponse {
        $result = new GetHealthResponse($json);
        if (isset($json['result'])) {
            $result->status = $json['result']['status'];
        } else if (isset($json['error'])) {
            $result->error = SorobanRpcErrorResponse::fromJson($json);
        }
        return $result;
    }

    /**
     * @return string|null health status.
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @param string|null $status
     */
    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }
}