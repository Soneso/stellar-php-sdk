<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

/**
 * Response for getLatestLedger request.
 *
 * @package Soneso\StellarSDK\Soroban\Responses
 * @see https://developers.stellar.org/network/soroban-rpc/api-reference/methods/getLatestLedger
 */
class GetLatestLedgerResponse extends SorobanRpcResponse
{
    /**
     * @var string|null $id Hash identifier of the latest ledger as hex-encoded string
     */
    public ?string $id = null;

    /**
     * @var int|null $protocolVersion Stellar Core protocol version associated with the latest ledger
     */
    public ?int $protocolVersion = null;

    /**
     * @var int|null $sequence The sequence number of the latest ledger known to Soroban RPC
     */
    public ?int $sequence = null;

    /**
     * Creates an instance from JSON-RPC response data
     *
     * @param array<string,mixed> $json The JSON response data
     * @return static The created instance
     */
    public static function fromJson(array $json) : GetLatestLedgerResponse {
        $result = new GetLatestLedgerResponse($json);
        if (isset($json['result'])) {
            $result->id = $json['result']['id'];
            $result->protocolVersion = $json['result']['protocolVersion'];
            $result->sequence = $json['result']['sequence'];
        } else if (isset($json['error'])) {
            $result->error = SorobanRpcErrorResponse::fromJson($json);
        }
        return $result;
    }

    /**
     * @return string|null Hash identifier of the latest ledger as hex-encoded string
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param string|null $id Hash identifier of the latest ledger
     * @return void
     */
    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return int|null Stellar Core protocol version associated with the latest ledger
     */
    public function getProtocolVersion(): ?int
    {
        return $this->protocolVersion;
    }

    /**
     * @param int|null $protocolVersion Stellar Core protocol version
     * @return void
     */
    public function setProtocolVersion(?int $protocolVersion): void
    {
        $this->protocolVersion = $protocolVersion;
    }

    /**
     * @return int|null The sequence number of the latest ledger known to Soroban RPC
     */
    public function getSequence(): ?int
    {
        return $this->sequence;
    }

    /**
     * @param int|null $sequence The sequence number of the latest ledger
     * @return void
     */
    public function setSequence(?int $sequence): void
    {
        $this->sequence = $sequence;
    }

}