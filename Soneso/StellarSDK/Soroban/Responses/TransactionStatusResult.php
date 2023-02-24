<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban\Responses;


use Soneso\StellarSDK\Footprint;

/**
 * Used as a part of get transaction status and send transaction.
 */
class TransactionStatusResult
{
    /// xdr-encoded return value of the contract call
    public string $xdr;

    protected function loadFromJson(array $json) : void {
        if (isset($json['xdr'])) {
            $this->xdr = $json['xdr'];
        }
    }

    public static function fromJson(array $json) : TransactionStatusResult {
        $result = new TransactionStatusResult();
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
}