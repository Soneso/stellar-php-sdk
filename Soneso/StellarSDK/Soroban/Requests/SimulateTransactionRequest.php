<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Requests;

use Soneso\StellarSDK\Transaction;

class SimulateTransactionRequest
{
    public Transaction $transaction;
    public ?ResourceConfig $resourceConfig = null;

    /**
     * @param Transaction $transaction The transaction to be sumbitted.
     * @param ResourceConfig|null $resourceConfig allows budget instruction leeway used in preflight calculations to be configured. If not provided the leeway defaults to 3000000 instructions.
     */
    public function __construct(Transaction $transaction, ?ResourceConfig $resourceConfig = null)
    {
        $this->transaction = $transaction;
        $this->resourceConfig = $resourceConfig;
    }

    public function getRequestParams() : array {
        $params = array(
            'transaction' => $this->transaction->toEnvelopeXdrBase64()
        );

        if ($this->resourceConfig != null) {
            $params['resourceConfig'] = $this->resourceConfig->getRequestParams();
        }
        return $params;
    }

    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }

    public function setTransaction(Transaction $transaction): void
    {
        $this->transaction = $transaction;
    }

    public function getResourceConfig(): ?ResourceConfig
    {
        return $this->resourceConfig;
    }

    public function setResourceConfig(?ResourceConfig $resourceConfig): void
    {
        $this->resourceConfig = $resourceConfig;
    }

}