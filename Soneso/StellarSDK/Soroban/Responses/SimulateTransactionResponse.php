<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Soroban\Footprint;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;
use Soneso\StellarSDK\Xdr\XdrContractEventType;
use Soneso\StellarSDK\Xdr\XdrDiagnosticEvent;
use Soneso\StellarSDK\Xdr\XdrSCErrorType;
use Soneso\StellarSDK\Xdr\XdrSCValType;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionData;

/**
 * Response that will be received when submitting a trial contract invocation.
 *
 * @package Soneso\StellarSDK\Soroban\Responses
 * @see https://developers.stellar.org/network/soroban-rpc/api-reference/methods/simulateTransaction
 * @see SendTransactionResponse For submitting transactions after simulation
 */
class SimulateTransactionResponse extends SorobanRpcResponse
{
    /**
     * @var int $latestLedger The sequence number of the latest ledger known to Soroban RPC at the time it handled the request
     */
    public int $latestLedger;

    /**
     * @var int|null $minResourceFee Recommended minimum resource fee to add when submitting the transaction (not present in case of error)
     */
    public ?int $minResourceFee = null;

    /**
     * @var SimulateTransactionResults|null Results for the Host Function invocation (only present on successful simulation of InvokeHostFunction operations)
     */
    public ?SimulateTransactionResults $results = null;

    /**
     * @var XdrSorobanTransactionData|null The recommended Soroban Transaction Data containing refundable fee and resource usage information
     */
    public ?XdrSorobanTransactionData $transactionData = null;

    /**
     * @var array<string>|null Array of serialized base64 event strings emitted during contract invocation (can be present on error for extra context)
     */
    public ?array $events = null;

    /**
     * @var RestorePreamble|null Preamble indicating archived ledger entries that need restoration before submission
     */
    public ?RestorePreamble $restorePreamble = null;

    /**
     * @var array<LedgerEntryChange>|null Array of ledger entries before and after simulation for tracking state changes
     */
    public ?array $stateChanges = null;

    /**
     * @var string|null Error details explaining why the invoke host function call failed
     */
    public ?string $resultError = null;

    /**
     * Selects a contract error from the current failure fields without raising.
     *
     * A contract-error header wins. For Context or Auth headers, the earliest
     * diagnostic event of a failed call with exactly the symbol error and a typed
     * contract error as its topics is selected. Undecodable entries are skipped.
     * Selection is a heuristic: earlier caught or rolled-back errors and multiple
     * failing contracts can affect it, and foreign codes can collide with known
     * error tables. A selected event does not prove causation. Without RPC debug
     * events, only a contract-error header supplies a code.
     *
     * @return ReportedContractError|null The selected immutable value, or null when none qualifies
     * @see ReportedContractError
     */
    public function reportedContractError(): ?ReportedContractError
    {
        if ($this->resultError === null) {
            return null;
        }
        $lines = preg_split('/\r\n|\r|\n/', $this->resultError, 2);
        if ($lines === false || preg_match('~^\s*(?:HostError:\s*)?Error\(\s*([A-Za-z]+)\s*,\s*([^()]*?)\s*\)\s*$~D', $lines[0], $header) !== 1) {
            return null;
        }
        if ($header[1] === 'Contract') {
            $code = self::parseContractErrorCode($header[2]);
            return $code === null ? null : new ReportedContractError($code, ContractErrorProvenance::HOST_ERROR);
        }
        if ($header[1] !== 'Context' && $header[1] !== 'Auth') {
            return null;
        }
        foreach ($this->events ?? [] as $encoded) {
            try {
                $diagnostic = XdrDiagnosticEvent::fromBase64Xdr($encoded);
                $event = $diagnostic->event;
                $body = $event->body->v0;
                if ($diagnostic->inSuccessfulContractCall !== false ||
                    $event->type->getValue() !== XdrContractEventType::CONTRACT_EVENT_TYPE_DIAGNOSTIC ||
                    $body === null || count($body->topics) !== 2) {
                    continue;
                }
                [$symbol, $value] = $body->topics;
                if ($symbol->type->getValue() !== XdrSCValType::SCV_SYMBOL || $symbol->sym !== 'error' ||
                    $value->type->getValue() !== XdrSCValType::SCV_ERROR || $value->error === null ||
                    $value->error->type->getValue() !== XdrSCErrorType::SCE_CONTRACT ||
                    $value->error->contractCode === null) {
                    continue;
                }
                $contractId = $event->hash === null ? null : StrKey::encodeContractId($event->hash);
                return new ReportedContractError($value->error->contractCode,
                    ContractErrorProvenance::DIAGNOSTIC_EVENTS, $contractId);
            } catch (\Throwable) {
                continue;
            }
        }
        return null;
    }

    private static function parseContractErrorCode(string $text): ?int
    {
        if (preg_match('/\A#[0-9]+\z/', $text) !== 1) {
            return null;
        }
        $digits = ltrim(substr($text, 1), '0');
        if (strlen($digits) > 10 || (strlen($digits) === 10 && strcmp($digits, '4294967295') > 0)) {
            return null;
        }
        return $digits === '' ? 0 : (int)$digits;
    }

