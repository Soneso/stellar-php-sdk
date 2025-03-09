<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Contract;

use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionData;

class SimulateHostFunctionResult
{
    /**
     * @var array<SorobanAuthorizationEntry> $auth auth entries
     */
    public ?array $auth = null;
    public XdrSorobanTransactionData $transactionData;
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