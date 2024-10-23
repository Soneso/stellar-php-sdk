<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

use Soneso\StellarSDK\Soroban\Responses\SorobanRpcResponse;

class GetVersionInfoResponse extends SorobanRpcResponse
{
    /**
     * @var string|null $version The version of the RPC server.
     */
    public ?string $version = null;

    /**
     * @var string|null $commitHash The commit hash of the RPC server.
     */
    public ?string $commitHash = null;

    /**
     * @var string|null $buildTimeStamp The build timestamp of the RPC server.
     */
    public ?string $buildTimeStamp = null;

    /**
     * @var string|null $captiveCoreVersion The version of the Captive Core.
     */
    public ?string $captiveCoreVersion = null;

    /**
     * @var int|null $protocolVersion The protocol version.
     */
    public ?int $protocolVersion = null;


    public static function fromJson(array $json) : GetVersionInfoResponse {
        $result = new GetVersionInfoResponse($json);
        if (isset($json['result'])) {
            if (isset($json['result']['version'])) {
                $result->version = $json['result']['version'];
            }
            if (isset($json['result']['commitHash'])) {
                $result->commitHash = $json['result']['commitHash'];
            }
            if (isset($json['result']['buildTimestamp'])) {
                $result->buildTimeStamp = $json['result']['buildTimestamp'];
            }
            if (isset($json['result']['captiveCoreVersion'])) {
                $result->captiveCoreVersion = $json['result']['captiveCoreVersion'];
            }
            if (isset($json['result']['protocolVersion'])) {
                $result->protocolVersion = $json['result']['protocolVersion'];
            }
        } else if (isset($json['error'])) {
            $result->error = SorobanRpcErrorResponse::fromJson($json);
        }
        return $result;
    }
}