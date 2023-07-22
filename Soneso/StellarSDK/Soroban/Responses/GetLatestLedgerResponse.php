<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

/**
 * Response for getLatestLedger()
 * See: https://soroban.stellar.org/api/methods/getLatestLedger
 */
class GetLatestLedgerResponse extends SorobanRpcResponse
{
    ///  hash of the latest ledger as a hex-encoded string
    public ?string $id = null;

    /// Stellar Core protocol version associated with the latest ledger
    public ?string $protocolVersion = null;

    /// sequence number of the latest ledger
    public ?int $sequence = null;

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
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getProtocolVersion(): ?string
    {
        return $this->protocolVersion;
    }

    /**
     * @return int|null
     */
    public function getSequence(): ?int
    {
        return $this->sequence;
    }

    /**
     * @param int|null $sequence
     */
    public function setSequence(?int $sequence): void
    {
        $this->sequence = $sequence;
    }

}