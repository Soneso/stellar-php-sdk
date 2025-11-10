<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Responses\Operations;

/**
 * Represents an extend footprint TTL operation response from Horizon API
 *
 * This Soroban operation extends the time-to-live (TTL) of smart contract data entries in the ledger.
 * Contract data and code have limited lifespans and must be periodically extended to prevent archival.
 * This operation bumps the expiration ledger for entries in the read-only footprint, ensuring they
 * remain accessible for contract execution.
 *
 * @package Soneso\StellarSDK\Responses\Operations
 * @see OperationResponse Base operation response
 * @see https://developers.stellar.org/api/resources/operations/object/extend-footprint-ttl Horizon Extend Footprint TTL Operation
 */
class ExtendFootprintTTLOperationResponse extends OperationResponse
{
    public int $extendTo;

    protected function loadFromJson(array $json) : void {
        if (isset($json['extend_to'])) {
            $this->extendTo = $json['extend_to'];
        }
        parent::loadFromJson($json);
    }

    public static function fromJson(array $jsonData) : ExtendFootprintTTLOperationResponse {
        $result = new ExtendFootprintTTLOperationResponse();
        $result->loadFromJson($jsonData);
        return $result;
    }

    /**
     * Gets the ledger number to which the TTL is extended
     *
     * @return int The target ledger sequence for expiration
     */
    public function getExtendTo(): int
    {
        return $this->extendTo;
    }

    /**
     * Sets the ledger number to which the TTL is extended
     *
     * @param int $extendTo The target ledger sequence for expiration
     * @return void
     */
    public function setExtendTo(int $extendTo): void
    {
        $this->extendTo = $extendTo;
    }

}