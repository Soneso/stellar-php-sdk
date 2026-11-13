<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

use InvalidArgumentException;

/**
 * An immutable contract error reported by a failed Soroban simulation.
 *
 * @see SimulateTransactionResponse::reportedContractError()
 * @see ContractErrorProvenance
 */
final class ReportedContractError
{
    /**
     * Creates an immutable reported contract error.
     *
     * @param int $code Unsigned 32-bit contract error code; the full range requires 64-bit PHP
     * @param ContractErrorProvenance $provenance Where the failure reports the code
     * @param string|null $contractId Emitting contract as a C strkey, or null for a host header or absent ID
     * @throws InvalidArgumentException If the code is outside 0..4294967295
     */
    public function __construct(
        private readonly int $code,
        private readonly ContractErrorProvenance $provenance,
        private readonly ?string $contractId = null
    ) {
        if ($code < 0 || $code > 4294967295) {
            throw new InvalidArgumentException("Contract error code {$code} must be between 0 and 4294967295");
        }
    }

    /** @return int The unsigned 32-bit contract error code */
    public function getCode(): int
    {
        return $this->code;
    }

    /** @return ContractErrorProvenance Where the failure reports the code */
    public function getProvenance(): ContractErrorProvenance
    {
        return $this->provenance;
    }

    /** @return string|null Emitting contract as a C strkey, or null for a host header or absent ID */
    public function getContractId(): ?string
    {
        return $this->contractId;
    }
}
