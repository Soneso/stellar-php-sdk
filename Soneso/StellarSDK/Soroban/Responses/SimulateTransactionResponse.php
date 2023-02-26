<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;

use Soneso\StellarSDK\Soroban\Footprint;

/**
 * Response that will be received when submitting a trial contract invocation.
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
        } else if (isset($json['error'])) {
            $result->error = SorobanRpcErrorResponse::fromJson($json);
        }
        return $result;
    }

    public function getFootprint() : ?Footprint {
        $results = $this->results;
        if ($results!= null && $results->count() == 1) {
            $result = $results->toArray()[0];
            if ($result instanceof SimulateTransactionResult) {
                return $result->footprint;
            }
        }
        return null;
    }

    public function getAuth() : ?array {
        $results = $this->results;
        if ($results!= null && $results->count() == 1) {
            $result = $results->toArray()[0];
            if ($result instanceof SimulateTransactionResult) {
                return $result->auth;
            }
        }
        return null;
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

}