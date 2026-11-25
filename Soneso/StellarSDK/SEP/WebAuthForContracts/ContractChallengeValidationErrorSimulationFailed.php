<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\WebAuthForContracts;

use Soneso\StellarSDK\Soroban\Responses\SorobanRpcErrorResponse;
use Throwable;

/**
 * Exception thrown when simulating the signed SEP-45 authentication transaction fails or
 * produces a disallowed read-write ledger footprint.
 *
 * This exception is thrown when:
 * - Soroban RPC returns a JSON-RPC error or simulation execution error
 * - The simulation response does not include transaction data and a ledger footprint
 * - The simulation read-write footprint contains any entry outside the allowlist (nonce
 *   contract data for the client account, the server account, and optionally the client
 *   domain account when present in the challenge, plus a persistent non-nonce contract data
 *   entry for the client contract, a persistent contract instance for WEB_AUTH_CONTRACT_ID,
 *   or a CONTRACT_CODE entry, marked for restoration in SorobanTransactionData.ext) or
 *   malformed archivedSorobanEntries metadata
 * - No valid authorization entries are available to construct the simulation transaction
 *
 * Untrusted RPC and simulation error texts are kept out of the exception message and exposed
 * separately through getSimulationError() and getRpcError() for caller diagnostics.
 *
 * Security Impact:
 * Critical security check. Simulating the signed authorization entries verifies the permitted
 * read-write footprint of the web_auth_verify invocation before the client submits its signed
 * entries to the server. Restoration metadata does not prove that the invocation leaves a
 * restored entry unchanged.
 *
 * @package Soneso\StellarSDK\SEP\WebAuthForContracts
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0045.md SEP-45 Specification
 */
class ContractChallengeValidationErrorSimulationFailed extends ContractChallengeValidationError
{
    private readonly ?string $simulationError;
    private readonly ?SorobanRpcErrorResponse $rpcError;

    /**
     * Creates a new simulation failure exception.
     *
     * Accepts the ErrorException constructor parameters with the same names and order, and
     * appends optional diagnostic details reported by the Soroban RPC server.
     *
     * @param string $message Exception message
     * @param int $code Exception code
     * @param int $severity Severity level of the exception
     * @param string|null $filename Filename where the exception is thrown
     * @param int|null $line Line number where the exception is thrown
     * @param Throwable|null $previous Previous throwable used for exception chaining
     * @param string|null $simulationError Untrusted simulation error text (resultError) from the RPC response
     * @param SorobanRpcErrorResponse|null $rpcError Untrusted JSON-RPC error object from the RPC response
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        int $severity = E_ERROR,
        ?string $filename = null,
        ?int $line = null,
        ?Throwable $previous = null,
        ?string $simulationError = null,
        ?SorobanRpcErrorResponse $rpcError = null
    ) {
        parent::__construct($message, $code, $severity, $filename, $line, $previous);
        // Store RPC diagnostic details separately rather than interpolating them into $message
        // so untrusted RPC server text cannot leak into exception messages.
        $this->simulationError = $simulationError;
        $this->rpcError = $rpcError;
    }

    /**
     * Returns the simulation error text from the Soroban RPC response (resultError), if any.
     *
     * This text comes from the RPC server and is untrusted; treat it as untrusted external
     * input before logging, displaying, or forwarding it.
     *
     * @return string|null the simulation error text when simulation execution failed, or null otherwise
     */
    public function getSimulationError(): ?string
    {
        return $this->simulationError;
    }

    /**
     * Returns the JSON-RPC error from the Soroban RPC response, if any.
     *
     * The error text and fields come from the RPC server and are untrusted; treat them as
     * untrusted external input before logging, displaying, or forwarding them.
     *
     * @return SorobanRpcErrorResponse|null the JSON-RPC error object when the RPC returned an error, or null otherwise
     */
    public function getRpcError(): ?SorobanRpcErrorResponse
    {
        return $this->rpcError;
    }
}
