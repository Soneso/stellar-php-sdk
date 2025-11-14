<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

use Soneso\StellarSDK\Xdr\XdrSorobanTransactionData;

/**
 * Part of the simulateTransaction response.
 *
 * It can only present on successful simulation (i.e. no error) of InvokeHostFunction operations.
 * If present, it indicates the simulation detected expired ledger entries which requires restoring
 * with the submission of a RestoreFootprint operation before submitting the InvokeHostFunction operation.
 * The minResourceFee and transactionData fields should be used to construct the transaction
 * containing the RestoreFootprint operation.
 *
 * @package Soneso\StellarSDK\Soroban\Responses
 * @see https://developers.stellar.org/network/soroban-rpc/api-reference/methods/simulateTransaction
 */
class RestorePreamble
{

    /**
     * @var XdrSorobanTransactionData $transactionData The recommended Soroban Transaction Data to use when submitting
     * the RestoreFootprint operation.
     */
    public XdrSorobanTransactionData $transactionData;

    /**
     * @var int $minResourceFee Recommended minimum resource fee to add when submitting the RestoreFootprint operation.
     * This fee is to be added on top of the Stellar network fee.
     */
    public int $minResourceFee;

    /**
     * Constructor.
     *
     * @param XdrSorobanTransactionData $transactionData The recommended Soroban Transaction Data to use when submitting
     *  the RestoreFootprint operation.
     * @param int $minResourceFee Recommended minimum resource fee to add when submitting the RestoreFootprint operation.
     *  This fee is to be added on top of the Stellar network fee.
     */
    public function __construct(XdrSorobanTransactionData $transactionData, int $minResourceFee)
    {
        $this->transactionData = $transactionData;
        $this->minResourceFee = $minResourceFee;
    }

    /**
     * Creates an instance from JSON-RPC response data
     *
     * @param array<string,mixed> $json The JSON response data
     * @return static The created instance
     * @throws \InvalidArgumentException If XDR data is malformed
     */
    public static function fromJson(array $json) : RestorePreamble {
        $transactionData = XdrSorobanTransactionData::fromBase64Xdr($json['transactionData']);
        $minResourceFee = intval($json['minResourceFee']);

        return new RestorePreamble($transactionData, $minResourceFee);
    }

    /**
     * @return XdrSorobanTransactionData The recommended Soroban Transaction Data to use when submitting
     *  the RestoreFootprint operation.
     */
    public function getTransactionData(): XdrSorobanTransactionData
    {
        return $this->transactionData;
    }

    /**
     * @param XdrSorobanTransactionData $transactionData The recommended Soroban Transaction Data to use when submitting
     *  the RestoreFootprint operation.
     */
    public function setTransactionData(XdrSorobanTransactionData $transactionData): void
    {
        $this->transactionData = $transactionData;
    }

    /**
     * @return int Recommended minimum resource fee to add when submitting the RestoreFootprint operation.
     *   This fee is to be added on top of the Stellar network fee.
     */
    public function getMinResourceFee(): int
    {
        return $this->minResourceFee;
    }

    /**
     * @param int $minResourceFee Recommended minimum resource fee to add when submitting the RestoreFootprint operation.
     *   This fee is to be added on top of the Stellar network fee.
     */
    public function setMinResourceFee(int $minResourceFee): void
    {
        $this->minResourceFee = $minResourceFee;
    }

}