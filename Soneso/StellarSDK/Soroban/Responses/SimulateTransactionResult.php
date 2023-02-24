<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;


use Soneso\StellarSDK\Footprint;

/**
 * Used as a part of simulate transaction
 */
class SimulateTransactionResult
{
    /// xdr-encoded return value of the contract call
    public string $xdr;

    /// Footprint containing the ledger keys expected to be written by this transaction
    public ?Footprint $footprint = null;

    public ?array $auth = null;

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
     * @return string xdr-encoded return value of the contract call.
     */
    public function getXdr(): string
    {
        return $this->xdr;
    }

    /**
     * @param string $xdr
     */
    public function setXdr(string $xdr): void
    {
        $this->xdr = $xdr;
    }

    /**
     * @return Footprint|null
     */
    public function getFootprint(): ?Footprint
    {
        return $this->footprint;
    }

    /**
     * @param Footprint|null $footprint
     */
    public function setFootprint(?Footprint $footprint): void
    {
        $this->footprint = $footprint;
    }

    /**
     * @return array|null
     */
    public function getAuth(): ?array
    {
        return $this->auth;
    }

    /**
     * @param array|null $auth
     */
    public function setAuth(?array $auth): void
    {
        $this->auth = $auth;
    }

}