    /**
     * Creates an instance from JSON-RPC response data
     *
     * @param array<string,mixed> $json The JSON response data
     * @return static The created instance
     */
    public static function fromJson(array $json) : SimulateTransactionResponse {
        $result = new SimulateTransactionResponse($json);
        if (isset($json['result'])) {
            if (isset($json['result']['error'])) {
                $result->resultError = $json['result']['error'];
            } else if (isset($json['result']['results'])) {
                $result->results = new SimulateTransactionResults();
                foreach ($json['result']['results'] as $jsonValue) {
                    $value = SimulateTransactionResult::fromJson($jsonValue);
                    $result->results->add($value);
                }
            }
            if (isset($json['result']['latestLedger'])) {
                $result->latestLedger = $json['result']['latestLedger'];
            }
            if (isset($json['result']['transactionData']) && trim($json['result']['transactionData']) != "") {
                $result->transactionData = XdrSorobanTransactionData::fromBase64Xdr($json['result']['transactionData']);
            }

            if (isset($json['result']['events'])) {
                $result->events = array();
                foreach ($json['result']['events'] as $jsonValue) {
                    array_push($result->events, $jsonValue);
                }
            }

            if (isset($json['result']['minResourceFee'])) {
                $result->minResourceFee = intval($json['result']['minResourceFee']);
            }
            if (isset($json['result']['restorePreamble'])) {
                $result->restorePreamble = RestorePreamble::fromJson($json['result']['restorePreamble']);
            }

            if (isset($json['result']['stateChanges'])) {
                $result->stateChanges = array();
                foreach ($json['result']['stateChanges'] as $jsonValue) {
                    $result->stateChanges[] = LedgerEntryChange::fromJson($jsonValue);
                }
            }

        } else if (isset($json['error'])) {
            $result->error = SorobanRpcErrorResponse::fromJson($json);
        }
        return $result;
    }

    /**
     * @return Footprint|null Footprint from the transaction data
     * @throws \InvalidArgumentException If XDR data is malformed
     */
    public function getFootprint() : ?Footprint {
        if ($this->transactionData !== null) {
            $xdrFootprint = $this->transactionData->resources->footprint;
            return new Footprint($xdrFootprint);
        }
        return null;
    }

    /**
     * @return array<SorobanAuthorizationEntry>|null Authorization entries that need to be signed
     * @throws \InvalidArgumentException If XDR data is malformed
     */
    public function getSorobanAuth() : ?array {
        $results = $this->results;
        if ($results !== null && $results->count() > 0 && $results->toArray()[0]->auth !== null) {
            $result = array();
            foreach($results->toArray()[0]->auth as $nextAuthXdr) {
                array_push($result, SorobanAuthorizationEntry::fromBase64Xdr($nextAuthXdr));
            }
            return $result;
        }
        return null;
    }

    /**
     * @return int|null Recommended minimum resource fee to add when submitting the transaction (not present in case of error)
     */
    public function getMinResourceFee(): ?int
    {
        return $this->minResourceFee;
    }

    /**
     * @param int|null $minResourceFee Recommended minimum resource fee to add when submitting the transaction
     * @return void
     */
    public function setMinResourceFee(?int $minResourceFee): void
    {
        $this->minResourceFee = $minResourceFee;
    }

    /**
     * @return XdrSorobanTransactionData|null The recommended Soroban Transaction Data containing refundable fee and resource usage information
     */
    public function getTransactionData(): ?XdrSorobanTransactionData
    {
        return $this->transactionData;
    }

    /**
     * @param XdrSorobanTransactionData|null $transactionData The recommended Soroban Transaction Data
     * @return void
     */
    public function setTransactionData(?XdrSorobanTransactionData $transactionData): void
    {
        $this->transactionData = $transactionData;
    }

    /**
     * @return int The sequence number of the latest ledger known to Soroban RPC at the time it handled the request
     */
    public function getLatestLedger(): int
    {
        return $this->latestLedger;
    }

    /**
     * @param int $latestLedger The sequence number of the latest ledger known to Soroban RPC at the time it handled the request
     * @return void
     */
    public function setLatestLedger(int $latestLedger): void
    {
        $this->latestLedger = $latestLedger;
    }

    /**
     * @return SimulateTransactionResults|null Results for the Host Function invocation (only present on successful simulation of InvokeHostFunction operations)
     */
    public function getResults(): ?SimulateTransactionResults
    {
        return $this->results;
    }

    /**
     * @param SimulateTransactionResults|null $results Results for the Host Function invocation
     * @return void
     */
    public function setResults(?SimulateTransactionResults $results): void
    {
        $this->results = $results;
    }


    /**
     * @return string|null Error details explaining why the invoke host function call failed
     */
    public function getResultError(): ?string
    {
        return $this->resultError;
    }

    /**
     * @param string|null $resultError Error details explaining why the invoke host function call failed
     * @return void
     */
    public function setResultError(?string $resultError): void
    {
        $this->resultError = $resultError;
    }

    /**
     * @return array<string>|null Array of serialized base64 event strings emitted during contract invocation (can be present on error for extra context)
     */
    public function getEvents(): ?array
    {
        return $this->events;
    }

    /**
     * @param array<string>|null $events Array of serialized base64 event strings emitted during contract invocation
     * @return void
     */
    public function setEvents(?array $events): void
    {
        $this->events = $events;
    }

    /**
     * @return RestorePreamble|null Preamble indicating archived ledger entries that need restoration before submission
     */
    public function getRestorePreamble(): ?RestorePreamble
    {
        return $this->restorePreamble;
    }

    /**
     * @param RestorePreamble|null $restorePreamble Preamble indicating archived ledger entries that need restoration
     * @return void
     */
    public function setRestorePreamble(?RestorePreamble $restorePreamble): void
    {
        $this->restorePreamble = $restorePreamble;
    }

    /**
     * @return array<LedgerEntryChange>|null Array of ledger entries before and after simulation for tracking state changes
     */
    public function getStateChanges(): ?array
    {
        return $this->stateChanges;
    }

    /**
     * @param array<LedgerEntryChange>|null $stateChanges Array of ledger entries before and after simulation
     * @return void
     */
    public function setStateChanges(?array $stateChanges): void
    {
        $this->stateChanges = $stateChanges;
    }

}
