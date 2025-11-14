<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Requests;

use Soneso\StellarSDK\Transaction;

/**
 * Soroban Simulate Transaction Request.
 *
 * @see https://developers.stellar.org/network/soroban-rpc/api-reference/methods/simulateTransaction
 * @package Soneso\StellarSDK\Soroban\Requests
 */
class SimulateTransactionRequest
{
    /**
     * @var Transaction $transaction A Stellar transaction. In order for the RPC server to successfully simulate a
     * Stellar transaction, the provided transaction must contain only a single operation of the
     * type invokeHostFunction.
     */
    public Transaction $transaction;

    /**
     * @var ResourceConfig|null Contains configuration for how resources will be calculated when simulating
     * transactions.
     */
    public ?ResourceConfig $resourceConfig = null;

    /**
     * @var string|null $authMode Support for non-root authorization. Only available for protocol >= 23
     * Possible values: "enforce" | "record" | "record_allow_nonroot"
    */
    public ?string $authMode = null;


    /**
     * @param Transaction $transaction The transaction to be submitted. In order for the RPC server to successfully
     * simulate a Stellar transaction, the provided transaction must contain only a single operation of the
     * type invokeHostFunction.
     * @param ResourceConfig|null $resourceConfig Contains configuration for how resources will be calculated when simulating
     * transactions.
     * @param string|null $authMode Support for non-root authorization. Only available for protocol >= 23
     *  Possible values: "enforce" | "record" | "record_allow_nonroot"
     */
    public function __construct(Transaction $transaction, ?ResourceConfig $resourceConfig = null, ?string $authMode = null)
    {
        $this->transaction = $transaction;
        $this->resourceConfig = $resourceConfig;
        $this->authMode = $authMode;
    }

    /**
     * Builds and returns the request parameters array for the RPC API call.
     *
     * @return array<string, mixed> The request parameters formatted for Soroban RPC
     */
    public function getRequestParams() : array {
        $params = array(
            'transaction' => $this->transaction->toEnvelopeXdrBase64()
        );

        if ($this->resourceConfig != null) {
            $params['resourceConfig'] = $this->resourceConfig->getRequestParams();
        }
        if ($this->authMode != null) {
            $params['authMode'] = $this->authMode;
        }
        return $params;
    }

    /**
     * @return Transaction The transaction to be submitted. In order for the RPC server to successfully
     *  simulate a Stellar transaction, the provided transaction must contain only a single operation of the
     *  type invokeHostFunction.
     */
    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }

    /**
     * @param Transaction $transaction The transaction to be submitted. In order for the RPC server to successfully
     *  simulate a Stellar transaction, the provided transaction must contain only a single operation of the
     *  type invokeHostFunction.
     */
    public function setTransaction(Transaction $transaction): void
    {
        $this->transaction = $transaction;
    }

    /**
     * @return ResourceConfig|null Contains configuration for how resources will be calculated when simulating
     *  transactions.
     */
    public function getResourceConfig(): ?ResourceConfig
    {
        return $this->resourceConfig;
    }

    /**
     * @param ResourceConfig|null $resourceConfig Contains configuration for how resources will be calculated when
     * simulating transactions.
     */
    public function setResourceConfig(?ResourceConfig $resourceConfig): void
    {
        $this->resourceConfig = $resourceConfig;
    }

    /**
     * Returns the authorization mode for transaction simulation.
     * Controls how authorization is handled during simulation.
     *
     * @return string|null The auth mode ("enforce", "record", or "record_allow_nonroot"), or null if not set
     */
    public function getAuthMode(): ?string
    {
        return $this->authMode;
    }

    /**
     * Sets the authorization mode for transaction simulation.
     * Only available for protocol >= 23.
     *
     * @param string|null $authMode The auth mode: "enforce" | "record" | "record_allow_nonroot"
     * @return void
     */
    public function setAuthMode(?string $authMode): void
    {
        $this->authMode = $authMode;
    }

}