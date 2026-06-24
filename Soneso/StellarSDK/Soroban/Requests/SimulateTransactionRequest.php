<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Requests;

use Soneso\StellarSDK\Transaction;

/**
 * Soroban Simulate Transaction Request.
 *
 * The useUpgradedAuth flag requests that the RPC node return ADDRESS_V2 credential entries
 * (Protocol 27, CAP-71) instead of legacy ADDRESS entries during recording-mode simulation.
 * The flag is effective only in recording mode: when authMode is "record" or
 * "record_allow_nonroot", or when authMode is unset and the transaction carries no auth
 * entries (the RPC then defaults to recording). It is ignored under "enforce". RPCs without
 * Protocol 27 support silently ignore the flag and return legacy ADDRESS entries — support
 * is detected by inspecting the credential arm of returned entries, not by any error signal.
 *
 * The key "useUpgradedAuth" is omitted from the request when the flag is false (the default), so
 * existing call sites require no changes and pre-27 RPCs never see the key.
 *
 * @see https://developers.stellar.org/network/soroban-rpc/api-reference/methods/simulateTransaction
 * @package Soneso\StellarSDK\Soroban\Requests
 */
class SimulateTransactionRequest
{
    /**
     * Constructor.
     *
     * @param Transaction $transaction The transaction to be submitted. In order for the RPC server to successfully
     *  simulate a Stellar transaction, the provided transaction must contain only a single operation of the
     *  type invokeHostFunction.
     * @param ResourceConfig|null $resourceConfig Contains configuration for how resources will be calculated when simulating
     *  transactions.
     * @param string|null $authMode Support for non-root authorization. Only available for protocol >= 23.
     *  Possible values: "enforce" | "record" | "record_allow_nonroot"
     * @param bool $useUpgradedAuth When true, requests ADDRESS_V2 credential entries (Protocol 27, CAP-71).
     *  The key is omitted when false; RPCs without support silently ignore it and return legacy entries.
     *  Invalid on pre-27 networks: emitting ADDRESS_V2 entries on a pre-27 network invalidates the transaction.
     */
    public function __construct(
        public Transaction $transaction,
        public ?ResourceConfig $resourceConfig = null,
        public ?string $authMode = null,
        public bool $useUpgradedAuth = false,
    ) {
    }

    /**
     * Builds and returns the request parameters array for the RPC API call.
     *
     * The "useUpgradedAuth" key is included only when $useUpgradedAuth is true. Omitting the key (the default)
     * preserves compatibility with pre-27 RPCs that do not recognize it.
     *
     * @return array<string, mixed> The request parameters formatted for Soroban RPC
     */
    public function getRequestParams() : array {
        $params = array(
            'transaction' => $this->transaction->toEnvelopeXdrBase64()
        );

        if ($this->resourceConfig !== null) {
            $params['resourceConfig'] = $this->resourceConfig->getRequestParams();
        }
        if ($this->authMode !== null) {
            $params['authMode'] = $this->authMode;
        }
        if ($this->useUpgradedAuth) {
            $params['useUpgradedAuth'] = true;
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

    /**
     * Returns whether ADDRESS_V2 credential entries are requested during simulation.
     *
     * @return bool true when the useUpgradedAuth flag is set
     */
    public function getUseUpgradedAuth(): bool
    {
        return $this->useUpgradedAuth;
    }

    /**
     * Sets the useUpgradedAuth flag.
     *
     * When true, "useUpgradedAuth": true is included in the request params. RPCs without Protocol 27 support
     * silently ignore the flag. Do not enable on pre-27 networks.
     *
     * @param bool $useUpgradedAuth whether to request ADDRESS_V2 credential entries
     */
    public function setUseUpgradedAuth(bool $useUpgradedAuth): void
    {
        $this->useUpgradedAuth = $useUpgradedAuth;
    }

}