<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban;

/**
 * Response for fetching current info about a stellar account.
 */
class GetAccountResponse extends SorobanRpcResponse
{
    /// Account Id of the account
    public ?string $id = null;

    /// Current sequence number of the account
    public ?string $sequence = null;

    public static function fromJson(array $json) : GetAccountResponse {
        $result = new GetAccountResponse($json);
        if (isset($json['result'])) {
            $result->id = $json['result']['id'];
            $result->sequence = $json['result']['sequence'];
        } else if (isset($json['error'])) {
            $result->error = SorobanRpcErrorResponse::fromJson($json);
        }
        return $result;
    }

    /**
     * @return string|null account id.
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param string|null $id
     */
    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string|null current sequence number.
     */
    public function getSequence(): ?string
    {
        return $this->sequence;
    }

    /**
     * @param string|null $sequence
     */
    public function setSequence(?string $sequence): void
    {
        $this->sequence = $sequence;
    }
}