<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

use Soneso\StellarSDK\Soroban\Footprint;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionData;

/**
 * Response that will be received when submitting a trial contract invocation.
 * See: https://developers.stellar.org/network/soroban-rpc/api-reference/methods/simulateTransaction
 */
class SimulateTransactionResponse extends SorobanRpcResponse
{
    /**
     * @var int $latestLedger The sequence number of the latest ledger known to Soroban RPC at the time
     * it handled the request.
     */
    public int $latestLedger;

    /**
     * @var int|null $minResourceFee (optional) Recommended minimum resource fee to add when
     * submitting the transaction. This fee is to be added on top of the Stellar network fee.
     * Not present in case of error.
     */
    public ?int $minResourceFee = null;

    /**
     * @var SimulateTransactionCost $cost (optional) - The cost object is legacy, inaccurate, and will be
     * deprecated in future RPC releases. Please decode transactionData XDR to retrieve the correct resources.
     */
    public SimulateTransactionCost $cost;

    /**
     * @var SimulateTransactionResults|null This object will only have one element: the result for the Host
     * Function invocation. Only present on successful simulation (i.e. no error) of InvokeHostFunction operations.
     */
    public ?SimulateTransactionResults $results = null;

    /**
     * @var XdrSorobanTransactionData|null The recommended Soroban Transaction Data to use when submitting the
     * simulated transaction. This data contains the refundable fee and resource usage information such as the
     * ledger footprint and IO access data.
     */
    public ?XdrSorobanTransactionData $transactionData = null;

    /**
     * @var array<String>|null (optional) Array of serialized base64 strings - Array of the events emitted during
     * the contract invocation. The events are ordered by their emission time. (an array of serialized base64 strings).
     * Only present when simulating of InvokeHostFunction operations, note that it can be present on error,
     * providing extra context about what failed.
     */
    public ?array $events = null;

    /**
     * @var RestorePreamble|null (optional) - It can only be present on successful simulation (i.e. no error)
     * of InvokeHostFunction operations. If present, it indicates that the simulation detected archived ledger entries
     * which need to be restored before the submission of the InvokeHostFunction operation. The minResourceFee
     * and transactionData fields should be used to submit a transaction containing a RestoreFootprint operation
     */
    public ?RestorePreamble $restorePreamble = null;

    /**
     * @var array<LedgerEntryChange>|null $stateChanges (optional) - On successful simulation of InvokeHostFunction
     * operations, this field will be an array of LedgerEntrys before and after simulation occurred.
     * Note that at least one of before or after will be present: before and no after indicates a deletion event,
     * the inverse is a creation event, and both present indicates an update event. Or just check the type.
     */
    public ?array $stateChanges = null;

