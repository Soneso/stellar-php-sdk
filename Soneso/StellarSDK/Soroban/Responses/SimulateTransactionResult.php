<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;


use Soneso\StellarSDK\Xdr\XdrSCVal;

/**
 * Used as a part of simulate transaction
 * See: https://soroban.stellar.org/api/methods/simulateTransaction
 */
class SimulateTransactionResult
{
    /// (optional) Only present on success. xdr-encoded return value of the contract call operation.
    public ?string $xdr;

    /// Per-address authorizations recorded when simulating this operation. (an array of serialized base64 strings of [XdrContractAuth])
    public ?array $auth = null; //[string xdr]

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
    }

    public static function fromJson(array $json) : SimulateTransactionResult {
        $result = new SimulateTransactionResult();
        $result->loadFromJson($json);
        return $result;
    }

    public function getResultValue(): ?XdrSCVal {
        if($this->xdr != null) {
            return XdrSCVal::fromBase64Xdr($this->xdr);
        }
        return null;
    }
    /**
     * @return string|null
     */
    public function getXdr(): ?string
    {
        return $this->xdr;
    }

    /**
     * @return array|null
     */
    public function getAuth(): ?array
    {
        return $this->auth;
    }
}