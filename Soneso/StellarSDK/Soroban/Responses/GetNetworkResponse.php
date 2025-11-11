<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

/**
 * Response for the getNetwork request.
 *
 * @package Soneso\StellarSDK\Soroban\Responses
 * @see https://developers.stellar.org/network/soroban-rpc/api-reference/methods/getNetwork
 */
class GetNetworkResponse extends SorobanRpcResponse
{
    /**
     * @var string|null $friendbotUrl URL of this network's friendbot faucet
     */
    public ?string $friendbotUrl = null;

    /**
     * @var string|null $passphrase Network passphrase configured for this Soroban RPC node
     */
    public ?string $passphrase = null;

    /**
     * @var int|null $protocolVersion Stellar Core protocol version associated with the latest ledger
     */
    public ?int $protocolVersion = null;

    /**
     * Creates an instance from JSON-RPC response data
     *
     * @param array<string,mixed> $json The JSON response data
     * @return static The created instance
     */
    public static function fromJson(array $json) : GetNetworkResponse {
        $result = new GetNetworkResponse($json);
        if (isset($json['result'])) {
            if (isset($json['result']['friendbotUrl'])) {
                $result->friendbotUrl = $json['result']['friendbotUrl'];
            }
            if (isset($json['result']['passphrase'])) {
                $result->passphrase = $json['result']['passphrase'];
            }
            if (isset($json['result']['protocolVersion'])) {
                $result->protocolVersion = $json['result']['protocolVersion'];
            }
        } else if (isset($json['error'])) {
            $result->error = SorobanRpcErrorResponse::fromJson($json);
        }
        return $result;
    }

    /**
     * @return string|null URL of this network's friendbot faucet
     */
    public function getFriendbotUrl(): ?string
    {
        return $this->friendbotUrl;
    }

    /**
     * @param string|null $friendbotUrl URL of the friendbot faucet
     * @return void
     */
    public function setFriendbotUrl(?string $friendbotUrl): void
    {
        $this->friendbotUrl = $friendbotUrl;
    }

    /**
     * @return string|null Network passphrase configured for this Soroban RPC node
     */
    public function getPassphrase(): ?string
    {
        return $this->passphrase;
    }

    /**
     * @param string|null $passphrase Network passphrase
     * @return void
     */
    public function setPassphrase(?string $passphrase): void
    {
        $this->passphrase = $passphrase;
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

}