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
 * https://soroban.stellar.org/api/methods/simulateTransaction
 */
class SimulateTransactionResponse extends SorobanRpcResponse
{

    /// Stringified-number of the current latest ledger observed by the node when this response was generated.
    public string $latestLedger;

    public ?SimulateTransactionResults $results = null;

    /// (optional) only present if the transaction failed. This field will include more details from stellar-core about why the invoke host function call failed.
    public ?string $resultError = null;

    /// Information about the fees expected, instructions used, etc.
    public SimulateTransactionCost $cost;

    /// The recommended Soroban Transaction Data to use when submitting the simulated transaction. This data contains the refundable fee and resource usage information such as the ledger footprint and IO access data.
    public ?XdrSorobanTransactionData $transactionData = null;

    /// Recommended minimum resource fee to add when submitting the transaction. This fee is to be added on top of the Stellar network fee.
    public ?int $minResourceFee = null;

    /// Array of the events emitted during the contract invocation(s). The events are ordered by their emission time. (an array of serialized base64 strings)
    public ?array $events = null; //[string xdr XdrDiagnosticEvent]

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
        } else if (isset($json['error'])) {
            $result->error = SorobanRpcErrorResponse::fromJson($json);
        }
        return $result;
    }

    public function getFootprint() : ?Footprint {
        if ($this->transactionData != null) {
            $xdrFootprint = $this->transactionData->resources->footprint;
            return new Footprint($xdrFootprint);
        }
        return null;
    }


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
     * @return int|null
     */
    public function getMinResourceFee(): ?int
    {
        return $this->minResourceFee;
    }

    /**
     * @param int|null $minResourceFee
     */
    public function setMinResourceFee(?int $minResourceFee): void
    {
        $this->minResourceFee = $minResourceFee;
    }


    /**
     * @return XdrSorobanTransactionData|null
     */
    public function getTransactionData(): ?XdrSorobanTransactionData
    {
        return $this->transactionData;
    }

    /**
     * @param XdrSorobanTransactionData|null $transactionData
     */
    public function setTransactionData(?XdrSorobanTransactionData $transactionData): void
    {
        $this->transactionData = $transactionData;
    }

    /**
     * @return string Stringified-number of the current latest ledger observed by the node when this response was generated.
     */
    public function getLatestLedger(): string
    {
        return $this->latestLedger;
    }

    /**
     * @param string $latestLedger
     */
    public function setLatestLedger(string $latestLedger): void
    {
        $this->latestLedger = $latestLedger;
    }

    /**
     * @return SimulateTransactionResults|null If error is present then results will not be in the response
     */
    public function getResults(): ?SimulateTransactionResults
    {
        return $this->results;
    }

    /**
     * @param SimulateTransactionResults|null $results
     */
    public function setResults(?SimulateTransactionResults $results): void
    {
        $this->results = $results;
    }


    /**
     * @return string|null Error within the result if an error occurs.
     */
    public function getResultError(): ?string
    {
        return $this->resultError;
    }

    /**
     * @param string|null $resultError
     */
    public function setResultError(?string $resultError): void
    {
        $this->resultError = $resultError;
    }

    /**
     * @return SimulateTransactionCost Information about the fees expected, instructions used, etc.
     */
    public function getCost(): SimulateTransactionCost
    {
        return $this->cost;
    }

    /**
     * @param SimulateTransactionCost $cost
     */
    public function setCost(SimulateTransactionCost $cost): void
    {
        $this->cost = $cost;
    }

    /**
     * @return array|null
     */
    public function getEvents(): ?array
    {
        return $this->events;
    }

    /**
     * @param array|null $events
     */
    public function setEvents(?array $events): void
    {
        $this->events = $events;
    }

}