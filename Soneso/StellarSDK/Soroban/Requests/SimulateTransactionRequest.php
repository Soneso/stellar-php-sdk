<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Requests;

use Soneso\StellarSDK\Transaction;

/**
 * Soroban Simulate Transaction Request.
 *
 * The useUpgradedAuth flag selects the credential arm of auth entries recorded during
 * simulation: true (the default) requests ADDRESS_V2 entries (Protocol 27, CAP-71), false
 * requests legacy ADDRESS entries. The flag is effective only in recording mode: when
 * authMode is "record" or "record_allow_nonroot", or when authMode is unset and the
 * transaction carries no auth entries (the RPC then defaults to recording). It is ignored
 * under "enforce". RPCs without Protocol 27 support silently ignore the flag and return
 * legacy ADDRESS entries — support is detected by inspecting the credential arm of
 * returned entries, not by any error signal.
 *
 * The key "useUpgradedAuth" is always present in the request params with the current flag
 * value. Set the flag to false on a network below Protocol 27, where ADDRESS_V2 entries
 * invalidate the transaction.
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
     * @param bool $useUpgradedAuth When true (the default), requests ADDRESS_V2 credential entries
     *  (Protocol 27, CAP-71); when false, requests legacy ADDRESS entries. RPCs without support
     *  silently ignore the flag and return legacy entries. Set to false on a network below
     *  Protocol 27, where ADDRESS_V2 entries invalidate the transaction.
     */
    public function __construct(
        public Transaction $transaction,
        public ?ResourceConfig $resourceConfig = null,
        public ?string $authMode = null,
        public bool $useUpgradedAuth = true,
    ) {
    }

    /**
     * Builds and returns the request parameters array for the RPC API call.
     *
     * The "useUpgradedAuth" key is always included with the current flag value.
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
        $params['useUpgradedAuth'] = $this->useUpgradedAuth;
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
     * When true, ADDRESS_V2 credential entries are requested during recording-mode simulation;
     * when false, legacy ADDRESS entries. RPCs without Protocol 27 support silently ignore the
     * flag. Set to false on networks below Protocol 27.
     *
     * @param bool $useUpgradedAuth whether to request ADDRESS_V2 credential entries
     */
    public function setUseUpgradedAuth(bool $useUpgradedAuth): void
    {
        $this->useUpgradedAuth = $useUpgradedAuth;
    }

}