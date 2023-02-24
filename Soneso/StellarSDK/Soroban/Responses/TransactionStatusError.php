<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

/**
 * Internal error used within some of the responses.
 */
class TransactionStatusError
{
    public array $jsonResponse;

    /// Short unique string representing the type of error
    public ?string $code = null;

    /// Human friendly summary of the error
    public ?string $message = null; // error message

    /// (optional) More data related to the error if available
    public ?array $data = null;


    public static function fromJson(array $json) : TransactionStatusError {
        $result = new TransactionStatusError($json);
        $result->jsonResponse = $json;
        if (isset($json['error']['code'])) {
            $result->code = $json['error']['code'];
        }
        if (isset($json['error']['message'])) {
            $result->message = $json['error']['message'];
        }
        if (isset($json['error']['data'])) {
            $result->data = $json['error']['data'];
        }
        return $result;
    }


    /**
     * @return string|null Short unique string representing the type of error
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * @param string|null $code
     */
    public function setCode(?string $code): void
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
     * @param string|null $message
     */
    public function setMessage(?string $message): void
    {
        $this->message = $message;
    }

    /**
     * @return array|null (optional) More data related to the error if available
     */
    public function getData(): ?array
    {
        return $this->data;
    }

    /**
     * @param array|null $data
     */
    public function setData(?array $data): void
    {
        $this->data = $data;
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
}