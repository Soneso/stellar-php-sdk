<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

/**
 * Response for fee statistics query.
 *
 * @package Soneso\StellarSDK\Soroban\Responses
 * @see https://developers.stellar.org/network/soroban-rpc/api-reference/methods/getFeeStats
 */
class GetFeeStatsResponse extends SorobanRpcResponse
{
    /**
     * @var InclusionFee|null $sorobanInclusionFee Inclusion fee distribution statistics for Soroban transactions
     */
    public ?InclusionFee $sorobanInclusionFee = null;

    /**
     * @var InclusionFee|null $inclusionFee Fee distribution statistics for Stellar (non-Soroban) transactions normalized per operation
     */
    public ?InclusionFee $inclusionFee = null;

    /**
     * @var int|null $latestLedger The sequence number of the latest ledger known to Soroban RPC at the time it handled the request
     */
    public ?int $latestLedger = null;

    /**
     * Creates an instance from JSON-RPC response data
     *
     * @param array<string,mixed> $json The JSON response data
     * @return static The created instance
     */
    public static function fromJson(array $json) : GetFeeStatsResponse {
        $result = new GetFeeStatsResponse($json);
        if (isset($json['result'])) {
            if (isset($json['result']['latestLedger'])) {
                $result->latestLedger = $json['result']['latestLedger'];
            }
            if (isset($json['result']['sorobanInclusionFee'])) {
                $result->sorobanInclusionFee = InclusionFee::fromJson($json['result']['sorobanInclusionFee']);
            }
            if (isset($json['result']['inclusionFee'])) {
                $result->inclusionFee = InclusionFee::fromJson($json['result']['inclusionFee']);
            }
        } else if (isset($json['error'])) {
            $result->error = SorobanRpcErrorResponse::fromJson($json);
        }
        return $result;
    }

}