<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

/**
 *  Holds information about the fees expected, instructions used, etc.
 */
class SimulateTransactionCost
{
    /// Stringified-number of the total cpu instructions consumed by this transaction
    public string $cpuInsns;

    /// Stringified-number of the total memory bytes allocated by this transaction
    public string $memBytes;

    protected function loadFromJson(array $json) : void {
        $this->cpuInsns = $json['cpuInsns'];
        $this->memBytes = $json['memBytes'];
    }

    public static function fromJson(array $json) : SimulateTransactionCost {
        $result = new SimulateTransactionCost();
        $result->loadFromJson($json);
        return $result;
    }

    /**
     * @return string Stringified-number of the total cpu instructions consumed by this transaction.
     */
    public function getCpuInsns(): string
    {
        return $this->cpuInsns;
    }

    /**
     * @param string $cpuInsns
     */
    public function setCpuInsns(string $cpuInsns): void
    {
        $this->cpuInsns = $cpuInsns;
    }

    /**
     * @return string Stringified-number of the total memory bytes allocated by this transaction
     */
    public function getMemBytes(): string
    {
        return $this->memBytes;
    }

    /**
     * @param string $memBytes
     */
    public function setMemBytes(string $memBytes): void
    {
        $this->memBytes = $memBytes;
    }

}