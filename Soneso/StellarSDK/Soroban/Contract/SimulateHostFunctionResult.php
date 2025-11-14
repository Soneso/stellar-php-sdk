<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Contract;

use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionData;

/**
 * Result container for successful Soroban host function simulation
 *
 * This class encapsulates the data returned from a successful transaction simulation,
 * including the return value, authorization entries, and transaction resource data.
 * It is typically obtained via AssembledTransaction::getSimulationData().
 *
 * @package Soneso\StellarSDK\Soroban\Contract
 * @see AssembledTransaction::getSimulationData() For obtaining this result
 * @see SimulateTransactionResponse For the raw RPC response
 * @since 1.0.0
 */
class SimulateHostFunctionResult
{
    /**
     * @var array<SorobanAuthorizationEntry>|null Authorization entries required for this transaction.
     * Null or empty for read-only calls. Non-empty for state-changing operations that require
     * authorization from account or contract signers.
     */
    public ?array $auth = null;

    /**
     * @var XdrSorobanTransactionData Transaction resource data including footprint and resource limits.
     * This data is applied to the transaction before signing and submission.
     */
    public XdrSorobanTransactionData $transactionData;

    /**
     * @var XdrSCVal The return value from the contract function call.
     * For read-only calls, this contains the result. For write calls, this is available
     * after the transaction completes successfully.
     */
    public XdrSCVal $returnedValue;


    /**
     * Constructor.
     * @param XdrSorobanTransactionData $transactionData
     * @param XdrSCVal $returnedValue
     * @param array<SorobanAuthorizationEntry>|null $auth
     */
    public function __construct(XdrSorobanTransactionData $transactionData, XdrSCVal $returnedValue, ?array $auth = null)
    {
        $this->auth = $auth;
        $this->transactionData = $transactionData;
        $this->returnedValue = $returnedValue;
    }

}