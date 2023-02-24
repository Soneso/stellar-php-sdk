<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

class GetNetworkResponse extends SorobanRpcResponse
{
    public ?string $friendbotUrl = null;
    public ?string $passphrase = null;
    public ?string $protocolVersion = null;

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
     * @return string|null
     */
    public function getFriendbotUrl(): ?string
    {
        return $this->friendbotUrl;
    }

    /**
     * @param string|null $friendbotUrl
     */
    public function setFriendbotUrl(?string $friendbotUrl): void
    {
        $this->friendbotUrl = $friendbotUrl;
    }

    /**
     * @return string|null
     */
    public function getPassphrase(): ?string
    {
        return $this->passphrase;
    }

    /**
     * @param string|null $passphrase
     */
    public function setPassphrase(?string $passphrase): void
    {
        $this->passphrase = $passphrase;
    }

    /**
     * @return string|null
     */
    public function getProtocolVersion(): ?string
    {
        return $this->protocolVersion;
    }

    /**
     * @param string|null $protocolVersion
     */
    public function setProtocolVersion(?string $protocolVersion): void
    {
        $this->protocolVersion = $protocolVersion;
    }
}