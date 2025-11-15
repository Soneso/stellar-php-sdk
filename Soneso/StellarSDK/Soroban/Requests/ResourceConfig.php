<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Requests;

/**
 * Part of the soroban simulate transaction request.
 *
 * @see https://developers.stellar.org/network/soroban-rpc/api-reference/methods/simulateTransaction
 * @package Soneso\StellarSDK\Soroban\Requests
 */
class ResourceConfig
{
    /**
     * Constructor.
     *
     * @param int $instructionLeeway The number of additional CPU instructions to reserve for budget leeway
     *  in preflight calculations.
     */
    public function __construct(
        public int $instructionLeeway,
    ) {
    }

    /**
     * Builds and returns the request parameters array for the RPC API call.
     *
     * @return array<string, mixed> The request parameters formatted for Soroban RPC
     */
    public function getRequestParams() : array {
        $params = array();
        $params['instructionLeeway'] = $this->instructionLeeway;
        return $params;
    }

    /**
     * @return int The number of additional CPU instructions to reserve for budget leeway
     *  in preflight calculations.
     */
    public function getInstructionLeeway(): int
    {
        return $this->instructionLeeway;
    }

    /**
     * @param int $instructionLeeway The number of additional CPU instructions to reserve for budget leeway
     *  in preflight calculations.
     * @return void
     */
    public function setInstructionLeeway(int $instructionLeeway): void
    {
        $this->instructionLeeway = $instructionLeeway;
    }

}