<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

/**
 * The source of a heuristically selected contract error from a failed simulation.
 *
 * @see ReportedContractError
 * @see SimulateTransactionResponse::reportedContractError()
 */
enum ContractErrorProvenance
{
    /** The leading host-error header names the contract error. */
    case HOST_ERROR;

    /** The earliest qualifying diagnostic event of a failed call names the code. */
    case DIAGNOSTIC_EVENTS;
}