    /**
     * @var string|null $resultError (optional) - This field will include details about why the invoke host function
     * call failed. Only present if the transaction simulation failed.
     */
    public ?string $resultError = null;

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
            if (isset($json['result']['cost'])) {
                $result->cost = SimulateTransactionCost::fromJson($json['result']['cost']);
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
     * @return Footprint|null Footprint from the transaction data.
     */
    public function getFootprint() : ?Footprint {
        if ($this->transactionData != null) {
            $xdrFootprint = $this->transactionData->resources->footprint;
            return new Footprint($xdrFootprint);
        }
        return null;
    }

    /**
     * @return array<SorobanAuthorizationEntry>|null auth entries that eventually need to be signed.
     */
    public function getSorobanAuth() : ?array {
        $results = $this->results;
        if ($results!= null && $results->count() > 0 && $results->toArray()[0]->auth != null) {
            $result = array();
            foreach($results->toArray()[0]->auth as $nextAuthXdr) {
                array_push($result, SorobanAuthorizationEntry::fromBase64Xdr($nextAuthXdr));
            }
            return $result;
        }
        return null;
    }

    /**
     * @return int|null (optional) Recommended minimum resource fee to add when
     *  submitting the transaction. This fee is to be added on top of the Stellar network fee.
     *  Not present in case of error.
     */
    public function getMinResourceFee(): ?int
    {
        return $this->minResourceFee;
    }

    /**
     * @param int|null $minResourceFee (optional) Recommended minimum resource fee to add when
     *  submitting the transaction. This fee is to be added on top of the Stellar network fee.
     *  Not present in case of error.
     */
    public function setMinResourceFee(?int $minResourceFee): void
    {
        $this->minResourceFee = $minResourceFee;
    }

    /**
     * @return XdrSorobanTransactionData|null The recommended Soroban Transaction Data to use when submitting the
     *  simulated transaction. This data contains the refundable fee and resource usage information such as the
     *  ledger footprint and IO access data.
     */
    public function getTransactionData(): ?XdrSorobanTransactionData
    {
        return $this->transactionData;
    }

    /**
     * @param XdrSorobanTransactionData|null $transactionData The recommended Soroban Transaction Data to use when
     * submitting the simulated transaction. This data contains the refundable fee and resource usage information
     * such as the ledger footprint and IO access data.
     */
    public function setTransactionData(?XdrSorobanTransactionData $transactionData): void
    {
        $this->transactionData = $transactionData;
    }

    /**
     * @return int The sequence number of the latest ledger known to Soroban RPC at the time
     *  it handled the request.
     */
    public function getLatestLedger(): int
    {
        return $this->latestLedger;
    }

    /**
     * @param int $latestLedger The sequence number of the latest ledger known to Soroban RPC at the time
     *  it handled the request.
     */
    public function setLatestLedger(int $latestLedger): void
    {
        $this->latestLedger = $latestLedger;
    }

    /**
     * @return SimulateTransactionResults|null If error is present then results will not be in the response.
     * will only have one element: the result for the Host Function invocation. Only present on
     * successful simulation (i.e. no error) of InvokeHostFunction operations.
     */
    public function getResults(): ?SimulateTransactionResults
    {
        return $this->results;
    }

    /**
     * @param SimulateTransactionResults|null $results If error is present then results will not be in the response.
     *  will only have one element: the result for the Host Function invocation. Only present on
     *  successful simulation (i.e. no error) of InvokeHostFunction operations.
     */
    public function setResults(?SimulateTransactionResults $results): void
    {
        $this->results = $results;
    }


    /**
     * @return string|null (optional) - This field will include details about why the invoke host function
     *  call failed. Only present if the transaction simulation failed.
     */
    public function getResultError(): ?string
    {
        return $this->resultError;
    }

    /**
     * @param string|null $resultError (optional) - This field will include details about why the invoke host function
     *  call failed. Only present if the transaction simulation failed.
     */
    public function setResultError(?string $resultError): void
    {
        $this->resultError = $resultError;
    }

    /**
     * @return SimulateTransactionCost (optional) - The cost object is legacy, inaccurate, and will be
     *  deprecated in future RPC releases. Please decode transactionData XDR to retrieve the correct resources.
     */
    public function getCost(): SimulateTransactionCost
    {
        return $this->cost;
    }

    /**
     * @param SimulateTransactionCost $cost (optional) - The cost object is legacy, inaccurate, and will be
     *  deprecated in future RPC releases. Please decode transactionData XDR to retrieve the correct resources.
     */
    public function setCost(SimulateTransactionCost $cost): void
    {
        $this->cost = $cost;
    }

    /**
     * @return array<String>|null (optional) Array of serialized base64 strings - Array of the events emitted during
     *  the contract invocation. The events are ordered by their emission time. (an array of serialized base64 strings).
     *  Only present when simulating of InvokeHostFunction operations, note that it can be present on error,
     *  providing extra context about what failed.
     */
    public function getEvents(): ?array
    {
        return $this->events;
    }

    /**
     * @param array<String>|null $events (optional) Array of serialized base64 strings - Array of the events emitted during
     *  the contract invocation. The events are ordered by their emission time. (an array of serialized base64 strings).
     *  Only present when simulating of InvokeHostFunction operations, note that it can be present on error,
     *  providing extra context about what failed.
     */
    public function setEvents(?array $events): void
    {
        $this->events = $events;
    }

    /**
     * @return RestorePreamble|null (optional) - It can only be present on successful simulation (i.e. no error)
     *  of InvokeHostFunction operations. If present, it indicates that the simulation detected archived ledger entries
     *  which need to be restored before the submission of the InvokeHostFunction operation. The minResourceFee
     *  and transactionData fields should be used to submit a transaction containing a RestoreFootprint operation.
     */
    public function getRestorePreamble(): ?RestorePreamble
    {
        return $this->restorePreamble;
    }

    /**
     * @param RestorePreamble|null $restorePreamble (optional) - It can only be present on successful simulation
     * (i.e. no error) of InvokeHostFunction operations. If present, it indicates that the simulation detected
     * archived ledger entries which need to be restored before the submission of the InvokeHostFunction operation.
     * The minResourceFee and transactionData fields should be used to submit a transaction containing a
     * RestoreFootprint operation.
     */
    public function setRestorePreamble(?RestorePreamble $restorePreamble): void
    {
        $this->restorePreamble = $restorePreamble;
    }

    /**
     * @return array<LedgerEntryChange>|null (optional) - On successful simulation of InvokeHostFunction
     *  operations, this field will be an array of LedgerEntrys before and after simulation occurred.
     *  Note that at least one of before or after will be present: before and no after indicates a deletion event,
     *  the inverse is a creation event, and both present indicates an update event. Or just check the type.
     */
    public function getStateChanges(): ?array
    {
        return $this->stateChanges;
    }

    /**
     * @param array<LedgerEntryChange>|null $stateChanges (optional) - On successful simulation of InvokeHostFunction
     *  operations, this field will be an array of LedgerEntrys before and after simulation occurred.
     *  Note that at least one of before or after will be present: before and no after indicates a deletion event,
     *  the inverse is a creation event, and both present indicates an update event. Or just check the type.
     */
    public function setStateChanges(?array $stateChanges): void
    {
        $this->stateChanges = $stateChanges;
    }

}