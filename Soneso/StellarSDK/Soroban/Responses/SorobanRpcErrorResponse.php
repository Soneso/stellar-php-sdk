<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

/**
 * Holds error response info if no successful result is provided.
 *
 * @package Soneso\StellarSDK\Soroban\Responses
 */
class SorobanRpcErrorResponse
{
    /**
     * @var int|null $code Short unique int representing the type of error
     */
    public ?int $code = null;

    /**
     * @var string|null $message Human friendly summary of the error
     */
    public ?string $message = null;

    /**
     * @var array<array-key,mixed>|null $data More data related to the error if available
     */
    public ?array $data = null;

    /**
     * @var array<array-key,mixed> $jsonResponse Complete JSON response received
     */
    public array $jsonResponse;

    /**
     * @param array<array-key,mixed> $jsonResponse Complete JSON response received
     */
    public function __construct(array $jsonResponse)
    {
        $this->jsonResponse = $jsonResponse;
    }

    /**
     * Creates an instance from JSON-RPC response data
     *
     * @param array<string,mixed> $json The JSON response data
     * @return static The created instance
     */
    public static function fromJson(array $json) : SorobanRpcErrorResponse {
        $result = new SorobanRpcErrorResponse($json);
        if (isset($json['error'])) {
            if (isset($json['error']['code'])) {
                $result->code = $json['error']['code'];
            }
            if (isset($json['error']['message'])) {
                $result->message = $json['error']['message'];
            }
            if (isset($json['error']['data'])) {
                $result->data = $json['error']['data'];
            }
        }
        return $result;
    }

    /**
     * @return array<array-key,mixed> Complete JSON response received
     */
    public function getJsonResponse(): array
    {
        return $this->jsonResponse;
    }

    /**
     * @param array<array-key,mixed> $jsonResponse Complete JSON response received
     * @return void
     */
    public function setJsonResponse(array $jsonResponse): void
    {
        $this->jsonResponse = $jsonResponse;
    }

    /**
     * @return int|null Short unique int representing the type of error
     */
    public function getCode(): ?int
    {
        return $this->code;
    }

    /**
     * @param int|null $code Short unique int representing the type of error
     * @return void
     */
    public function setCode(?int $code): void
    {
        $this->code = $code;
    }

    /**
     * @return string|null Human friendly summary of the error
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * @param string|null $message Human friendly summary of the error
     * @return void
     */
    public function setMessage(?string $message): void
    {
        $this->message = $message;
    }

    /**
     * @return array<array-key,mixed>|null More data related to the error if available
     */
    public function getData(): ?array
    {
        return $this->data;
    }

    /**
     * @param array<array-key,mixed>|null $data More data related to the error if available
     * @return void
     */
    public function setData(?array $data): void
    {
        $this->data = $data;
    }
}