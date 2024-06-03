<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

/**
 * Part of the simulateTransaction response.
 * See: https://developers.stellar.org/network/soroban-rpc/api-reference/methods/simulateTransaction
 *
 * The cost object is legacy, inaccurate, and will be deprecated in future RPC releases.
 * Please decode transactionData XDR from the simulate transaction response to retrieve the correct resources.
 */
class SimulateTransactionCost
{
    /**
     * @var int $cpuInsns Total cpu instructions consumed by this transaction.
     */
    public int $cpuInsns;

    /**
     * @var int $memBytes Total memory bytes allocated by this transaction.
     */
    public int $memBytes;

    protected function loadFromJson(array $json) : void {
        $this->cpuInsns = intval($json['cpuInsns']);
        $this->memBytes = intval($json['memBytes']);
    }

    public static function fromJson(array $json) : SimulateTransactionCost {
        $result = new SimulateTransactionCost();
        $result->loadFromJson($json);
        return $result;
    }

    /**
     * @return int Number of the total cpu instructions consumed by this transaction.
     */
    public function getCpuInsns(): int
    {
        return $this->cpuInsns;
    }

    /**
     * @param int $cpuInsns Number of the total cpu instructions consumed by this transaction
     */
    public function setCpuInsns(int $cpuInsns): void
    {
        $this->cpuInsns = $cpuInsns;
    }

    /**
     * @return int Number of the total memory bytes allocated by this transaction.
     */
    public function getMemBytes(): int
    {
        return $this->memBytes;
    }

    /**
     * @param int $memBytes Number of the total memory bytes allocated by this transaction.
     */
    public function setMemBytes(int $memBytes): void
    {
        $this->memBytes = $memBytes;
    }

}