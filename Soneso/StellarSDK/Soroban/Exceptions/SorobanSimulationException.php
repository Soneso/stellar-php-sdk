<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Exceptions;

use Soneso\StellarSDK\Soroban\Responses\ContractErrorProvenance;
use Soneso\StellarSDK\Soroban\Responses\ReportedContractError;
use Soneso\StellarSDK\Soroban\Responses\SimulateTransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\SorobanRpcErrorResponse;

/**
 * A failed simulation with an immutable event snapshot, a selected contract error
 * and the original mutable RPC error.
 *
 * @see SimulateTransactionResponse::reportedContractError()
 */
class SorobanSimulationException extends \Exception
{
    /** @var list<string> */
    private readonly array $diagnosticEventsXdr;
    private readonly ?ReportedContractError $reportedContractError;
    private readonly ?SorobanRpcErrorResponse $rpcError;

    /**
     * Creates a simulation failure, appending a suffix for diagnostic-event provenance.
     *
     * @param string $message The simulation failure message
     * @param array<string> $diagnosticEventsXdr Base64 events in emission order, including duplicates and undecodable entries
     * @param ReportedContractError|null $reportedContractError The immutable heuristic selection at failure time
     * @param SorobanRpcErrorResponse|null $rpcError The original mutable JSON-RPC error, including data and raw JSON
     */
    public function __construct(
        string $message,
        array $diagnosticEventsXdr = [],
        ?ReportedContractError $reportedContractError = null,
        ?SorobanRpcErrorResponse $rpcError = null
    ) {
        $snapshot = [];
        foreach ($diagnosticEventsXdr as $event) {
            $snapshot[] = $event;
        }
        $this->diagnosticEventsXdr = $snapshot;
        $this->reportedContractError = $reportedContractError;
        $this->rpcError = $rpcError;
        parent::__construct(self::messageWithContractError($message, $reportedContractError), 0);
    }

    /** @return list<string> Immutable base64 event snapshot in emission order, or an empty list when absent */
    public function getDiagnosticEventsXdr(): array
    {
        return $this->diagnosticEventsXdr;
    }

    /** @return ReportedContractError|null The immutable heuristic selection at failure time */
    public function getReportedContractError(): ?ReportedContractError
    {
        return $this->reportedContractError;
    }

    /** @return SorobanRpcErrorResponse|null The original mutable RPC error object, including data and raw JSON */
    public function getRpcError(): ?SorobanRpcErrorResponse
    {
        return $this->rpcError;
    }

    private static function messageWithContractError(string $message, ?ReportedContractError $error): string
    {
        if ($error === null || $error->getProvenance() !== ContractErrorProvenance::DIAGNOSTIC_EVENTS) {
            return $message;
        }
        $suffix = ' (contract error in diagnostic events: Error(Contract, #' . $error->getCode() . ')';
        if ($error->getContractId() !== null) {
            $suffix .= ', contract ' . $error->getContractId();
        }
        return $message . $suffix . ')';
    }
}
