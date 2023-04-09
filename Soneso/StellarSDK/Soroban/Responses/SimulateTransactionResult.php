<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;



use Soneso\StellarSDK\Soroban\Footprint;
use Soneso\StellarSDK\Xdr\XdrLedgerFootprint;

/**
 * Used as a part of simulate transaction
 * See: https://soroban.stellar.org/api/methods/simulateTransaction
 */
class SimulateTransactionResult
{
    /// (optional) Only present on success. xdr-encoded return value of the contract call operation.
    public ?string $xdr;

    ///  The contract data ledger keys which were accessed when simulating this operation.
    public ?Footprint $footprint = null;

    /// Per-address authorizations recorded when simulating this operation. (an array of serialized base64 strings of [XdrContractAuth])
    public ?array $auth = null; //[string xdr]

    /// Events emitted during the contract invocation. (an array of serialized base64 strings of [XdrDiagnosticEvent])
    public ?array $events = null; //[string xdr]

    protected function loadFromJson(array $json) : void {
        if (isset($json['xdr'])) {
            $this->xdr = $json['xdr'];
        }

        if (isset($json['auth'])) {
            $this->auth = array();
            foreach ($json['auth'] as $jsonValue) {
                array_push($this->auth, $jsonValue);
            }
        }

        if (isset($json['events'])) {
            $this->events = array();
            foreach ($json['events'] as $jsonValue) {
                array_push($this->events, $jsonValue);
            }
        }

        if (isset($json['footprint']) && $json['footprint'] != "") {
            $this->footprint = Footprint::fromBase64Xdr($json['footprint']);
        }
    }

    public static function fromJson(array $json) : SimulateTransactionResult {
        $result = new SimulateTransactionResult();
        $result->loadFromJson($json);
        return $result;
    }

    /**
     * @return string|null
     */
    public function getXdr(): ?string
    {
        return $this->xdr;
    }

    /**
     * @return Footprint|null
     */
    public function getFootprint(): ?Footprint
    {
        return $this->footprint;
    }

    /**
     * @return array|null
     */
    public function getAuth(): ?array
    {
        return $this->auth;
    }

    /**
     * @return array|null
     */
    public function getEvents(): ?array
    {
        return $this->events;
    }


}